<?php
/**
 * user/mp_webhook.php - Webhook Server-to-Server con Notificaciones
 * Procesa pagos de Mercado Pago, actualiza saldo y envía alertas.
 */

// 1. Carga de dependencias
require_once 'db_connect.php'; 
require_once '../vendor/autoload.php'; // SDK de Mercado Pago
require_once '../clases/NotificationService.php'; // Sistema de Notificaciones

// 2. Configuración
// --- IMPORTANTE: PEGA TU ACCESS TOKEN DE PRODUCCIÓN AQUÍ ---
MercadoPago\SDK::setAccessToken('APP_USR-4695873334209156-120112-5ce4147ddcc83361ce83858aeab2d023-1190559801'); 
// -----------------------------------------------------------

// 3. Capturar notificación entrante
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Verificar que sea una notificación de pago
// Mercado Pago a veces envía 'type' o 'topic' dependiendo de la versión
$type = $data['type'] ?? $data['topic'] ?? null;

if ($type !== 'payment') {
    http_response_code(200); // Responder OK para evitar reintentos innecesarios de MP
    exit;
}

$payment_id = $data['data']['id'] ?? $data['id'] ?? null;

if (!$payment_id) {
    http_response_code(400);
    exit;
}

try {
    // 4. Consultar estado real en Mercado Pago
    $payment = MercadoPago\Payment::find_by_id($payment_id);

    if ($payment && $payment->status === 'approved') {
        
        $amount = $payment->transaction_amount;
        $userId = $payment->external_reference; // ID del usuario enviado al crear la preferencia
        
        // Validar que el usuario exista
        $stmtCheck = $pdo->prepare("SELECT id, email, account_balance FROM users WHERE id = :id");
        $stmtCheck->execute([':id' => $userId]);
        $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // 5. Actualizar Saldo en Base de Datos
            // Nota: Idealmente, verifica aquí si la transacción ya fue procesada (tabla transactions) para evitar duplicados.
            // Para este ejemplo, sumamos directamente.
            
            $stmtUpdate = $pdo->prepare("UPDATE users SET account_balance = account_balance + :monto WHERE id = :id");
            $stmtUpdate->execute([':monto' => $amount, ':id' => $userId]);
            
            // 6. ENVIAR NOTIFICACIONES (Email + WhatsApp)
            try {
                $notifier = new NotificationService($pdo);
                
                // Calcular nuevo saldo aproximado para el mensaje
                $nuevoSaldo = $user['account_balance'] + $amount;
                
                $notifier->notify($userId, 'recharge_success', [
                    '%amount%'  => number_format($amount, 0, ',', '.'),
                    '%balance%' => number_format($nuevoSaldo, 0, ',', '.')
                ]);
                
                error_log("Pago MP Aprobado: Usuario $userId recargó $$amount. Notificación enviada.");

            } catch (Exception $eNotify) {
                error_log("Error enviando notificación MP: " . $eNotify->getMessage());
                // No detenemos el proceso si falla la notificación
            }
        }
    }

    // Responder a Mercado Pago que recibimos la info correctamente
    http_response_code(200);

} catch (Exception $e) {
    error_log("MP Webhook Error Fatal: " . $e->getMessage());
    http_response_code(500);
}
?>