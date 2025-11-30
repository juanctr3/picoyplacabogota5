<?php
/**
 * admin/index.php - Panel Central de Gestión de Banners (CON ACCIONES)
 * Este archivo gestiona las acciones y luego llama al template de gestión.
 */

// 1. CONEXIÓN A LA BASE DE DATOS (Usando el módulo centralizado)
require_once 'db_connect.php'; 

// 2. LÓGICA DE GESTIÓN (IDLE O DESPUÉS DE UNA ACCIÓN)
$mostrar_reportes = isset($_GET['view']) && $_GET['view'] === 'reports';

// Consulta para obtener TODOS los banners para gestión
$stmt = $pdo->prepare("
    SELECT 
        b.id, b.titulo, b.city_slugs, b.posicion, b.is_active,
        COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS total_impresiones,
        COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks
    FROM banners b
    LEFT JOIN banner_events be ON b.id = be.banner_id
    GROUP BY b.id, b.titulo, b.city_slugs, b.posicion, b.is_active
    ORDER BY b.is_active DESC, b.id ASC
");
$stmt->execute();
$campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejo de mensajes de estado desde actions.php
$status_message = $_GET['msg'] ?? null;
$status_type = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Banners - Panel Central</title>
    <style>
        /* Estilos base */
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #3498db; color: white; }
        
        /* Estilos de gestión */
        .actions-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-new { background-color: #2ecc71; color: white; padding: 10px 15px; border: none; border-radius: 4px; text-decoration: none; }
        .active-status { background-color: #e6f7e9; color: #27ae60; font-weight: bold; }
        .inactive-status { background-color: #fcebeb; color: #c0392b; font-weight: bold; }
        .btn-action { padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.9em; margin-right: 5px; }
        .btn-toggle-on { background-color: #2ecc71; color: white; }
        .btn-toggle-off { background-color: #e74c3c; color: white; }
        .status-message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Estilos específicos para la vista de reportes */
        .reports-link { background-color: #3498db; color: white; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="actions-header">
            <h1><?= $mostrar_reportes ? 'Reportes de Publicidad' : 'Gestión Central de Campañas' ?> ⚙️</h1>
            <div>
                <?php if ($mostrar_reportes): ?>
                    <a href="index.php" class="btn-action reports-link">← Volver a Gestión</a>
                <?php else: ?>
                    <a href="index.php?view=reports" class="btn-action reports-link">Ver Reportes Detallados</a>
                    <a href="form.php" class="btn-new">➕ Crear Nuevo Banner</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($status_message): ?>
            <div class="status-message <?= $status_type === 'success' ? 'success-msg' : 'error-msg' ?>">
                <?= htmlspecialchars(urldecode($status_message)) ?>
            </div>
        <?php endif; ?>

        <?php if ($mostrar_reportes): ?>
            <?php include 'reports.php'; ?>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Ciudades</th>
                        <th>Posición</th>
                        <th>Impresiones</th>
                        <th>Clicks</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campanas as $c): ?>
                    <?php $status_class = $c['is_active'] ? 'active-status' : 'inactive-status'; ?>
                    <tr class="<?= $status_class ?>">
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['titulo']) ?></td>
                        <td><?= htmlspecialchars($c['city_slugs']) ?></td>
                        <td><?= ucfirst($c['posicion']) ?></td>
                        <td><?= number_format($c['total_impresiones'], 0, ',', '.') ?></td>
                        <td><?= number_format($c['total_clicks'], 0, ',', '.') ?></td>
                        <td><?= $c['is_active'] ? 'ACTIVO' : 'INACTIVO' ?></td>
                        <td>
                            <a href="form.php?id=<?= $c['id'] ?>" class="btn-action">Editar</a>
                            <?php if ($c['is_active']): ?>
                                <a href="actions.php?action=deactivate&id=<?= $c['id'] ?>" class="btn-action btn-toggle-off">Desactivar</a>
                            <?php else: ?>
                                <a href="actions.php?action=activate&id=<?= $c['id'] ?>" class="btn-action btn-toggle-on">Activar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
