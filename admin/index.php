<?php
/**
 * admin/index.php - Gestión Central de Campañas (Responsivo V2)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

// Consulta Banners
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.titulo, b.city_slugs, b.posicion, b.is_active, b.is_approved,
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS total_impresiones,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks
        FROM banners b
        LEFT JOIN banner_events be ON b.id = be.banner_id
        GROUP BY b.id
        ORDER BY b.is_active DESC, b.id ASC
    ");
    $stmt->execute();
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $campanas = []; }

$status_message = $_GET['msg'] ?? null;
$status_type = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Admin Banners</title>
    <style>
        /* --- ESTILOS BASE (PC) --- */
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        h1 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #3498db; padding-bottom: 10px; font-size: 1.8em; }
        
        /* Botonera Superior */
        .actions-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .btn-action { padding: 10px 15px; text-decoration: none; border-radius: 6px; font-weight: 600; color: white; font-size: 0.9em; transition: 0.2s; border:none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
        .btn-blue { background: #3498db; }
        .btn-green { background: #2ecc71; }
        .btn-red { background: #e74c3c; }
        .btn-purple { background: #8e44ad; }
        .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Mensajes */
        .status-msg { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* --- TABLA RESPONSIVA (La Magia) --- */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #3498db; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #555; vertical-align: middle; }
        
        /* Botones dentro de la tabla */
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-sm { padding: 6px 10px; font-size: 0.85em; }

        /* --- VISTA MÓVIL (Card View) --- */
        @media (max-width: 768px) {
            /* Ocultar cabeceras de tabla */
            thead { display: none; }
            
            /* Convertir filas en tarjetas */
            tr { display: block; background: #fff; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 15px; }
            
            /* Convertir celdas en renglones */
            td { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 10px 0; text-align: right; }
            td:last-child { border-bottom: none; flex-direction: column; align-items: stretch; gap: 10px; margin-top: 10px; }

            /* Poner etiquetas (Labels) usando el atributo data-label */
            td::before { content: attr(data-label); font-weight: bold; color: #2c3e50; text-align: left; margin-right: 15px; }
            
            /* Ajustar botones en móvil */
            .action-buttons { justify-content: space-between; width: 100%; }
            .btn-sm { flex: 1; text-align: center; justify-content: center; padding: 10px; }
            
            /* Ajuste contenedor general */
            body { padding: 10px; }
            .container { padding: 15px; }
            .actions-header { flex-direction: column; align-items: stretch; }
            .btn-group { flex-direction: column; }
            .btn-action { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="actions-header">
            <h1>Admin Panel ⚙️</h1>
            <div class="btn-group">
                <a href="user_management.php" class="btn-action btn-blue">👥 Usuarios</a>
                <a href="moderation.php" class="btn-action btn-red">🚨 Moderación</a>
                <a href="reports.php" class="btn-action btn-blue">📊 Reportes</a>
                <a href="settings.php" class="btn-action btn-purple">⚙️ Configuración</a>
                <a href="form.php" class="btn-action btn-green">➕ Crear</a>
				<a href="../user/logout.php" class="btn-action btn-red" style="margin-left:auto;">🚪 Salir</a>
				<a href="tickets.php" class="btn-action btn-purple">💬 Tickets Soporte</a>
    			<a href="clear_cache.php" class="btn-action btn-red" style="background:#e67e22;">🧹 Limpiar Caché</a>
            </div>
        </div>

        <?php if ($status_message): ?>
            <div class="status-msg <?= $status_type === 'success' ? 'msg-success' : 'msg-error' ?>">
                <?= htmlspecialchars(urldecode($status_message)) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Ciudades</th>
                    <th>Impresiones</th>
                    <th>Clicks</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campanas)): ?>
                    <tr><td colspan="7" style="text-align:center;">Sin datos.</td></tr>
                <?php else: ?>
                    <?php foreach ($campanas as $c): ?>
                    <tr>
                        <td data-label="ID">#<?= $c['id'] ?></td>
                        <td data-label="Título"><strong><?= htmlspecialchars($c['titulo']) ?></strong></td>
                        <td data-label="Ciudades"><small><?= str_replace(',', ', ', htmlspecialchars($c['city_slugs'])) ?></small></td>
                        <td data-label="Impresiones"><?= number_format($c['total_impresiones']) ?></td>
                        <td data-label="Clicks"><?= number_format($c['total_clicks']) ?></td>
                        <td data-label="Estado">
                            <?php if($c['is_active']): ?>
                                <span style="color:green; font-weight:bold;">● Activo</span>
                            <?php else: ?>
                                <span style="color:red; font-weight:bold;">● Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acciones">
                            <div class="action-buttons">
                                <a href="form.php?id=<?= $c['id'] ?>" class="btn-action btn-blue btn-sm">✏️ Editar</a>
                                <?php if ($c['is_active']): ?>
                                    <a href="actions.php?action=deactivate&id=<?= $c['id'] ?>" class="btn-action btn-red btn-sm">⏸ Pausar</a>
                                <?php else: ?>
                                    <a href="actions.php?action=activate&id=<?= $c['id'] ?>" class="btn-action btn-green btn-sm">▶ Activar</a>
                                <?php endif; ?>
                                <a href="actions.php?action=delete&id=<?= $c['id'] ?>" class="btn-action btn-red btn-sm" onclick="return confirm('¿Eliminar?');">🗑️</a>
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