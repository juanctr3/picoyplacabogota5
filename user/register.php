<?php
/**
 * user/register.php - Registro Completo (Captcha + Países + Notificación)
 */
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once 'db_connect.php'; 

// Carga de servicio de notificación
$notifyFile = '../clases/NotificationService.php';
if (file_exists($notifyFile)) { require_once $notifyFile; }

// Cargar Configuración Captcha
$configs = [];
try {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

$recaptchaSiteKey = $configs['recaptcha_site_key'] ?? '';
$recaptchaSecret  = $configs['recaptcha_secret_key'] ?? '';

$error = '';

// PROCESAR REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? ''); 
    $country_code = $_POST['country_code'] ?? '+57';
    $gResponse = $_POST['g-recaptcha-response'] ?? '';

    // 1. Validar Captcha
    $captchaValido = true;
    if (!empty($recaptchaSiteKey) && !empty($recaptchaSecret)) {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$gResponse}");
        $captchaData = json_decode($verify);
        if (!$captchaData->success) {
            $captchaValido = false;
            $error = "Captcha inválido. Intenta de nuevo.";
        }
    }

    if ($captchaValido) {
        if (empty($email) || empty($password)) {
            $error = 'Email y contraseña son obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Formato de email inválido.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Insertar usuario
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, phone, country_code, role) VALUES (:email, :pass, :phone, :ccode, 'advertiser')");
                $stmt->execute([
                    ':email' => $email, 
                    ':pass' => $passwordHash,
                    ':phone' => $phone,
                    ':ccode' => $country_code
                ]);
                $newId = $pdo->lastInsertId();
                
                // Enviar Notificación Bienvenida
                if (class_exists('NotificationService')) {
                    try {
                        $notifier = new NotificationService($pdo);
                        $notifier->notify($newId, 'register_success', ['phone' => $phone]);
                    } catch (Exception $e) { error_log("Error notify: ".$e->getMessage()); }
                }
                
                header("Location: login.php?registered=true");
                exit;

            } catch (PDOException $e) {
                if ($e->getCode() === '23000') $error = 'Este correo ya está registrado.';
                else $error = 'Error de base de datos: ' . $e->getMessage();
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
    <title>Crear Cuenta</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f7f6; margin: 0; padding: 20px; }
        .form-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1em; }
        .phone-group { display: flex; gap: 10px; }
        .phone-group select { width: 35%; } 
        .phone-group input { width: 65%; }  
        button { width: 100%; background: #2ecc71; color: white; padding: 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; margin-top: 10px; transition: 0.2s; }
        button:hover { background: #27ae60; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb; }
        .links { text-align: center; margin-top: 20px; font-size: 0.9em; }
        .links a { color: #3498db; text-decoration: none; }
        .captcha-box { display: flex; justify-content: center; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Crear Cuenta</h1>
        <?php if ($error): ?> <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div> <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" required placeholder="tu@email.com">
            </div>
            
            <div class="form-group">
                <label>Celular (WhatsApp)</label>
                <div class="phone-group">
                    <select id="country_selector" name="country_code">
                        <option value="+57" selected>🇨🇴 +57</option>
                        <option value="+1">🇺🇸 +1</option>
                        <option value="+34">🇪🇸 +34</option>
                        <option value="+52">🇲🇽 +52</option>
                        <option value="+54">🇦🇷 +54</option>
                        <option value="+56">🇨🇱 +56</option>
                        <option value="+51">🇵🇪 +51</option>
                        <option value="+593">🇪🇨 +593</option>
                        <option value="+58">🇻🇪 +58</option>
                        <option value="+55">🇧🇷 +55</option>
                    </select>
                    <input type="tel" id="phone_input" placeholder="3001234567" required>
                </div>
                <input type="hidden" name="phone" id="full_phone">
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required minlength="6">
            </div>
            
            <?php if(!empty($recaptchaSiteKey)): ?>
                <div class="captcha-box">
                    <div class="g-recaptcha" data-sitekey="<?= $recaptchaSiteKey ?>"></div>
                </div>
            <?php endif; ?>
            
            <button type="submit">Registrarse</button>
        </form>
        
        <div class="links"><a href="login.php">¿Ya tienes cuenta? <strong>Inicia Sesión</strong></a></div>
    </div>

    <script>
        const countrySelect = document.getElementById('country_selector');
        const phoneInput = document.getElementById('phone_input');
        const fullPhoneInput = document.getElementById('full_phone');
        
        function updateFull() {
            fullPhoneInput.value = countrySelect.value + phoneInput.value.trim();
        }
        countrySelect.addEventListener('change', updateFull);
        phoneInput.addEventListener('input', updateFull);
        updateFull(); // Init
    </script>
</body>
</html>