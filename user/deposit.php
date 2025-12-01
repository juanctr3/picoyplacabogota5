<?php
/**
 * user/deposit.php - Recarga de Saldo para Anunciantes
 * Simula la adición de fondos al account_balance.
 */
session_start();
require_once 'db_connect.php'; 

// Comprobar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitizar y validar la cantidad
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($amount <= 0 || $amount > 1000) { // Límite de ejemplo
        $error = 'Por favor, ingrese un monto válido entre $1 y $1000.';
    } else {
        try {
            // 2. Ejecutar la actualización del saldo
            // Se usa "account_balance = account_balance + :amount" para asegurar atomicidad.
            $stmt = $pdo->prepare("UPDATE users SET account_balance = account_balance + :amount WHERE id = :userId");
            $stmt->execute([':amount' => $amount, ':userId' => $userId]);
            
            // 3. Simulación de procesamiento de pago exitoso
            // En la vida real, este sería el punto de confirmación de la pasarela de pago.
            
            // 4. Redirigir al dashboard con mensaje de éxito
            header("Location: dashboard.php?recharged=true");
            exit;

        } catch (PDOException $e) {
            $error = 'Error de base de datos al procesar la recarga.';
            error_log("Deposit DB Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recargar Cuenta</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f7f6; }
        .form-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h1 { color: #3498db; text-align: center; margin-bottom: 25px; }
        p { text-align: center; margin-bottom: 30px; color: #555; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"] { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1.2em; text-align: right; }
        button { width: 100%; background-color: #2ecc71; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1.1em; transition: background-color 0.3s; }
        button:hover { background-color: #27ad60; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .back-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Recargar Saldo</h1>
        <p>Simulación de pago. Ingrese la cantidad que desea añadir a su cuenta de anunciante.</p>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="deposit.php">
            <label for="amount">Monto a Recargar (USD):</label>
            <input type="number" id="amount" name="amount" min="1" step="0.01" required placeholder="Ej: 50.00">
            
            <button type="submit">Confirmar Recarga (Simulación)</button>
        </form>
        <div class="back-link">
            <a href="dashboard.php">Volver al Panel</a>
        </div>
    </div>
</body>
</html>