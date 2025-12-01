<?php
/**
 * user/mp_webhook.php - Webhook Server-to-Server
 */
require_once 'db_connect.php'; 
require_once '../vendor/autoload.php'; 

// --- ACCESS TOKEN ---
MercadoPago\SDK::setAccessToken('APP_USR-4695873334209156-120112-5ce4147ddcc83361ce83858aeab2d023-1190559801');
// --------------------

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Solo nos interesan notificaciones de pagos
if (!isset($data['type']) || $data['type'] !== 'payment') {
    http_response_code(200); 
    exit;
}

$payment_id = $data['data']['id'];

try {
    $payment = MercadoPago\Payment::find_by_id($payment_id);

    if ($payment && $payment->status === 'approved') {
        $amount = $payment->transaction_amount;
        $userId = $payment->external_reference; 
        
        // Verificar usuario
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $stmtCheck->execute([':id' => $userId]);
        
        if ($stmtCheck->fetch()) {
            // Sumar Saldo
            $stmtUpdate = $pdo->prepare("UPDATE users SET account_balance = account_balance + :monto WHERE id = :id");
            $stmtUpdate->execute([':monto' => $amount, ':id' => $userId]);
        }
    }
    http_response_code(200);

} catch (Exception $e) {
    http_response_code(500);
}
?>