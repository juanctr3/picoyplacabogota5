<?php
/**
 * admin/user_management.php - Panel de Gestión de Cuentas de Anunciantes
 * Permite al administrador ver saldos, gastos y estadísticas de usuarios.
 */
session_start();
require_once 'db_connect.php'; 

// 1. Verificación de Rol (CRÍTICO: Solo Admin)
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
    // Consulta SQL para obtener todos los usuarios y sus estadísticas financieras combinadas
    // Usa LEFT JOIN para incluir a los anunciantes que aún no tienen banners/eventos
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.email, u.account_balance, u.created_at, u.role,
            COALESCE(SUM(be.cost_applied), 0.00) AS total_spent_on_ads
        FROM users u
        LEFT JOIN banners b ON u.id = b.user_id
        LEFT JOIN banner_events be ON b.id = be.banner_id
        WHERE u.role = 'advertiser'
        GROUP BY u.id, u.email, u.account_balance, u.created_at, u.role
        ORDER BY u.id ASC
    ");
    $stmt->execute();
    $anunciantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("DB Error fetching user management data: " . $e->getMessage());
    $message = '<div class="error-msg">Error al cargar la lista de anunciantes.</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #34495e; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; font-size: 0.9em; }
        th { background-color: #3498db; color: white; }
        .btn-action { padding: 5px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9em; background-color: #f39c12; color: white; }
        .status-message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .success-msg { background-color: #d4edda; color: #155724; }
        .error-msg { background-color: #f8d7da; color: #721c24; }
        .logout-link { text-align: right; margin-bottom: 20px; }
        .balance-low { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logout-link">
            <a href="../user/logout.php">Cerrar Sesión Admin</a>
        </div>
        <h1>Gestión de Cuentas de Anunciantes 👥</h1>
        <p><a href="index.php">← Volver a Gestión de Campañas</a></p>

        <?= $message ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email (Anunciante)</th>
                    <th>Saldo Actual (COP)</th>
                    <th>Gasto Total</th>
                    <th>Estado de Saldo</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($anunciantes)): ?>
                    <tr><td colspan="7" style="text-align:center;">No hay anunciantes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($anunciantes as $u): ?>
                    <?php
                        // Usando el umbral de $5 COP (LOW_BALANCE_THRESHOLD = 5.00)
                        $is_low_balance = $u['account_balance'] <= 5.00 && $u['account_balance'] > 0;
                        $is_empty = $u['account_balance'] <= 0;
                    ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>$<?= number_format($u['account_balance'], 2, ',', '.') ?></td>
                        <td>$<?= number_format($u['total_spent_on_ads'], 2, ',', '.') ?></td>
                        <td>
                            <?php if ($is_empty): ?>
                                <span class="balance-low">AGOTADO</span>
                            <?php elseif ($is_low_balance): ?>
                                <span class="balance-low">BAJO</span>
                            <?php else: ?>
                                OK
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="adjust_balance.php?id=<?= $u['id'] ?>" class="btn-action">Ajustar Saldo</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>