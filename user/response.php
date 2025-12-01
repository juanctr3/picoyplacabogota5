<?php
/**
 * response.php - Página de Respuesta de ePayco (Frontend)
 */
session_start();

// Capturar los parámetros GET de ePayco
$x_response = $_GET['x_response'] ?? 'No se recibió estado';
$x_transaction_id = $_GET['x_transaction_id'] ?? 'N/A';

// URL del botón de regresar
$dashboardUrl = "dashboard.php";

// Estilos y contenido de la tarjeta según el estado
if ($x_response === 'Aceptada') {
    $titulo = "¡Pago Exitoso!";
    $mensaje = "Tu pago #$x_transaction_id fue aprobado. Tu saldo se actualizará pronto.";
    $icono = "✅";
    $clase = "success"; // Verde
} elseif ($x_response === 'Rechazada') {
    $titulo = "Pago Rechazado";
    $mensaje = "Lo sentimos, el pago #$x_transaction_id fue rechazado.";
    $icono = "❌";
    $clase = "error"; // Rojo
} else {
    $titulo = "Procesando Pago...";
    $mensaje = "Tu pago #$x_transaction_id está pendiente o en proceso de validación.";
    $icono = "⏳";
    $clase = "warning"; // Amarillo
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado del Pago</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Estilos CSS (puedes usar los mismos que para MP) */
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 500px; text-align: center; }
        .icon { font-size: 3em; margin-bottom: 20px; display: block; }
        h1 { color: #2d3436; margin-bottom: 5px; }
        .success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
        .btn-back { display: block; margin-top: 20px; color: #888; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card <?= $clase ?>">
        <span class="icon"><?= $icono ?></span>
        <h1><?= $titulo ?></h1>
        <p><?= $mensaje ?></p>
        <a href="<?= $dashboardUrl ?>" class="btn-back">Volver al Panel</a>
    </div>
</body>
</html>