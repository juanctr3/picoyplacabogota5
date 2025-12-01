<?php
/**
 * admin/reports.php - Panel de Reportes de Publicidad (Consulta Completa y Corregida)
 * Arregla el error SQL 500 al garantizar que todas las columnas de SELECT estén en GROUP BY.
 */

// Se asume que db_connect.php ya existe y establece $pdo
session_start(); 
require_once 'db_connect.php'; 

// 1. Verificación de Rol (solo Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$reportes = [];
$message = '';

try {
    // Consulta SQL para obtener los datos de reportes
    $stmt = $pdo->prepare("
        SELECT 
            b.id AS banner_id,
            b.titulo,
            b.max_impresiones,
            b.max_clicks,
            b.offer_cpc,
            b.offer_cpm,
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS total_impresiones,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks
        FROM banners b
        LEFT JOIN banner_events be ON b.id = be.banner_id
        GROUP BY 
            b.id, b.titulo, b.max_impresiones, b.max_clicks, b.offer_cpc, b.offer_cpm 
        ORDER BY b.id ASC
    ");

    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la consulta falla, el error 500 se detiene aquí y se muestra el mensaje al Admin
    $message = '<div class="error-msg" style="text-align: center;">Error SQL: ' . $e->getMessage() . '</div>';
    error_log("Reportes SQL Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Reportes de Banners</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; font-size: 0.9em; }
        th { background-color: #3498db; color: white; }
        .success { background-color: #e6f7e9; }
        .warning { background-color: #fff8e1; }
        .error { background-color: #fcebeb; }
        .actions-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-action { padding: 8px 12px; background-color: #2c3e50; color: white; border-radius: 4px; text-decoration: none; }
        .error-msg { color: #c0392b; background-color: #fcebeb; border: 1px solid #f8d7da; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="actions-header">
            <h1>Reportes de Publicidad (Tiempo Real) 📈</h1>
            <a href="index.php" class="btn-action">← Volver a Gestión Central</a>
        </div>
        <p>Aquí puedes monitorear el rendimiento de tus campañas activas y el uso de los límites de Clicks/Impresiones.</p>

        <?= $message ?>
        
        <?php if (!$message): // Solo mostrar la tabla si no hay error crítico ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título del Banner</th>
                    <th>Impresiones (Vistas)</th>
                    <th>Clicks (Acciones)</th>
                    <th>Oferta CPC/CPM</th>
                    <th>Límite Máx. (V/C)</th>
                    <th>Ratio Click (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportes as $banner): ?>
                <?php
                    $impresiones = (int)$banner['total_impresiones'];
                    $clicks = (int)$banner['total_clicks'];
                    $maxImp = (int)$banner['max_impresiones'];
                    $maxClick = (int)$banner['max_clicks'];
                    
                    // Cálculo de Ratio Click-Through (CTR)
                    $ctr = ($impresiones > 0) ? round(($clicks / $impresiones) * 100, 2) : 0;

                    // Clases para resaltar si los límites están cerca
                    $rowClass = 'success';
                    if ($clicks >= $maxClick * 0.9 || $impresiones >= $maxImp * 0.9) {
                        $rowClass = 'error'; // 90% o más
                    } elseif ($clicks >= $maxClick * 0.7 || $impresiones >= $maxImp * 0.7) {
                        $rowClass = 'warning'; // 70% o más
                    }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= $banner['banner_id'] ?></td>
                    <td><?= htmlspecialchars($banner['titulo']) ?></td>
                    <td><?= number_format($impresiones, 0, ',', '.') ?></td>
                    <td><?= number_format($clicks, 0, ',', '.') ?></td>
                    <td>$<?= number_format($banner['offer_cpc'], 2) ?> / $<?= number_format($banner['offer_cpm'], 2) ?></td>
                    <td>
                        V: <?= number_format($maxImp, 0, ',', '.') ?><br>
                        C: <?= number_format($maxClick, 0, ',', '.') ?>
                    </td>
                    <td><?= $ctr ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>