<?php
/**
 * user/confirmation.php - Webhook ePayco (Corregido: Envío de Balance)
 */
require_once 'db_connect.php'; 

function logger($msg) {
    file_put_contents('epayco_debug.log', "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

// 1. CARGAR CONFIGURACIÓN DESDE BD
$stmtConf = $pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('epayco_p_key', 'epayco_customer_id')");
$configs = $stmtConf->fetchAll(PDO::FETCH_KEY_PAIR);

$p_key = $configs['epayco_p_key'] ?? '';
$p_cust_id_cliente = $configs['epayco_customer_id'] ?? '';

if (empty($p_key) || empty($p_cust_id_cliente)) {
    logger("ERROR FATAL: Credenciales ePayco no configuradas.");
    die("Config Error");
}

$x_ref_payco       = $_POST['x_ref_payco'] ?? '';
$x_transaction_id  = $_POST['x_transaction_id'] ?? '';
$x_amount          = $_POST['x_amount'] ?? '';
$x_currency_code   = $_POST['x_currency_code'] ?? '';
$x_signature       = $_POST['x_signature'] ?? '';
$x_cod_response    = $_POST['x_cod_response'] ?? 0;
$x_id_invoice      = $_POST['x_id_invoice'] ?? '';

if (!$x_ref_payco) die("No data");

// Validar Firma
$cadena = $p_cust_id_cliente . '^' . $p_key . '^' . $x_ref_payco . '^' . $x_transaction_id . '^' . $x_amount . '^' . $x_currency_code;
$signature_local = hash('sha256', $cadena);

if ($signature_local !== $x_signature) {
    logger("Firma inválida.");
    die("Firma invalida");
}

// Procesar
if ($x_cod_response == 1) { 
    $partes = explode('-', $x_id_invoice);
    $userId = end($partes);

    if (is_numeric($userId)) {
        try {
            // Verificar usuario y obtener saldo actual
            $stmt = $pdo->prepare("SELECT account_balance FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Sumar Saldo
                $stmtUpd = $pdo->prepare("UPDATE users SET account_balance = account_balance + :monto WHERE id = :id");
                $stmtUpd->execute([':monto' => $x_amount, ':id' => $userId]);
                
                // Calcular nuevo saldo para la notificación
                $nuevoSaldo = $user['account_balance'] + $x_amount;
                
                logger("EXITO: Recarga de $$x_amount al usuario $userId. Nuevo saldo: $nuevoSaldo");
                echo "x_cod_response=1"; 

                // Notificar
                if (file_exists('../clases/NotificationService.php')) {
                    require_once '../clases/NotificationService.php';
                    $notifier = new NotificationService($pdo);
                    
                    // AQUÍ ESTABA EL ERROR: Ahora pasamos %balance%
                    $notifier->notify($userId, 'recharge_success', [
                        '%amount%' => number_format($x_amount, 0, ',', '.'),
                        '%balance%' => number_format($nuevoSaldo, 0, ',', '.')
                    ]);
                }
            }
        } catch (Exception $e) {
            logger("Error BD: " . $e->getMessage());
        }
    }
} else {
    echo "x_cod_response=" . $x_cod_response;
}
?>