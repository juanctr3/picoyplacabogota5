<?php
/**
 * user/dashboard.php - Panel Principal
 * Corrección: Codificación UTF-8 forzada para tildes y emojis.
 */
// 1. Forzar cabecera UTF-8 desde el servidor
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();
require_once 'db_connect.php'; 

// Seguridad
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php"); exit;
}

$userId = $_SESSION['user_id'];

// Inicializar variables para evitar errores si la BD falla
$user = ['full_name' => 'Usuario', 'account_balance' => 0, 'email' => ''];
$stats = ['total' => 0, 'active' => 0];

try {
    // Datos del usuario
    $stmt = $pdo->prepare("SELECT full_name, account_balance, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData) { $user = $userData; }
    
    // Resumen de campañas
    $stmtBanners = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) as active FROM banners WHERE user_id = :id");
    $stmtBanners->execute([':id' => $userId]);
    $statsData = $stmtBanners->fetch(PDO::FETCH_ASSOC);
    if ($statsData) { $stats = $statsData; }

} catch (Exception $e) {
    error_log("Error Dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anunciante</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6f8; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .welcome h1 { margin: 0; color: #2c3e50; font-size: 1.8em; }
        .welcome p { margin: 5px 0 0; color: #7f8c8d; }
        
        .actions-top { display: flex; gap: 10px; }
        .btn-top { padding: 8px 15px; border-radius: 6px; text-decoration: none; color: white; font-weight: bold; font-size: 0.9em; transition: 0.2s; }
        .btn-profile { background: #34495e; }
        .btn-logout { background: #c0392b; }
        .btn-top:hover { opacity: 0.9; transform: translateY(-2px); }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid transparent; }
        .stat-money { border-bottom-color: #27ae60; }
        .stat-ads { border-bottom-color: #3498db; }
        
        .stat-value { display: block; font-size: 2.2em; font-weight: 800; color: #2c3e50; margin: 10px 0; }
        .stat-label { color: #95a5a6; font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .stat-link { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.9em; display: inline-block; margin-top: 5px; }
        
        /* Action Cards */
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .action-card { background: white; padding: 25px; border-radius: 12px; text-align: center; text-decoration: none; color: #2c3e50; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #eee; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 160px; }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); border-color: #3498db; }
        
        .icon { font-size: 2.5em; margin-bottom: 15px; display: block; }
        .card-title { font-weight: 700; font-size: 1.1em; }
        .card-desc { font-size: 0.9em; color: #7f8c8d; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="welcome">
                <h1>Hola, <?= htmlspecialchars($user['full_name'] ?: 'Anunciante') ?></h1>
                <p><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div class="actions-top">
                <a href="profile.php" class="btn-top btn-profile">? Mi Perfil</a>
                <a href="logout.php" class="btn-top btn-logout">Salir</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-money">
                <span class="stat-label">Saldo Disponible</span>
                <span class="stat-value" style="color: #27ae60;">$<?= number_format($user['account_balance'], 0, ',', '.') ?></span>
                <a href="deposit.php" class="stat-link">Recargar Saldo ?</a>
            </div>
            <div class="stat-card stat-ads">
                <span class="stat-label">Campañas Activas</span>
                <span class="stat-value"><?= $stats['active'] ?></span>
                <a href="campaigns.php" class="stat-link">Ver todas ?</a>
            </div>
        </div>

        <div class="action-grid">
            <a href="create_ad.php" class="action-card">
                <span class="icon">?</span>
                <span class="card-title">Crear Anuncio</span>
                <span class="card-desc">Lanza una nueva campaña</span>
            </a>
            <a href="campaigns.php" class="action-card">
                <span class="icon">?</span>
                <span class="card-title">Mis Campañas</span>
                <span class="card-desc">Estadísticas y gestión</span>
            </a>
            <a href="tickets.php" class="action-card">
                <span class="icon">?</span>
                <span class="card-title">Soporte</span>
                <span class="card-desc">Ayuda y Tickets</span>
            </a>
            <a href="profile.php" class="action-card">
                <span class="icon">??</span>
                <span class="card-title">Configuración</span>
                <span class="card-desc">Datos y Facturación</span>
            </a>
        </div>
    </div>
</body>
</html>