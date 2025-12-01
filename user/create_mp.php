<?php
/**
 * user/create_mp.php - Generador de Preferencia de Mercado Pago
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_start();
require_once 'db_connect.php'; 
require_once '../vendor/autoload.php'; // ¡Esta línea ahora sí funcionará!

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$monto = $input['monto'] ?? 0;

if ($monto < 5000) {
    echo json_encode(['error' => 'El monto mínimo es $5.000 COP']);
    exit;
}

// --- CONFIGURACIÓN ---
// Pega aquí tu Access Token (el que copiaste del dashboard de MP)
MercadoPago\SDK::setAccessToken('APP_USR-4695873334209156-120112-5ce4147ddcc83361ce83858aeab2d023-1190559801'); 
// ---------------------

try {
    $preference = new MercadoPago\Preference();

    $item = new MercadoPago\Item();
    $item->title = "Recarga Saldo Publicidad";
    $item->quantity = 1;
    $item->unit_price = (float)$monto;
    $item->currency_id = "COP";
    
    $preference->items = array($item);

    $payer = new MercadoPago\Payer();
    $payer->email = $_SESSION['user_email']; 
    $preference->payer = $payer;

    $preference->external_reference = $_SESSION['user_id'];

    // URLs dinámicas
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
    
    $preference->back_urls = array(
        "success" => $baseUrl . "/user/mp_response.php",
        "failure" => $baseUrl . "/user/mp_response.php",
        "pending" => $baseUrl . "/user/mp_response.php"
    );
    $preference->auto_return = "approved"; 

    $preference->save();

    if (!$preference->id) {
        throw new Exception("Error: No se obtuvo ID de preferencia de MP.");
    }

    echo json_encode([
        'id' => $preference->id,
        'init_point' => $preference->init_point, 
        'sandbox_init_point' => $preference->sandbox_init_point
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error Interno: ' . $e->getMessage()]);
}
?>