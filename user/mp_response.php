<?php
/**
 * user/mp_response.php - Respuesta de Mercado Pago
 */
session_start();
require_once 'db_connect.php'; 

$status = $_GET['collection_status'] ?? '';
$external_reference = $_GET['external_reference'] ?? ''; // ID del usuario

$titulo = "Procesando...";
$mensaje = "Verificando pago...";
$clase = "info";

if ($status == 'approved') {
    $titulo = "¡Pago Exitoso!";
    $mensaje = "Tu recarga ha sido aprobada. El saldo se reflejará en breve.";
    $clase = "success";
} elseif ($status == 'failure') {
    $titulo = "Pago Rechazado";
    $mensaje = "Lo sentimos, el pago no pudo procesarse.";
    $clase = "error";
} elseif ($status == 'pending') {
    $titulo = "Pago Pendiente";
    $mensaje = "Esperando confirmación del banco.";
    $clase = "warning";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado Pago</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f4f7f6; }
        .box { background: white; padding: 40px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .success { color: #2ecc71; } .error { color: #e74c3c; } .warning { color: orange; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="box">
        <h1 class="<?= $clase ?>"><?= $titulo ?></h1>
        <p><?= $mensaje ?></p>
        <a href="dashboard.php" class="btn">Volver al Panel</a>
    </div>
</body>
</html>