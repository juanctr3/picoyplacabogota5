<?php
/**
 * user/confirmation.php - Webhook con Diagnóstico de Firma Detallado
 */
require_once 'db_connect.php'; 

function logger($msg) {
    $date = date('Y-m-d H:i:s');
    file_put_contents('epayco_debug.log', "[$date] $msg\n", FILE_APPEND);
}

// --- CONFIGURACIÓN ---
$p_key = 'f14eb370c397ef4da95d155660742327096ac1e5'; // <--- ¡VERIFICA QUE SEA LA P_KEY, NO LA PRIVATE_KEY!
// ---------------------

// Recibir datos
$p_cust_id_cliente = $_POST['p_cust_id_cliente'] ?? '';
$x_ref_payco       = $_POST['x_ref_payco'] ?? '';
$x_transaction_id  = $_POST['x_transaction_id'] ?? '';
$x_amount          = $_POST['x_amount'] ?? '';
$x_currency_code   = $_POST['x_currency_code'] ?? '';
$x_signature       = $_POST['x_signature'] ?? '';
$x_cod_response    = $_POST['x_cod_response'] ?? 0;
$x_id_invoice      = $_POST['x_id_invoice'] ?? '';

// Si no hay datos, salir
if (!$x_ref_payco) { die("No data"); }

// Construir la cadena para firmar
// Fórmula oficial: p_cust_id_cliente^p_key^x_ref_payco^x_transaction_id^x_amount^x_currency_code
$cadena_a_firmar = $p_cust_id_cliente . '^' . $p_key . '^' . $x_ref_payco . '^' . $x_transaction_id . '^' . $x_amount . '^' . $x_currency_code;

// Calcular firma local
$signature_local = hash('sha256', $cadena_a_firmar);

// --- LOGUEAR TODO PARA ENCONTRAR EL ERROR ---
logger("--------------------------------------------------");
logger("NUEVA TRANSACCIÓN RECIBIDA ($x_ref_payco)");
logger("Datos Recibidos:");
logger(" - ID Cliente: '$p_cust_id_cliente'");
logger(" - Ref Payco:  '$x_ref_payco'");
logger(" - Trans ID:   '$x_transaction_id'");
logger(" - Monto:      '$x_amount'");
logger(" - Moneda:     '$x_currency_code'");
logger(" - Tu P_KEY:   '" . substr($p_key, 0, 5) . "...' (Oculta por seguridad)");
logger("");
logger("CADENA QUE ARMA TU SERVIDOR: [$cadena_a_firmar]");
logger("");
logger("Firma Calculada: $signature_local");
logger("Firma Recibida:  $x_signature");

if ($signature_local !== $x_signature) {
    logger("❌ ERROR: LAS FIRMAS NO COINCIDEN");
    die("Firma invalida");
}

logger("✅ FIRMAS COINCIDEN. Procesando saldo...");

// Procesar Pago si la firma es válida
if ($x_cod_response == 1) { 
    $partes = explode('-', $x_id_invoice);
    $userId = end($partes);

    if (is_numeric($userId)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET account_balance = account_balance + :monto WHERE id = :id");
            $stmt->execute([':monto' => $x_amount, ':id' => $userId]);
            logger("EXITO: Saldo actualizado para usuario $userId.");
            echo "x_cod_response=1"; 
            
            // Notificar
            if (file_exists('../clases/NotificationService.php')) {
                require_once '../clases/NotificationService.php';
                $notifier = new NotificationService($pdo);
                $notifier->notify($userId, 'recharge_success', ['%amount%' => number_format($x_amount)]);
            }
        } catch (Exception $e) {
            logger("Error BD: " . $e->getMessage());
        }
    } else {
        logger("Error: ID Usuario inválido en factura: $x_id_invoice");
    }
} else {
    logger("Pago no aprobado (Estado: $x_cod_response)");
    echo "x_cod_response=" . $x_cod_response;
}
?>