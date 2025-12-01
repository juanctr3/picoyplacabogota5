<?php
/**
 * user/campaigns.php - Mis Campañas (Responsivo V2)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php"); exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS total_impresiones,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks,
            COALESCE(SUM(be.cost_applied), 0.00) AS total_spent
        FROM banners b
        LEFT JOIN banner_events be ON b.id = be.banner_id
        WHERE b.user_id = :userId
        GROUP BY b.id
        ORDER BY b.id DESC
    ");
    $stmt->execute([':userId' => $userId]);
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $campanas = []; }

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Campañas</title>
    <style>
        /* Reutilizamos la base de estilos del admin por consistencia */
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        h1 { color: #2c3e50; border-bottom: 2px solid #f39c12; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.8em; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        
        .btn { padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; color: white; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; font-size: 0.9em; }
        .btn-main { background: #f39c12; }
        .btn-sec { background: #34495e; }
        .btn:hover { opacity: 0.9; transform: scale(1.02); }

        /* Tabla Responsiva */
        table { width: 100%; border-collapse: collapse; }
        th { background: #34495e; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #555; }
        
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
        .s-active { background: #d4edda; color: #155724; }
        .s-paused { background: #fff3cd; color: #856404; }
        .s-pending { background: #e2e3e5; color: #383d41; }

        .btn-row { padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; font-size: 0.85em; margin-right: 3px; }
        .br-edit { background: #3498db; }
        .br-pause { background: #f1c40f; }
        .br-play { background: #2ecc71; }
        .br-del { background: #e74c3c; }

        /* --- MÓVIL --- */
        @media (max-width: 768px) {
            thead { display: none; }
            tr { display: block; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            td { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 8px 0; text-align: right; }
            td:last-child { border: none; justify-content: center; gap: 10px; margin-top: 10px; }
            td::before { content: attr(data-label); font-weight: bold; color: #333; }
            
            .header-actions { flex-direction: column; align-items: stretch; }
            .btn { justify-content: center; width: 100%; box-sizing: border-box; }
            
            .action-group { display: flex; width: 100%; gap: 5px; }
            .btn-row { flex: 1; text-align: center; padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>Mis Campañas 📢</h1>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="dashboard.php" class="btn btn-sec">← Volver</a>
                <a href="create_ad.php" class="btn btn-main">➕ Nueva Campaña</a>
            </div>
        </div>

        <?php if($msg): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; border-radius:6px; margin-bottom:20px; text-align:center;">
                <?= htmlspecialchars(urldecode($msg)) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Campaña</th>
                    <th>Estado</th>
                    <th>Vistas</th>
                    <th>Clicks</th>
                    <th>Inversión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campanas)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px;">Aún no tienes campañas. ¡Crea la primera!</td></tr>
                <?php else: ?>
                    <?php foreach ($campanas as $c): ?>
                    <tr>
                        <td data-label="Campaña"><strong><?= htmlspecialchars($c['titulo']) ?></strong></td>
                        <td data-label="Estado">
                            <?php if (!$c['is_approved']): ?>
                                <span class="status-badge s-pending">En Revisión</span>
                            <?php elseif ($c['is_active']): ?>
                                <span class="status-badge s-active">Activa</span>
                            <?php else: ?>
                                <span class="status-badge s-paused">Pausada</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Vistas"><?= number_format($c['total_impresiones']) ?></td>
                        <td data-label="Clicks"><?= number_format($c['total_clicks']) ?></td>
                        <td data-label="Inversión">$<?= number_format($c['total_spent'], 0) ?></td>
                        <td data-label="Acciones">
                            <div class="action-group">
                                <a href="create_ad.php?id=<?= $c['id'] ?>" class="btn-row br-edit">✏️</a>
                                <?php if ($c['is_active']): ?>
                                    <a href="user_actions.php?action=pause&id=<?= $c['id'] ?>" class="btn-row br-pause">⏸</a>
                                <?php elseif ($c['is_approved']): ?>
                                    <a href="user_actions.php?action=activate&id=<?= $c['id'] ?>" class="btn-row br-play">▶</a>
                                <?php endif; ?>
                                <a href="user_actions.php?action=delete&id=<?= $c['id'] ?>" class="btn-row br-del" onclick="return confirm('¿Eliminar campaña?');">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>