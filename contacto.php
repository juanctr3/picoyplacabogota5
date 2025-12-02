<?php
/**
 * contacto.php - Formulario de Contacto Público
 */
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once 'admin/db_connect.php'; // Ajusta la ruta si está en raíz

// Cargar Configuración (Captcha y Notificaciones)
$configs = $pdo->query("SELECT config_key, config_value FROM system_config")->fetchAll(PDO::FETCH_KEY_PAIR);
$siteKey = $configs['recaptcha_site_key'] ?? '';
$secretKey = $configs['recaptcha_secret_key'] ?? '';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // 1. VALIDAR CAPTCHA
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}";
    $verifyResponse = file_get_contents($verifyUrl);
    $responseData = json_decode($verifyResponse);

    if (empty($siteKey) || ($responseData && $responseData->success)) {
        // Captcha válido o no configurado (modo dev)
        
        // 2. ENVIAR NOTIFICACIONES
        if (file_exists('clases/NotificationService.php')) {
            require_once 'clases/NotificationService.php';
            $notifier = new NotificationService($pdo);
            
            // A. Notificar al ADMIN (Email + WA)
            // Usamos un "ID ficticio" 0 o null porque el contacto puede no ser usuario registrado
            // El NotificationService necesita ser actualizado para soportar envío sin ID de usuario (ver Paso 3)
            // Por ahora, pasamos los datos directos al método notify modificado
            
            $notifier->notifyCustom('contact_admin', [
                '%name%' => $name,
                '%email%' => $email,
                '%message%' => nl2br($message)
            ], $configs['admin_email']); // Email del admin

            // B. Confirmación al USUARIO (Solo Email)
            $notifier->notifyCustom('contact_user', [
                '%name%' => $name,
                '%message%' => nl2br($message)
            ], $email); // Email del usuario
            
            $msg = '¡Mensaje enviado! Te responderemos pronto.';
            $msgType = 'success';
        } else {
            $msg = 'Error interno: Servicio de notificación no disponible.';
            $msgType = 'error';
        }
    } else {
        $msg = 'Verificación de seguridad fallida. Por favor intenta de nuevo.';
        $msgType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contáctanos</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); width: 100%; max-width: 500px; }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 25px; }
        input, textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; background: #3498db; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; transition: 0.2s; }
        button:hover { background: #2980b9; }
        .msg { padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .back { display: block; text-align: center; margin-top: 20px; color: #7f8c8d; text-decoration: none; }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="card">
        <h1>Contáctanos</h1>
        <?php if($msg): ?><div class="msg <?= $msgType ?>"><?= $msg ?></div><?php endif; ?>
        
        <form method="POST">
            <input type="text" name="name" placeholder="Tu Nombre" required>
            <input type="email" name="email" placeholder="Tu Correo" required>
            <textarea name="message" rows="5" placeholder="¿En qué podemos ayudarte?" required></textarea>
            
            <?php if(!empty($siteKey)): ?>
                <div class="g-recaptcha" data-sitekey="<?= $siteKey ?>" style="margin-bottom: 15px;"></div>
            <?php endif; ?>
            
            <button type="submit">Enviar Mensaje</button>
        </form>
        <a href="index.php" class="back">← Volver al Inicio</a>
    </div>
</body>
</html>