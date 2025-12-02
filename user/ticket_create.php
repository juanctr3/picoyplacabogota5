<?php
/**
 * user/ticket_create.php - Crear Ticket + Notificaciones
 */
session_start();
require_once 'db_connect.php'; 
// Cargar notificador
if (file_exists('../clases/NotificationService.php')) {
    require_once '../clases/NotificationService.php';
}

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if ($subject && $message) {
        try {
            $pdo->beginTransaction();
            
            // 1. Crear Ticket
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, status) VALUES (:uid, :sub, 'abierto')");
            $stmt->execute([':uid' => $userId, ':sub' => $subject]);
            $ticketId = $pdo->lastInsertId();
            
            // 2. Insertar Mensaje
            $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (:tid, 'user', :msg)");
            $stmtMsg->execute([':tid' => $ticketId, ':msg' => $message]);
            
            $pdo->commit();

            // 3. ENVIAR NOTIFICACIONES
            if (class_exists('NotificationService')) {
                try {
                    $notifier = new NotificationService($pdo);
                    
                    // Datos para las plantillas
                    $data = [
                        '%id%' => $ticketId,
                        '%subject%' => $subject,
                        '%message%' => $message
                    ];

                    // A. Notificar al USUARIO (Confirmación)
                    $notifier->notifyCustom('ticket_new_user', $data, $_SESSION['user_email'], null); // null en phone si no lo tenemos en sesión, el servicio lo buscará en BD si usa notify() normal, pero aquí usamos custom.
                    // Mejor usamos el método notify estándar que busca el teléfono en BD:
                    $notifier->notify($userId, 'ticket_new_user', $data);

                    // B. Notificar al ADMIN (Alerta)
                    // Obtenemos email admin de config
                    $stmtConf = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'admin_email'");
                    $adminEmail = $stmtConf->fetchColumn();
                    
                    if ($adminEmail) {
                        // Agregamos datos del usuario para el admin
                        $data['%email%'] = $_SESSION['user_email'];
                        $notifier->notifyCustom('ticket_new_admin', $data, $adminEmail);
                    }

                } catch (Exception $e) { error_log("Error notif ticket: " . $e->getMessage()); }
            }

            header("Location: tickets.php"); exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al crear ticket.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ticket</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        input, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { margin-top: 20px; padding: 12px 25px; background: #2ecc71; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="color:#2c3e50; border-bottom:2px solid #e67e22; padding-bottom:10px;">📝 Nuevo Ticket</h2>
        <form method="POST">
            <label><strong>Asunto:</strong></label>
            <input type="text" name="subject" required placeholder="Ej: Problema con recarga..." maxlength="100">
            
            <label style="margin-top:15px; display:block;"><strong>Mensaje:</strong></label>
            <textarea name="message" rows="6" required placeholder="Detalla tu solicitud..."></textarea>
            
            <button type="submit">Enviar Ticket</button>
            <a href="tickets.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>
</body>
</html>