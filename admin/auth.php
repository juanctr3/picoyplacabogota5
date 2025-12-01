<?php
/**
 * admin/auth.php
 * Sistema de Login simple para proteger el panel.
 */
session_start();

// --- CONFIGURA TU CONTRASEÑA AQUÍ ---
$PASSWORD_ADMIN = 'admin123'; // <--- ¡CÁMBIALA!

// Lógica de Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Lógica de Login
if (isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === $PASSWORD_ADMIN) {
        $_SESSION['admin_logged'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error_login = "Contraseña incorrecta";
    }
}

// Si no está logueado, mostrar formulario de login y DETENER la ejecución
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Acceso Restringido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 320px; }
        input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #6c5ce7; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .error { color: red; font-size: 0.9em; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔒 Admin</h2>
        <?php if(isset($error_login)) echo "<span class='error'>$error_login</span>"; ?>
        <form method="POST">
            <input type="password" name="login_pass" placeholder="Contraseña" required autofocus>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
<?php
    exit; // IMPORTANTE: Detiene la carga del resto de la página
}
?>
