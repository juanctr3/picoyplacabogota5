<?php
/**
 * user/create_mp.php - Generador MP con Validación Dinámica
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_start();

try {
    require_once 'db_connect.php'; 
    require_once '../vendor/autoload.php'; 

    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $monto = $input['monto'] ?? 0;

    // 1. Consultar monto mínimo dinámico
    $stmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'min_recharge_amount'");
    $min_db = (int)$stmt->fetchColumn();
    if(!$min_db) $min_db = 5000;

    // 2. Validar
    if ($monto < $min_db) {
        echo json_encode(['error' => "El monto mínimo es $" . number_format($min_db) . " COP"]);
        exit;
    }

    // --- CONFIGURACIÓN MP ---
    MercadoPago\SDK::setAccessToken('APP_USR-4695873334209156-120112-5ce4147ddcc83361ce83858aeab2d023-1190559801'); 
    // ------------------------

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

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

    $preference->back_urls = array(
        "success" => $baseUrl . "/mp_response.php",
        "failure" => $baseUrl . "/mp_response.php",
        "pending" => $baseUrl . "/mp_response.php"
    );
    $preference->auto_return = "approved"; 

    $preference->save();

    if (!$preference->id) {
        throw new Exception("Error al crear preferencia en Mercado Pago.");
    }

    echo json_encode([
        'id' => $preference->id,
        'init_point' => $preference->init_point, 
        'sandbox_init_point' => $preference->sandbox_init_point
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>