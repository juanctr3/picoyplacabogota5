<?php
/**
 * user/login.php - Iniciar Sesión
 */
session_start();
require_once 'db_connect.php'; 

if (isset($_SESSION['user_id'])) {
    // Redirigir según rol
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';
$msg = '';

if (isset($_GET['registered'])) {
    $msg = "¡Cuenta creada! Inicia sesión para continuar.";
}
if (isset($_GET['reset'])) {
    $msg = "Tu contraseña ha sido restablecida. Ingresa con la nueva clave.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f7f6; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 380px; }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 25px; font-size: 1.8em; }
        
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1em; transition: 0.2s; }
        input:focus { border-color: #3498db; outline: none; }
        
        button { width: 100%; background: #3498db; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; margin-bottom: 15px; transition: 0.2s; }
        button:hover { background: #2980b9; }
        
        .msg-box { padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 0.9em; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .links { text-align: center; font-size: 0.9em; margin-top: 15px; display: flex; flex-direction: column; gap: 8px; }
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
            <button type="submit">Ingresar</button>
        </form>
        
        <div class="links">
            <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
            <span>¿Aún no tienes cuenta? <strong><a href="register.php">Regístrate gratis</a></strong></span>
        </div>
    </div>
</body>
</html>