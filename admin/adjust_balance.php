<?php
/**
 * admin/adjust_balance.php - Interfaz para Ajustar el Saldo de un Anunciante
 * Permite al Admin añadir o restar dinero a una cuenta de usuario específica.
 */
session_start();
require_once 'db_connect.php'; 

// 1. Verificación de Rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$userId = $_GET['id'] ?? null;
$message = '';
$es_error = false;
$user = null;

try {
    if (!$userId) {
        throw new Exception("ID de usuario no especificado.");
    }

    // Obtener datos del usuario
    $stmt = $pdo->prepare("SELECT id, email, account_balance FROM users WHERE id = :id AND role = 'advertiser'");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Usuario no encontrado o no es anunciante.");
    }
    
    // 2. Lógica de Procesamiento del Formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $amount = (float)($_POST['amount'] ?? 0);
        $operation = $_POST['operation'] ?? 'add';
        
        $final_amount = ($operation === 'subtract') ? -$amount : $amount;
        
        if (abs($amount) < 0.01) {
             throw new Exception("El monto debe ser mayor a 0.");
        }

        // Actualizar Saldo
        $stmt_update = $pdo->prepare("UPDATE users SET account_balance = account_balance + :amount WHERE id = :id");
        $stmt_update->execute([':amount' => $final_amount, ':id' => $userId]);

        // Re-obtener el saldo para mostrar la actualización
        $user['account_balance'] += $final_amount; 

        $message = "Saldo de {$user['email']} ajustado con éxito. Cantidad: " . ($final_amount > 0 ? "+" : "") . number_format($final_amount, 2);
    }

} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    $es_error = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajustar Saldo - Admin</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #34495e; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-bottom: 20px; }
        .current-balance { font-size: 1.5em; font-weight: bold; color: #2ecc71; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="number"], select { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1.1em; text-align: right; }
        button { width: 100%; background-color: #3498db; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error-box { background-color: #fcebeb; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .success-box { background-color: #e6f7e9; color: #27ae60; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .radio-group { display: flex; gap: 20px; margin-bottom: 20px; }
        .back-link { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajustar Saldo (Admin)</h1>
        
        <?php if ($message): ?>
            <div class="<?= $es_error ? 'error-box' : 'success-box' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <p>Ajustando saldo para: <strong><?= htmlspecialchars($user['email']) ?></strong></p>
            <div class="current-balance">Saldo Actual: $<?= number_format($user['account_balance'], 2, ',', '.') ?></div>

            <form method="POST" action="adjust_balance.php?id=<?= $userId ?>">
                <label>Monto a Ajustar:</label>
                <input type="number" name="amount" min="0.01" step="0.01" required placeholder="Ej: 50.00">
                
                <label>Operación:</label>
                <div class="radio-group">
                    <label><input type="radio" name="operation" value="add" checked> Añadir Saldo</label>
                    <label><input type="radio" name="operation" value="subtract"> Descontar Saldo</label>
                </div>

                <button type="submit">Confirmar Ajuste</button>
            </form>
        <?php else: ?>
            <div class="error-box">No se pudo cargar la información del usuario.</div>
        <?php endif; ?>

        <div class="back-link">
            <a href="user_management.php">← Volver a Gestión de Cuentas</a>
        </div>
    </div>
</body>
</html>