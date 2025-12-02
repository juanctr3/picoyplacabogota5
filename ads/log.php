<?php
/**
 * ads/log.php - Módulo de Registro, Cobro y Alertas
 * Versión Final: Integra cobros, validación dinámica de saldo y notificaciones.
 */

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

// 1. CONFIGURACIÓN DE LA BASE DE DATOS
$dbHost = 'localhost';
$dbName = 'picoyplacabogota';   
$dbUser = 'picoyplacabogota';   
$dbPass = 'Q20BsIFHI9j8h2XoYNQm3RmQg';   

// 2. OBTENCIÓN DE DATOS Y SANITIZACIÓN
$bannerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$eventType = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_STRING);
$citySlug = filter_input(INPUT_GET, 'ciudad', FILTER_SANITIZE_STRING);
$cpcOffer = filter_input(INPUT_GET, 'cpc', FILTER_VALIDATE_FLOAT);
$cpmOffer = filter_input(INPUT_GET, 'cpm', FILTER_VALIDATE_FLOAT);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; 

// Validación de parámetros (Acepta 'impresion' en español para coincidir con JS)
if (!$bannerId || !in_array($eventType, ['impresion', 'click'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

// 3. CÁLCULO DE COSTO
$costo = 0.00;
$needsBilling = false; 

if ($eventType === 'click') {
    $costo = $cpcOffer;
    $needsBilling = true;
} elseif ($eventType === 'impresion') {
    // Costo por una impresión (CPM / 1000)
    $costo = $cpmOffer / 1000;
    $needsBilling = true;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Iniciar Transacción (CRÍTICO para dinero)
    $pdo->beginTransaction();

    // 4. REGISTRO DEL EVENTO
    $stmtLog = $pdo->prepare("INSERT INTO banner_events (banner_id, event_type, city_slug, ip_address, cost_applied) 
                               VALUES (:banner_id, :event_type, :city_slug, :ip_address, :costo)");
    $stmtLog->execute([
        ':banner_id' => $bannerId,
        ':event_type' => $eventType,
        ':city_slug' => $citySlug,
        ':ip_address' => $ipAddress,
        ':costo' => $costo
    ]);

    // 5. PROCESAMIENTO FINANCIERO
    if ($needsBilling) {
        
        // Obtener dueño del banner
        $stmtUser = $pdo->prepare("SELECT user_id FROM banners WHERE id = :bannerId");
        $stmtUser->execute([':bannerId' => $bannerId]);
        $userId = $stmtUser->fetchColumn();

        if ($userId) {
            // Descontar costo
            $stmtUpdateBalance = $pdo->prepare("UPDATE users SET account_balance = account_balance - :costo WHERE id = :userId");
            $stmtUpdateBalance->execute([':costo' => $costo, ':userId' => $userId]);

            // Obtener saldo actualizado
            $stmtCheck = $pdo->prepare("SELECT account_balance, email FROM users WHERE id = :userId");
            $stmtCheck->execute([':userId' => $userId]);
            $userFin = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            $newBalance = $userFin['account_balance'];

            // --- LÓGICA DE ALERTAS Y DESACTIVACIÓN ---
            
            // 1. Desactivar si se acabó el saldo
            if ($newBalance <= 0) {
                $stmtDeactivate = $pdo->prepare("UPDATE banners SET is_active = FALSE WHERE user_id = :userId");
                $stmtDeactivate->execute([':userId' => $userId]);
            } 
            // 2. Alerta de Saldo Bajo (Configurable desde Admin)
            else {
                // Obtener umbral dinámico
                $stmtConf = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'low_balance_threshold'");
                $threshold = (float)($stmtConf->fetchColumn() ?: 2000); // Default 2000 si no existe

                // Si el saldo es menor al umbral (y mayor a 0), enviar alerta
                // Nota: Para evitar spam masivo en cada impresión, idealmente se debería tener un flag 'alerta_enviada' en la tabla users.
                // Por simplicidad en este paso, enviamos la notificación.
                if ($newBalance < $threshold) {
                    // Cargar servicio de notificaciones
                    if (file_exists(__DIR__ . '/../clases/NotificationService.php')) {
                        require_once __DIR__ . '/../clases/NotificationService.php';
                        try {
                            $notifier = new NotificationService($pdo);
                            $notifier->notify($userId, 'low_balance', [
                                '%balance%' => number_format($newBalance, 0, ',', '.')
                            ]);
                        } catch (Exception $eNotify) {
                            // No hacer nada si falla la notificación, lo importante es el cobro
                            error_log("Fallo notificación saldo bajo: " . $eNotify->getMessage());
                        }
                    }
                }
            }
        }
    }

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error crítico en log.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de transacción.']);
}
?>