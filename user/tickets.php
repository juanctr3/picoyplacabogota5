<?php
/**
 * user/tickets.php - Listado de Tickets (UTF-8 Corregido)
 */
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];

// Obtener tickets
$tickets = [];
$error = '';
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = :id ORDER BY updated_at DESC");
    $stmt->execute([':id' => $userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar tickets: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte Técnico</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e67e22; padding-bottom: 15px; margin-bottom: 25px; flex-wrap: wrap; gap: 10px; }
        h1 { margin: 0; color: #2c3e50; font-size: 1.6em; }
        
        .btn { padding: 10px 20px; border-radius: 6px; text-decoration: none; color: white; font-weight: bold; font-size: 0.9em; transition: 0.2s; display: inline-block; }
        .btn-new { background: #e67e22; }
        .btn-back { background: #34495e; }
        .btn-view { background: #3498db; padding: 6px 12px; font-size: 0.85em; border-radius: 4px; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        
        /* Tabla Desktop */
        table { width: 100%; border-collapse: collapse; }
        th { background: #34495e; color: white; padding: 12px; text-align: left; border-radius: 4px 4px 0 0; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
        .st-abierto { background: #d1fae5; color: #065f46; }
        .st-respondido { background: #fef3c7; color: #92400e; }
        .st-cerrado { background: #f3f4f6; color: #374151; }
        
        /* Vista Móvil (Cards) */
        @media (max-width: 768px) {
            thead { display: none; }
            tr { display: block; background: #fff; border: 1px solid #ddd; margin-bottom: 15px; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
            td { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f9f9f9; padding: 8px 0; text-align: right; }
            td:last-child { border: none; justify-content: center; padding-top: 15px; }
            td::before { content: attr(data-label); font-weight: bold; color: #2c3e50; text-align: left; margin-right: 10px; }
            .header { flex-direction: column; align-items: stretch; text-align: center; }
            .btn { display: block; text-align: center; }
        }
        
        .empty { text-align: center; padding: 40px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mis Tickets 💬</h1>
            <div style="display:flex; gap:10px;">
                <a href="dashboard.php" class="btn btn-back">← Volver</a>
                <a href="ticket_create.php" class="btn btn-new">➕ Nuevo Ticket</a>
            </div>
        </div>

        <?php if ($error): ?>
            <p style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:6px;"><?= $error ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Actualizado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="5"><div class="empty">No tienes tickets de soporte.<br>Si necesitas ayuda, crea uno nuevo.</div></td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td data-label="ID">#<?= $t['id'] ?></td>
                        <td data-label="Asunto"><strong><?= htmlspecialchars($t['subject']) ?></strong></td>
                        <td data-label="Estado">
                            <?php 
                                $class = 'st-cerrado';
                                if($t['status'] == 'abierto') $class = 'st-abierto';
                                if($t['status'] == 'respondido') $class = 'st-respondido';
                            ?>
                            <span class="badge <?= $class ?>"><?= ucfirst($t['status']) ?></span>
                        </td>
                        <td data-label="Actualizado"><?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></td>
                        <td data-label="Acción">
                            <a href="ticket_view.php?id=<?= $t['id'] ?>" class="btn btn-view">Ver Chat</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>