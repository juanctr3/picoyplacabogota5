<?php
/**
 * user/ticket_view.php - Ver Ticket (Con Bloqueo de Cierre)
 */
session_start();
require_once 'db_connect.php'; 
require_once '../clases/NotificationService.php'; // Para notificar si se necesitara

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];
$ticketId = $_GET['id'] ?? null;

// Validar ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $ticketId, ':uid' => $userId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) { die("Ticket no encontrado."); }

// Enviar Respuesta (SOLO SI NO ESTÁ CERRADO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['reply'])) {
    
    // VALIDACIÓN DE SEGURIDAD: Impedir responder si está cerrado
    if ($ticket['status'] === 'cerrado') {
        header("Location: ticket_view.php?id=$ticketId&error=closed");
        exit;
    }

    $msg = trim($_POST['reply']);
    
    // Guardar mensaje
    $stmtReply = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (:tid, 'user', :msg)");
    $stmtReply->execute([':tid' => $ticketId, ':msg' => $msg]);
    
    // Actualizar a abierto (si estaba respondido)
    $pdo->prepare("UPDATE tickets SET status = 'abierto', updated_at = NOW() WHERE id = :id")->execute([':id' => $ticketId]);
    
    // Notificar Admin
    if (class_exists('NotificationService')) {
        try {
            $notifier = new NotificationService($pdo);
            $stmtConf = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'admin_email'");
            $adminEmail = $stmtConf->fetchColumn();
            
            // Obtener nombre usuario actual
            $stmtU = $pdo->prepare("SELECT full_name FROM users WHERE id = :id");
            $stmtU->execute([':id'=>$userId]);
            $uName = $stmtU->fetchColumn();

            if ($adminEmail) {
                $notifier->notifyCustom('ticket_reply_admin', [
                    '%id%' => $ticketId,
                    '%name%' => $uName,
                    '%reply%' => nl2br($msg)
                ], $adminEmail);
            }
        } catch (Exception $e) {}
    }
    
    header("Location: ticket_view.php?id=$ticketId"); exit;
}

// Cargar Mensajes
$stmtMsgs = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = :tid ORDER BY created_at ASC");
$stmtMsgs->execute([':tid' => $ticketId]);
$messages = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $ticketId ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; display: flex; flex-direction: column; height: 90vh; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .chat-box { flex: 1; overflow-y: auto; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px; }
        .msg { margin-bottom: 15px; padding: 10px 15px; border-radius: 8px; max-width: 80%; }
        .msg-user { background: #d4edda; color: #155724; align-self: flex-end; margin-left: auto; }
        .msg-admin { background: #fff3cd; color: #856404; align-self: flex-start; margin-right: auto; }
        .closed-alert { background: #f8d7da; color: #721c24; padding: 15px; text-align: center; border-radius: 5px; font-weight: bold; border: 1px solid #f5c6cb; }
        .btn-new { display: inline-block; margin-top: 10px; background: #2c3e50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 0.9em; }
        .btn-send { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; height: 60px; margin-bottom: 10px; }
        .btn-back { text-decoration: none; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="tickets.php" class="btn-back">← Volver</a>
            <div style="display:flex; justify-content:space-between; margin-top:10px;">
                <h2 style="margin:0;">#<?= $ticketId ?>: <?= htmlspecialchars($ticket['subject']) ?></h2>
                <span style="background:<?= $ticket['status']=='cerrado'?'#343a40':'#2ecc71' ?>; color:white; padding:3px 8px; border-radius:4px; font-size:0.8em; align-self:center;">
                    <?= strtoupper($ticket['status']) ?>
                </span>
            </div>
        </div>

        <div class="chat-box" id="chat">
            <?php foreach ($messages as $m): ?>
                <div style="display:flex; flex-direction:column;">
                    <div class="msg <?= $m['sender_type'] === 'user' ? 'msg-user' : 'msg-admin' ?>">
                        <small style="opacity:0.7; display:block; margin-bottom:3px;">
                            <?= $m['sender_type'] === 'user' ? 'Tú' : 'Soporte' ?> • <?= date('d/m H:i', strtotime($m['created_at'])) ?>
                        </small>
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($ticket['status'] === 'cerrado'): ?>
            <div class="closed-alert">
                🔒 Este ticket ha sido cerrado y no admite más respuestas.<br>
                <a href="ticket_create.php" class="btn-new">Abrir Nuevo Ticket</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <textarea name="reply" required placeholder="Escribe tu respuesta..."></textarea>
                <button type="submit" class="btn-send">Enviar Mensaje</button>
            </form>
        <?php endif; ?>
    </div>
    <script> document.getElementById('chat').scrollTop = document.getElementById('chat').scrollHeight; </script>
</body>
</html>