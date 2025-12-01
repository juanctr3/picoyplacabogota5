<?php
/**
 * ads/log.php - Módulo de Registro de Eventos y Descuento Financiero (SaaS)
 * Implementa el cobro por evento, la verificación de saldo y la desactivación por cuenta agotada.
 */

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

// 1. CONFIGURACIÓN DE LA BASE DE DATOS
$dbHost = 'localhost';
$dbName = 'picoyplacabogota';   
$dbUser = 'picoyplacabogota';   
$dbPass = 'Q20BsIFHI9j8h2XoYNQm3RmQg';   

// Umbral para notificar al usuario que su saldo está bajo
const LOW_BALANCE_THRESHOLD = 5.00; 

// 2. OBTENCIÓN DE DATOS Y SANITIZACIÓN
$bannerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$eventType = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_STRING);
$citySlug = filter_input(INPUT_GET, 'ciudad', FILTER_SANITIZE_STRING);
$cpcOffer = filter_input(INPUT_GET, 'cpc', FILTER_VALIDATE_FLOAT);
$cpmOffer = filter_input(INPUT_GET, 'cpm', FILTER_VALIDATE_FLOAT);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; 

if (!$bannerId || !in_array($eventType, ['impression', 'click'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros de log inválidos o faltantes.']);
    exit;
}

// 3. CÁLCULO DE COSTO Y TIPO DE COBRO
$costo = 0.00;
$logEventType = $eventType; 
$needsBilling = false; 

if ($eventType === 'click') {
    $costo = $cpcOffer;
    $needsBilling = true;
} elseif ($eventType === 'impression') {
    // Calculamos el costo por una impresión (CPM / 1000)
    $costo = $cpmOffer / 1000;
    $needsBilling = true;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Iniciar Transacción (CRÍTICO: Garantiza que el dinero y el log sean seguros)
    $pdo->beginTransaction();

    // 4. REGISTRO DEL EVENTO (banner_events)
    $stmtLog = $pdo->prepare("INSERT INTO banner_events (banner_id, event_type, city_slug, ip_address, cost_applied) 
                               VALUES (:banner_id, :event_type, :city_slug, :ip_address, :costo)");
    $stmtLog->execute([
        ':banner_id' => $bannerId,
        ':event_type' => $logEventType,
        ':city_slug' => $citySlug,
        ':ip_address' => $ipAddress,
        ':costo' => $costo
    ]);

    // 5. PROCESAMIENTO FINANCIERO (Descuento y Verificación de Saldo)
    if ($needsBilling) {
        
        // Obtener ID del usuario vinculado al banner (Se requiere el user_id para cobrar)
        $stmtUser = $pdo->prepare("SELECT user_id FROM banners WHERE id = :bannerId");
        $stmtUser->execute([':bannerId' => $bannerId]);
        $userId = $stmtUser->fetchColumn();

        if ($userId) {
            // Descontar el costo al saldo del usuario
            $stmtUpdateBalance = $pdo->prepare("UPDATE users SET account_balance = account_balance - :costo WHERE id = :userId");
            $stmtUpdateBalance->execute([':costo' => $costo, ':userId' => $userId]);

            // 6. NOTIFICACIONES Y DESACTIVACIÓN POR SALDO BAJO
            $stmtCheckBalance = $pdo->prepare("SELECT account_balance, email FROM users WHERE id = :userId");
            $stmtCheckBalance->execute([':userId' => $userId]);
            $userFinancials = $stmtCheckBalance->fetch(PDO::FETCH_ASSOC);
            $newBalance = $userFinancials['account_balance'];

            if ($newBalance <= 0) {
                // Desactivar campaña si el saldo es <= 0
                $stmtDeactivate = $pdo->prepare("UPDATE banners SET is_active = FALSE WHERE user_id = :userId");
                $stmtDeactivate->execute([':userId' => $userId]);
                error_log("CUENTA AGOTADA: Usuario {$userId} ({$userFinancials['email']}) desactivado.");
                // NOTIFICACIÓN: DISPARAR EMAIL DE CUENTA AGOTADA (simulación)

            } elseif ($newBalance < LOW_BALANCE_THRESHOLD) {
                // Notificar si el saldo está bajo
                error_log("SALDO BAJO: Usuario {$userId} ({$userFinancials['email']}). Saldo: {$newBalance}.");
                // NOTIFICACIÓN: DISPARAR EMAIL DE SALDO BAJO (simulación)
            }
        }
    }

    $pdo->commit(); // Finalizar transacción
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Evento registrado y cobrado.']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack(); // Deshacer cambios si algo falló
    }
    error_log("Error crítico en LOG Y COBRO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fallo en la transacción financiera.']);
}
