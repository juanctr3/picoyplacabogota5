<?php
/**
 * admin/moderation.php - Panel de Moderación y Aprobación de Banners.
 * Muestra las campañas creadas por anunciantes que requieren revisión.
 */
session_start();
require_once 'db_connect.php'; 

// 1. Verificación de Rol (CRÍTICO: Solo Admin puede acceder)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$message = '';
$status = $_GET['status'] ?? null;
$msg = $_GET['msg'] ?? null;

if ($msg) {
    $message = '<div class="status-message ' . ($status === 'success' ? 'success-msg' : 'error-msg') . '">' . htmlspecialchars(urldecode($msg)) . '</div>';
}

try {
    // Consulta SQL para obtener banners que NO han sido aprobados
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.titulo, b.descripcion, b.city_slugs, b.offer_cpc, b.offer_cpm, b.posicion, b.user_id, u.email 
        FROM banners b
        JOIN users u ON b.user_id = u.id
        WHERE b.is_approved = FALSE
        ORDER BY b.id ASC
    ");
    $stmt->execute();
    $campanas_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("DB Error fetching pending campaigns: " . $e->getMessage());
    $message = '<div class="error-msg">Error al cargar la lista de moderación.</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Moderación de Anuncios - Admin</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; font-size: 0.9em; }
        th { background-color: #e74c3c; color: white; }
        .btn-action { padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9em; margin-right: 5px; }
        .btn-approve { background-color: #2ecc71; color: white; }
        .btn-reject { background-color: #f39c12; color: white; }
        .status-message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .admin-link { color: #3498db; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Banners Pendientes de Aprobación 🚨</h1>
        <p><a href="index.php" class="admin-link">← Volver al Panel Admin</a></p>

        <?= $message ?>

        <?php if (empty($campanas_pendientes)): ?>
            <div class="success-msg" style="text-align: center;">🎉 No hay banners pendientes de revisión. ¡Todo al día!</div>
        <?php else: ?>
            <p>Se encontraron **<?= count($campanas_pendientes) ?>** campañas nuevas que requieren tu aprobación antes de entrar en rotación.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Anunciante</th>
                        <th>Título / Contenido</th>
                        <th>Ciudades</th>
                        <th>Posición</th>
                        <th>Oferta (CPC/CPM)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campanas_pendientes as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['email']) ?> (ID: <?= $c['user_id'] ?>)</td>
                        <td>
                            <strong><?= htmlspecialchars($c['titulo']) ?></strong><br>
                            <?= htmlspecialchars($c['descripcion']) ?>
                        </td>
                        <td><?= str_replace(',', ', ', htmlspecialchars($c['city_slugs'])) ?></td>
                        <td><?= ucfirst($c['posicion']) ?></td>
                        <td>$<?= number_format($c['offer_cpc'], 2) ?>/$<?= number_format($c['offer_cpm'], 2) ?></td>
                        
                        <td>
                            <a href="moderation_actions.php?action=approve&id=<?= $c['id'] ?>" class="btn-action btn-approve">APROBAR</a>
                            <a href="moderation_actions.php?action=reject&id=<?= $c['id'] ?>" class="btn-action btn-reject">RECHAZAR</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>