<?php
/**
 * user/dashboard.php - Panel Principal de Anunciantes
 * Muestra el saldo y enlaces de gestión.
 */
session_start();
require_once 'db_connect.php'; 

// Comprobar autenticación (si no está logueado, redirigir)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'];
$balance = 0.00;
$message = '';

try {
    // Obtener el saldo actual del usuario
    $stmt = $pdo->prepare("SELECT account_balance FROM users WHERE id = :userId");
    $stmt->execute([':userId' => $userId]);
    $balance = $stmt->fetchColumn();
    
    // Formato de moneda (ajusta si necesitas un formato más específico)
    $formattedBalance = number_format($balance, 2, ',', '.');
    
    // Mensaje de éxito tras una recarga
    if (isset($_GET['recharged']) && $_GET['recharged'] === 'true') {
        $message = '<div class="success-msg">✅ ¡Recarga de cuenta exitosa! Saldo actualizado.</div>';
    }

} catch (PDOException $e) {
    error_log("DB Error fetching dashboard data: " . $e->getMessage());
    $message = '<div class="error-msg">Error al cargar los datos de la cuenta.</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Anunciante</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #34495e; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        .header-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .balance-box { background-color: #2ecc71; color: white; padding: 15px 25px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .balance-box h2 { margin: 0; font-size: 1.2em; }
        .balance-box p { margin: 5px 0 0; font-size: 2.5em; font-weight: bold; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .action-card { background-color: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; text-decoration: none; transition: background-color 0.3s; }
        .action-card:hover { background-color: #2980b9; }
        .action-card h3 { margin: 0 0 10px; font-size: 1.5em; }
        .action-card p { margin: 0; }
        .logout-link { text-align: right; margin-top: 20px; }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-info">
            <h1>Panel de Control</h1>
            <div class="balance-box">
                <h2>Saldo Actual</h2>
                <p>$<?= $formattedBalance ?></p>
            </div>
        </div>
        
        <p>Bienvenido, **<?= htmlspecialchars($userEmail) ?>**.</p>
        
        <?= $message ?>
        
        <div class="actions-grid">
            <a href="deposit.php" class="action-card">
                <h3>💰 Recargar Cuenta</h3>
                <p>Añade fondos para mantener tus campañas activas.</p>
            </a>
            
            <a href="campaigns.php" class="action-card" style="background-color: #e67e22;">
                <h3>📊 Ver Campañas</h3>
                <p>Gestiona y monitorea tus anuncios (Clics, Impresiones, Costo).</p>
            </a>
            
            <a href="create_ad.php" class="action-card" style="background-color: #9b59b6;">
                <h3>✏️ Crear Nuevo Anuncio</h3>
                <p>Configura una nueva campaña de banner.</p>
            </a>
        </div>

        <div class="logout-link">
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>