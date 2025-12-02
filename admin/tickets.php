<?php
/**
 * admin/tickets.php - Gestión de Soporte (Admin)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

// Obtener tickets con info del usuario
$stmt = $pdo->query("
    SELECT t.*, u.email, u.full_name 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY field(t.status, 'abierto', 'respondido', 'cerrado'), t.updated_at DESC
");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Soporte Técnico</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #2c3e50; border-bottom: 2px solid #e67e22; padding-bottom: 10px; }
        
        .btn-back { display: inline-block; padding: 8px 15px; background: #34495e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 20px; }
        .btn-view { background: #3498db; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #e67e22; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #555; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
        .st-abierto { background: #d1fae5; color: #065f46; } /* Verde suave */
        .st-respondido { background: #fff3cd; color: #856404; } /* Amarillo */
        .st-cerrado { background: #e2e3e5; color: #383d41; } /* Gris */
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Tickets de Soporte 💬</h1>
            <a href="index.php" class="btn-back">← Volver al Panel</a>
        </div>

        <table>
            <thead><tr><th>ID</th><th>Usuario</th><th>Asunto</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($t['full_name'] ?: 'Usuario') ?></strong><br>
                        <small><?= htmlspecialchars($t['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($t['subject']) ?></td>
                    <td><span class="badge st-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                    <td><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                    <td><a href="ticket_view.php?id=<?= $t['id'] ?>" class="btn-view">Gestionar</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>