<?php
/**
 * user/login.php - Iniciar Sesión (Con Captcha y Seguridad)
 */
session_start();
require_once 'db_connect.php'; 

// Redirección si ya está logueado
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

// Cargar Configuración (Para Captcha)
$configs = [];
try {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

$recaptchaSiteKey = $configs['recaptcha_site_key'] ?? '';
$recaptchaSecret  = $configs['recaptcha_secret_key'] ?? '';

$error = '';
$msg = '';

if (isset($_GET['registered'])) $msg = "¡Cuenta creada! Inicia sesión para continuar.";
if (isset($_GET['reset'])) $msg = "Tu contraseña ha sido restablecida.";

// PROCESAR LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $gResponse = $_POST['g-recaptcha-response'] ?? '';

    // 1. Validar Captcha (Si está configurado)
    $captchaValido = true;
    if (!empty($recaptchaSiteKey) && !empty($recaptchaSecret)) {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$gResponse}");
        $captchaData = json_decode($verify);
        if (!$captchaData->success) {
            $captchaValido = false;
            $error = "Por favor verifica que no eres un robot.";
        }
    }

    if ($captchaValido) {
        if (empty($email) || empty($password)) {
            $error = "Por favor completa todos los campos.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, password_hash, role, email FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Login Exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/index.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Credenciales incorrectas.";
                }
            } catch (PDOException $e) {
                $error = "Error de conexión.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f7f6; margin: 0; padding: 20px; }
        .login-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 380px; text-align: center; }
        h1 { color: #2c3e50; margin-bottom: 25px; font-size: 1.8em; }
        
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1em; transition: 0.2s; }
        input:focus { border-color: #3498db; outline: none; }
        
        button { width: 100%; background: #3498db; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; margin-top: 15px; transition: 0.2s; }
        button:hover { background: #2980b9; }
        
        .msg-box { padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9em; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .captcha-box { display: flex; justify-content: center; margin-bottom: 15px; }
        
        .links { font-size: 0.9em; margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
        .links a { color: #7f8c8d; text-decoration: none; }
        .links a:hover { color: #3498db; text-decoration: underline; }
        .links strong a { color: #3498db; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Bienvenido</h1>
        
        <?php if ($error): ?> <div class="msg-box error"><?= htmlspecialchars($error) ?></div> <?php endif; ?>
        <?php if ($msg): ?> <div class="msg-box success"><?= htmlspecialchars($msg) ?></div> <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Correo Electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            
            <?php if(!empty($recaptchaSiteKey)): ?>
                <div class="captcha-box">
                    <div class="g-recaptcha" data-sitekey="<?= $recaptchaSiteKey ?>"></div>
                </div>
            <?php endif; ?>

            <button type="submit">Ingresar</button>
        </form>
        
        <div class="links">
            <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
            <span>¿No tienes cuenta? <strong><a href="register.php">Regístrate gratis</a></strong></span>
        </div>
    </div>
</body>
</html>