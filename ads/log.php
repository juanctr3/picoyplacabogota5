<?php
/**
 * ads/log.php - Módulo de Registro de Eventos y Descuento Financiero (SaaS)
 * CORREGIDO: Unificado a 'impresion' (español) para coincidir con JS y BD.
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
$eventType = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_STRING); // Recibe 'impresion' o 'click'
$citySlug = filter_input(INPUT_GET, 'ciudad', FILTER_SANITIZE_STRING);
$cpcOffer = filter_input(INPUT_GET, 'cpc', FILTER_VALIDATE_FLOAT);
$cpmOffer = filter_input(INPUT_GET, 'cpm', FILTER_VALIDATE_FLOAT);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; 

// --- CORRECCIÓN AQUÍ: Validamos 'impresion' (español) ---
if (!$bannerId || !in_array($eventType, ['impresion', 'click'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros de log inválidos. Se esperaba impresion o click.']);
    exit;
}

// 3. CÁLCULO DE COSTO Y TIPO DE COBRO
$costo = 0.00;
$logEventType = $eventType; 
$needsBilling = false; 

if ($eventType === 'click') {
    $costo = $cpcOffer;
    $needsBilling = true;
} elseif ($eventType === 'impresion') { // --- CORRECCIÓN AQUÍ ---
    // Calculamos el costo por una impresión (CPM / 1000)
    $costo = $cpmOffer / 1000;
    $needsBilling = true;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Iniciar Transacción
    $pdo->beginTransaction();

    // 4. REGISTRO DEL EVENTO (banner_events)
    // Asegúrate que tu tabla en la BD tenga el ENUM('impresion', 'click')
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
        
        $stmtUser = $pdo->prepare("SELECT user_id FROM banners WHERE id = :bannerId");
        $stmtUser->execute([':bannerId' => $bannerId]);
        $userId = $stmtUser->fetchColumn();

        if ($userId) {
            $stmtUpdateBalance = $pdo->prepare("UPDATE users SET account_balance = account_balance - :costo WHERE id = :userId");
            $stmtUpdateBalance->execute([':costo' => $costo, ':userId' => $userId]);

            $stmtCheckBalance = $pdo->prepare("SELECT account_balance, email FROM users WHERE id = :userId");
            $stmtCheckBalance->execute([':userId' => $userId]);
            $userFinancials = $stmtCheckBalance->fetch(PDO::FETCH_ASSOC);
            
            if ($userFinancials) {
                $newBalance = $userFinancials['account_balance'];

                if ($newBalance <= 0) {
                    $stmtDeactivate = $pdo->prepare("UPDATE banners SET is_active = FALSE WHERE user_id = :userId");
                    $stmtDeactivate->execute([':userId' => $userId]);
                }
            }
        }
    }

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Evento registrado y cobrado.']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en log.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno.']);
}
?>