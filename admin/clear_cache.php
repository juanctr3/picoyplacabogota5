<?php
/**
 * admin/clear_cache.php - Herramienta de Mantenimiento
 * Limpia la caché de código del servidor (OPCache) y fuerza cabeceras nuevas.
 */
// Forzar UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();
require_once 'db_connect.php';

// Seguridad: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

$mensajes = [];

// 1. Limpiar OPCache (La memoria de scripts de PHP)
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $mensajes[] = "✅ <strong>OPCache:</strong> Memoria de scripts reiniciada correctamente.";
    } else {
        $mensajes[] = "⚠️ <strong>OPCache:</strong> No se pudo reiniciar (o estaba vacía).";
    }
} else {
    $mensajes[] = "ℹ️ <strong>OPCache:</strong> No detectado en este servidor.";
}

// 2. Limpiar Caché de Usuario (APC/APCu) si existe
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    $mensajes[] = "✅ <strong>APCu:</strong> Memoria de variables limpiada.";
}

// 3. Forzar expiración inmediata en el navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento del Sistema</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 50px; text-align: center; color: #333; }
        .card { background: white; max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #3498db; padding-bottom: 10px; display: inline-block; }
        
        ul { text-align: left; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #eee; list-style: none; margin: 20px 0; }
        li { margin-bottom: 10px; font-size: 1.1em; }
        
        .tip-box { background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba; margin-top: 20px; font-size: 0.9em; }
        
        .btn { display: inline-block; margin-top: 25px; padding: 12px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 50px; font-weight: bold; transition: 0.2s; }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="card">
        <h1>🧹 Limpieza Realizada</h1>
        
        <ul>
            <?php foreach($mensajes as $msg): ?>
                <li><?= $msg ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="tip-box">
            <strong>💡 Nota Importante para tu Navegador:</strong><br>
            El servidor ya está limpio, pero tu navegador (Chrome, Edge, etc.) puede seguir guardando archivos viejos.
            <br><br>
            Si sigues viendo errores visuales, presiona estas teclas:
            <br>
            <strong>Ctrl + F5</strong> (Windows/Linux) o <strong>Cmd + Shift + R</strong> (Mac)
        </div>

        <a href="index.php" class="btn">Volver al Panel Admin</a>
    </div>
</body>
</html>