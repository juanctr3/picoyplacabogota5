<?php
/**
 * user/reset_password.php - Establecer nueva clave
 */
require_once 'db_connect.php'; 

$token = $_GET['token'] ?? '';
$msg = '';
$valid = false;

// Validar Token
if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = :tk AND reset_expires > NOW()");
    $stmt->execute([':tk' => $token]);
    $user = $stmt->fetch();
    if ($user) $valid = true;
    else $msg = '<div style="color:red">El enlace es inválido o ha expirado.</div>';
} else {
    header("Location: login.php"); exit;
}

// Procesar Cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $pass = $_POST['password'];
    if (strlen($pass) < 6) {
        $msg = '<div style="color:red">Mínimo 6 caracteres.</div>';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        // Actualizar y borrar token para que no se use de nuevo
        $upd = $pdo->prepare("UPDATE users SET password_hash = :h, reset_token = NULL, reset_expires = NULL WHERE id = :id");
        $upd->execute([':h' => $hash, ':id' => $user['id']]);
        
        $msg = '<div style="color:green">¡Contraseña actualizada! Redirigiendo...</div>';
        header("refresh:2;url=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f7f6; }
        .box { background: white; padding: 30px; border-radius: 8px; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Nueva Contraseña</h2>
        <?= $msg ?>
        <?php if ($valid): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Escribe tu nueva clave" required>
            <button type="submit">Guardar</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>