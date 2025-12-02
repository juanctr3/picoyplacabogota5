<?php
/**
 * admin/user_management.php - Gestión de Usuarios (Responsivo V2)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

// Consulta Usuarios
try {
    $stmt = $pdo->query("
        SELECT u.*, COALESCE(SUM(be.cost_applied), 0) as spent 
        FROM users u 
        LEFT JOIN banners b ON u.id = b.user_id 
        LEFT JOIN banner_events be ON b.id = be.banner_id 
        WHERE u.role='advertiser' 
        GROUP BY u.id 
        ORDER BY u.id DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $users = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Usuarios</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        h1 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        
        .btn-back { display: inline-block; padding: 10px 20px; background: #34495e; color: white; text-decoration: none; border-radius: 50px; margin-bottom: 20px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #3498db; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #555; }
        
        .btn-money { background: #2ecc71; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.9em; }

        /* --- MÓVIL --- */
        @media (max-width: 768px) {
            thead { display: none; }
            tr { display: block; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            td { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 8px 0; text-align: right; }
            td:last-child { border: none; justify-content: center; padding-top: 15px; }
            td::before { content: attr(data-label); font-weight: bold; color: #333; text-align: left; }
            
            .btn-money { display: block; width: 100%; text-align: center; padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Usuarios 👥</h1>
            <a href="index.php" class="btn-back">← Volver</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Saldo</th>
                    <th>Gastado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td data-label="ID">#<?= $u['id'] ?></td>
                    <td data-label="Email"><?= htmlspecialchars($u['email']) ?></td>
                    <td data-label="Saldo" style="color: <?= $u['account_balance'] < 2000 ? 'red' : 'green' ?>; font-weight:bold;">
                        $<?= number_format($u['account_balance']) ?>
                    </td>
                    <td data-label="Gastado">$<?= number_format($u['spent']) ?></td>
                    <td data-label="Acción">
                        <a href="adjust_balance.php?id=<?= $u['id'] ?>" class="btn-money">💰 Ajustar Saldo</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>