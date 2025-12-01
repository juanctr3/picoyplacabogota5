<?php
/**
 * user/login.php - Inicio de Sesión para Anunciantes
 */
require_once 'db_connect.php'; 
session_start();

$error = '';

// Si ya hay una sesión activa, redirigir al panel
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['registered'])) {
    $error = '¡Registro exitoso! Por favor, inicie sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingrese sus credenciales.';
    } else {
        try {
            // 1. Buscar usuario por email
            $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // 2. Autenticación exitosa: Iniciar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // 3. Redirección según el rol
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/index.php"); // Redirige al panel principal
                } else {
                    header("Location: dashboard.php"); // Redirige al panel de anunciantes
                }
                exit;

            } else {
                $error = 'Credenciales inválidas. Intente de nuevo.';
            }

        } catch (PDOException $e) {
            $error = 'Error de base de datos durante el inicio de sesión.';
            error_log("Login DB Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f7f6; }
        .form-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { color: #3498db; text-align: center; margin-bottom: 25px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; background-color: #3498db; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error-msg { color: #e74c3c; text-align: center; margin-bottom: 15px; }
        .register-link { text-align: center; margin-top: 20px; }
        .success-msg { color: #27ae60; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Iniciar Sesión</h1>
        <?php if (isset($_GET['registered'])): ?>
            <div class="success-msg">¡Registro exitoso! Por favor, inicie sesión.</div>
        <?php endif; ?>
        <?php if ($error && !isset($_GET['registered'])): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
        <div class="register-link">
            ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
        </div>
    </div>
</body>
</html>