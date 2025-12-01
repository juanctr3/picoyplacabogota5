<?php
/**
 * user/register.php - Registro de Nuevos Anunciantes
 */
require_once 'db_connect.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 1. Validación simple
    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingrese un email y una contraseña.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email es inválido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // 2. Hash de Contraseña para Seguridad
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // 3. Inserción del nuevo usuario (Anunciante)
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, 'advertiser')");
            $stmt->execute([
                ':email' => $email,
                ':password_hash' => $passwordHash
            ]);
            
            // 4. Redirección
            header("Location: login.php?registered=true");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // Código para entrada duplicada (email)
                $error = 'El email ya se encuentra registrado.';
            } else {
                $error = 'Error de base de datos al intentar registrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Anunciante</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f7f6; }
        .form-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { color: #3498db; text-align: center; margin-bottom: 25px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; background-color: #2ecc71; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error-msg { color: #e74c3c; text-align: center; margin-bottom: 15px; }
        .login-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Registrarse como Anunciante</h1>
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Crear Cuenta</button>
        </form>
        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia Sesión aquí</a>
        </div>
    </div>
</body>
</html>