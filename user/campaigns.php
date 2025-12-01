<?php
/**
 * user/campaigns.php - Listado y Gestión de Campañas del Anunciante
 * Muestra solo las campañas vinculadas al usuario logueado.
 */
session_start();
require_once 'db_connect.php'; 

// Comprobar autenticación y rol (solo anunciantes)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';

// Consulta para obtener SOLO las campañas del usuario logueado
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.titulo, b.city_slugs, b.posicion, b.is_active, b.is_approved, b.offer_cpc, b.offer_cpm,
            COALESCE(SUM(CASE WHEN be.event_type = 'impression' THEN 1 ELSE 0 END), 0) AS total_impresiones,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks,
            COALESCE(SUM(be.cost_applied), 0.00) AS total_spent
        FROM banners b
        LEFT JOIN banner_events be ON b.id = be.banner_id
        WHERE b.user_id = :userId
        GROUP BY b.id, b.titulo, b.city_slugs, b.posicion, b.is_active, b.is_approved, b.offer_cpc, b.offer_cpm
        ORDER BY b.is_active DESC, b.id ASC
    ");
    $stmt->execute([':userId' => $userId]);
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Manejo de mensajes de estado (Ej: Tras pausar/activar)
    $status_message = $_GET['msg'] ?? null;
    if ($status_message) {
        $message = '<div class="success-msg">' . htmlspecialchars(urldecode($status_message)) . '</div>';
    }

} catch (PDOException $e) {
    error_log("DB Error fetching user campaigns: " . $e->getMessage());
    $message = '<div class="error-msg">Error al cargar el listado de campañas.</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Campañas - Panel de Anunciante</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #34495e; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; font-size: 0.9em; }
        th { background-color: #3498db; color: white; }
        
        /* Estilos de estado y acción */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-new { background-color: #2ecc71; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .status-msg { padding: 10px; border-radius: 4px; margin-bottom: 20px; background-color: #d4edda; color: #155724; }
        .status-badge { padding: 5px 10px; border-radius: 3px; font-weight: bold; font-size: 0.8em; }
        .active { background-color: #2ecc71; color: white; }
        .inactive { background-color: #e74c3c; color: white; }
        .pending { background-color: #f1c40f; color: #333; }
        .btn-action-pause { background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.9em; margin-right: 5px; }
        .btn-action-start { background-color: #2ecc71; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.9em; margin-right: 5px; }
        .btn-action-edit { background-color: #3498db; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.9em; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>Mis Campañas de Publicidad</h1>
            <div>
                <a href="dashboard.php" class="btn-action-edit">← Dashboard</a>
                <a href="create_ad.php" class="btn-new">➕ Crear Nuevo Anuncio</a>
            </div>
        </div>
        
        <?= $message ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Ciudades</th>
                    <th>Oferta (CPC/CPM)</th>
                    <th>Estado</th>
                    <th>Aprobación</th>
                    <th>Impresiones</th>
                    <th>Clicks</th>
                    <th>Gasto Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campanas)): ?>
                    <tr><td colspan="10" style="text-align:center;">Aún no tienes campañas. ¡Crea una ahora!</td></tr>
                <?php else: ?>
                    <?php foreach ($campanas as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['titulo']) ?></td>
                        <td><?= str_replace(',', ', ', htmlspecialchars($c['city_slugs'])) ?></td>
                        <td>$<?= number_format($c['offer_cpc'], 2) ?>/$<?= number_format($c['offer_cpm'], 2) ?></td>
                        
                        <td>
                            <?php if (!$c['is_approved']): ?>
                                <span class="status-badge pending">PENDIENTE</span>
                            <?php elseif ($c['is_active']): ?>
                                <span class="status-badge active">ACTIVO</span>
                            <?php elseif (!$c['is_active']): ?>
                                <span class="status-badge inactive">PAUSADO</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $c['is_approved'] ? 'Aprobado' : 'Pendiente' ?>
                        </td>

                        <td><?= number_format($c['total_impresiones'], 0, ',', '.') ?></td>
                        <td><?= number_format($c['total_clicks'], 0, ',', '.') ?></td>
                        <td>$<?= number_format($c['total_spent'], 2, ',', '.') ?></td>
                        
                        <td>
                            <a href="create_ad.php?id=<?= $c['id'] ?>" class="btn-action-edit">Editar</a>
                            <?php if ($c['is_active']): ?>
                                <a href="user_actions.php?action=pause&id=<?= $c['id'] ?>" class="btn-action-pause">Pausar</a>
                            <?php elseif ($c['is_approved']): ?>
                                <a href="user_actions.php?action=activate&id=<?= $c['id'] ?>" class="btn-action-start">Activar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>