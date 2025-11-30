<?php
/**
 * admin/reports.php - Vista de Reportes (Mostrada desde admin/index.php)
 * Nota: El archivo db_connect.php debe ser incluido antes de este script.
 */

// Este script ya no incluye la conexión ni el HTML completo.
// Se asume que \$pdo está disponible desde la inclusión de db_connect.php.

$reportes = [];

try {
    // Consulta SQL para agrupar y sumar los eventos
    $stmt = $pdo->prepare("
        SELECT 
            b.id AS banner_id,
            b.titulo,
            b.max_impresiones,
            b.max_clicks,
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS total_impresiones,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS total_clicks
        FROM banners b
        LEFT JOIN banner_events be ON b.id = be.banner_id
        GROUP BY b.id, b.titulo, b.max_impresiones, b.max_clicks
        ORDER BY b.id ASC
    ");

    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Manejo de errores de consulta
    die("Error de consulta al generar reportes: " . $e->getMessage());
}
?>

<div class="container">
    <div class="actions-header">
        <h1>Reportes de Publicidad (Tiempo Real) 📈</h1>
        <a href="index.php" class="btn-action">← Volver a Gestión Central</a>
    </div>
    <p>Aquí puedes monitorear el rendimiento de tus campañas activas y el uso de los límites de Clicks/Impresiones.</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Título del Banner</th>
                <th>Impresiones (Vistas)</th>
                <th>Clicks (Acciones)</th>
                <th>Límite Máx.</th>
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
                
                $ctr = ($impresiones > 0) ? round(($clicks / $impresiones) * 100, 2) : 0;

                $rowClass = 'success';
                if ($clicks >= $maxClick * 0.9 || $impresiones >= $maxImp * 0.9) {
                    $rowClass = 'error'; 
                } elseif ($clicks >= $maxClick * 0.7 || $impresiones >= $maxImp * 0.7) {
                    $rowClass = 'warning'; 
                }
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= $banner['banner_id'] ?></td>
                <td><?= htmlspecialchars($banner['titulo']) ?></td>
                <td><?= number_format($impresiones, 0, ',', '.') ?></td>
                <td><?= number_format($clicks, 0, ',', '.') ?></td>
                <td>
                    V: <?= number_format($maxImp, 0, ',', '.') ?><br>
                    C: <?= number_format($maxClick, 0, ',', '.') ?>
                </td>
                <td><?= $ctr ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
