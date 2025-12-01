<?php
/**
 * admin/index.php - Panel Central de Gestión de Campañas (CON ACCIONES)
 * Responsivo para PC y Móvil.
 */
session_start();
// Asegurar que la conexión a la BD y la sesión de PHP estén activas
require_once 'auth.php';
require_once 'db_connect.php'; 

// 1. Verificación de Rol (CRÍTICO: Solo Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

// 2. LÓGICA DE GESTIÓN (IDLE O DESPUÉS DE UNA ACCIÓN)
$mostrar_reportes = isset($_GET['view']) && $_GET['view'] === 'reports';

// Consulta para obtener TODOS los banners para gestión
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.titulo, b.city_slugs, b.posicion, b.is_active, b.is_approved,
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS total_impresiones,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks
        FROM banners b
        LEFT JOIN banner_events be ON b.id = be.banner_id
        GROUP BY b.id, b.titulo, b.city_slugs, b.posicion, b.is_active, b.is_approved
        ORDER BY b.is_active DESC, b.id ASC
    ");
    $stmt->execute();
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("DB Error fetching dashboard data: " . $e->getMessage());
    $campanas = [];
    $error = "Error al cargar los datos de las campañas.";
}

// Manejo de mensajes de estado desde actions.php
$status_message = $_GET['msg'] ?? null;
$status_type = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Banners - Panel Central</title>
    <style>
        /* Estilos Base y Responsividad Móvil */
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* Encabezado y Acciones */
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.8em; }
        .actions-header { 
            display: flex; 
            flex-wrap: wrap; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
            gap: 10px;
        }
        .actions-header div { 
            display: flex; 
            flex-wrap: wrap;
            gap: 8px; 
        }
        
        /* Botones de Acción */
        .btn-action, .btn-new { 
            padding: 8px 12px; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: bold; 
            font-size: 0.9em; 
            transition: background-color 0.2s;
        }
        .btn-new { background-color: #2ecc71; color: white; }
        .btn-moderacion { background-color: #e74c3c; color: white; }
        .btn-gestion-users { background-color: #3498db; color: white; }

        /* Estilos de Tabla */
        .table-wrapper { 
            overflow-x: auto; /* CRÍTICO: Permite hacer scroll horizontal en móviles */
            width: 100%;
        }
        table { 
            width: 100%; 
            min-width: 800px; /* Asegura que la tabla no se colapse */
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            padding: 12px 15px; 
            border: 1px solid #ddd; 
            text-align: left; 
            font-size: 0.85em; 
        }
        th { background-color: #3498db; color: white; }

        /* Estilos de Estado */
        .active-status { background-color: #e6f7e9; color: #27ae60; font-weight: bold; }
        .inactive-status { background-color: #fcebeb; color: #c0392b; font-weight: bold; }
        .pending-status { background-color: #fff8e1; color: #f39c12; font-weight: bold; }
        
        /* Botones de Fila */
        .btn-toggle-off { background-color: #e74c3c; color: white; }
        .btn-toggle-on { background-color: #2ecc71; color: white; }
        .btn-edit { background-color: #3498db; color: white; }
        .btn-action-sm { padding: 5px 8px; text-decoration: none; border-radius: 3px; font-size: 0.8em; margin-right: 5px; }

        /* Mensajes */
        .status-message { padding: 10px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .success-msg { background-color: #d4edda; color: #155724; }
        .error-msg { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="actions-header">
            <h1>Gestión Central de Campañas ⚙️</h1>
            <div>
                <a href="user_management.php" class="btn-action btn-gestion-users">👥 Gestión Usuarios</a>
                <a href="moderation.php" class="btn-action btn-moderacion">🚨 Moderación (Aprobar)</a>
                <a href="reports.php" class="btn-action btn-gestion-users">Ver Reportes Detallados</a>
                <a href="form.php" class="btn-new">➕ Crear Nuevo Banner</a>
            </div>
        </div>

        <?php if ($status_message): ?>
            <div class="status-message <?= $status_type === 'success' ? 'success-msg' : 'error-msg' ?>">
                <?= htmlspecialchars(urldecode($status_message)) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
             <div class="error-msg status-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-wrapper">
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
                        <th>Aprobación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campanas)): ?>
                        <tr><td colspan="9" style="text-align:center;">No hay campañas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($campanas as $c): ?>
                        <?php 
                            $status_class = 'inactive-status';
                            $status_text = 'INACTIVO';
                            $approval_text = 'Pendiente';
                            
                            if ($c['is_approved']) {
                                $approval_text = 'Aprobado';
                                if ($c['is_active']) {
                                    $status_class = 'active-status';
                                    $status_text = 'ACTIVO';
                                } else {
                                    $status_class = 'inactive-status';
                                    $status_text = 'PAUSADO';
                                }
                            } else {
                                $status_class = 'pending-status';
                            }
                        ?>
                        <tr class="<?= $status_class ?>">
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['titulo']) ?></td>
                            <td><?= str_replace(',', ', ', htmlspecialchars($c['city_slugs'])) ?></td>
                            <td><?= ucfirst($c['posicion']) ?></td>
                            <td><?= number_format($c['total_impresiones'], 0, ',', '.') ?></td>
                            <td><?= number_format($c['total_clicks'], 0, ',', '.') ?></td>
                            <td><?= $status_text ?></td>
                            <td><?= $approval_text ?></td>
                            <td>
                                <a href="form.php?id=<?= $c['id'] ?>" class="btn-action-sm btn-edit">Editar</a>
                                <?php if ($c['is_active']): ?>
                                    <a href="actions.php?action=deactivate&id=<?= $c['id'] ?>" class="btn-action-sm btn-toggle-off">Desactivar</a>
                                <?php else: ?>
                                    <a href="actions.php?action=activate&id=<?= $c['id'] ?>" class="btn-action-sm btn-toggle-on">Activar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
