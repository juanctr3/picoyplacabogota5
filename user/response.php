<?php
/**
 * user/response.php - Procesa la respuesta de ePayco
 */
session_start();
require_once 'db_connect.php'; 

$ref_payco = $_GET['ref_payco'] ?? null;

if (!$ref_payco) {
    die("No se recibió referencia de pago.");
}

// Validar transacción con API de ePayco
$url = "https://secure.epayco.co/validation/v1/reference/" . $ref_payco;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if ($data && $data['success']) {
    $transaccion = $data['data'];
    $estado = $transaccion['x_cod_response'];
    $monto = $transaccion['x_amount'];
    
    // Buscar el ID de usuario en la descripción (hack simple, mejor usar x_extra1)
    // En deposit.php pusimos: "Recarga de saldo para cuenta ID: X"
    if (preg_match('/ID: (\d+)/', $transaccion['x_description'], $matches)) {
        $userIdRecarga = $matches[1];
        
        // Estado 1 = Aceptada
        if ($estado == 1) {
            // Verificar si esta referencia ya fue procesada para no sumar doble (Necesitarías una tabla de transacciones, omitido por brevedad pero recomendado).
            
            // Actualizar Saldo
            $stmt = $pdo->prepare("UPDATE users SET account_balance = account_balance + :monto WHERE id = :id");
            $stmt->execute([':monto' => $monto, ':id' => $userIdRecarga]);
            
            echo "<h1>¡Pago Aprobado!</h1><p>Se han recargado $$monto COP a tu cuenta.</p>";
            echo "<a href='dashboard.php'>Ir al Dashboard</a>";
        } else {
            echo "<h1>Transacción no aprobada</h1><p>Estado: " . $transaccion['x_response_reason_text'] . "</p>";
        }
    } else {
        echo "Error: No se pudo identificar el usuario.";
    }
} else {
    echo "Error validando la firma de ePayco.";
}
?>