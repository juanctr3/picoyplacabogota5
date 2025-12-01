<?php
/**
 * user/forgot_password.php - Solicitud de Recuperación (Timezone Fix)
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php'; 

// Carga segura del servicio
$notifyFile = '../clases/NotificationService.php';
if (file_exists($notifyFile)) { require_once $notifyFile; }

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    try {
        // 1. Buscar usuario
        $stmt = $pdo->prepare("SELECT id, email, phone FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 2. Generar Token (64 caracteres)
            $token = bin2hex(random_bytes(32));

            // 3. Guardar en BD usando LA HORA DE LA BD (DATE_ADD)
            // Esto soluciona el problema de "Enlace expirado" por diferencia de hora
            $stmtUpd = $pdo->prepare("UPDATE users SET reset_token = :tk, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id");
            $stmtUpd->execute([':tk' => $token, ':id' => $user['id']]);

            // 4. Generar Link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $path = dirname($_SERVER['PHP_SELF']); 
            $link = $protocol . "://" . $_SERVER['HTTP_HOST'] . $path . "/reset_password.php?token=" . $token;

            // 5. Enviar Notificación
            $enviado = false;
            if (class_exists('NotificationService')) {
                try {
                    $notifier = new NotificationService($pdo);
                    $notifier->notify($user['id'], 'password_recovery', [
                        '%link%' => $link,
                        'phone'  => $user['phone']
                    ]);
                    $enviado = true;
                } catch (Exception $e) {
                    error_log("Error notificación: " . $e->getMessage());
                }
            }

            if ($enviado) {
                $msg = '¡Enlace enviado! Revisa tu correo o WhatsApp.';
                $msgType = 'success';
            } else {
                $msg = 'Enlace generado, pero hubo un error enviando el mensaje. Contacta soporte.';
                $msgType = 'warning';
            }

        } else {
            $msg = 'Si el correo existe, recibirás un enlace.';
            $msgType = 'success';
        }
    } catch (PDOException $e) {
        $msg = 'Error de sistema: ' . $e->getMessage();
        $msgType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f7f6; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 10px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; margin-bottom: 20px; }
        p { color: #7f8c8d; margin-bottom: 25px; line-height: 1.5; font-size: 0.95em; }
        
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; transition: 0.2s; }
        button:hover { background: #2980b9; }
        
        .msg { padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9em; text-align: left; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        
        .back-link { display: block; margin-top: 20px; color: #7f8c8d; text-decoration: none; font-size: 0.9em; }
        .back-link:hover { color: #3498db; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Recuperar Acceso</h2>
        <p>Ingresa tu correo electrónico registrado.</p>
        
        <?php if ($msg): ?>
            <div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="ejemplo@correo.com" required>
            <button type="submit">Enviar Enlace</button>
        </form>
        
        <a href="login.php" class="back-link">← Volver al Inicio de Sesión</a>
    </div>
</body>
</html>