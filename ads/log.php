<?php
/**
 * ads/log.php - Módulo de Registro de Eventos (Tracking)
 * Recibe el ID del banner y el tipo de evento (impresion/click) y los almacena.
 * Utiliza PHP Data Objects (PDO) para la conexión.
 */

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

// 1. CONFIGURACIÓN DE LA BASE DE DATOS - ¡DEBES EDITAR ESTAS 4 LÍNEAS!
$dbHost = 'localhost';
$dbName = '[TU_NOMBRE_DE_BD]';   // Ejemplo: 'picoyplaca_db'
$dbUser = '[TU_USUARIO_DE_BD]';   // Ejemplo: 'root'
$dbPass = '[TU_CONTRASEÑA]';   // Ejemplo: 'contraseñasegura'

try {
    // Conexión a la Base de Datos
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si la conexión falla, el frontend no se ve afectado.
    error_log("Error de conexión a la BD: " . $e->getMessage());
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Error de servidor.']);
    exit;
}

// 2. OBTENCIÓN Y VALIDACIÓN DE DATOS
$bannerId = $_GET['id'] ?? null;
$eventType = $_GET['tipo'] ?? null;
$citySlug = $_GET['ciudad'] ?? 'unknown'; 
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; // Captura la IP

if (!$bannerId || !$eventType || ($eventType !== 'impresion' && $eventType !== 'click')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros de log inválidos.']);
    exit;
}

// 3. INSERCIÓN SEGURA EN LA BASE DE DATOS
try {
    $stmt = $pdo->prepare("INSERT INTO banner_events (banner_id, event_type, city_slug, ip_address) 
                           VALUES (:banner_id, :event_type, :city_slug, :ip_address)");
    
    $stmt->execute([
        ':banner_id' => $bannerId,
        ':event_type' => $eventType,
        ':city_slug' => $citySlug,
        ':ip_address' => $ipAddress
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Evento registrado con éxito.']);

} catch (PDOException $e) {
    error_log("Error al insertar evento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fallo al registrar evento.']);
}
?>