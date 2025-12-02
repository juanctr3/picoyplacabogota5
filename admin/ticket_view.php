<?php
/**
 * admin/ticket_view.php - Admin Ticket (Con Notificación de Cierre)
 */
session_start();
require_once 'db_connect.php'; 
if (file_exists('../clases/NotificationService.php')) { require_once '../clases/NotificationService.php'; }

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

$ticketId = $_GET['id'] ?? null;

// Cargar Ticket
$stmt = $pdo->prepare("SELECT t.*, u.email, u.full_name, u.phone FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = :id");
$stmt->execute([':id' => $ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("Ticket no encontrado.");

// Procesar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // RESPONDER
    if (isset($_POST['reply']) && !empty($_POST['reply'])) {
        $msg = trim($_POST['reply']);
        $stmtRep = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (:tid, 'admin', :msg)");
        $stmtRep->execute([':tid' => $ticketId, ':msg' => $msg]);
        
        $pdo->prepare("UPDATE tickets SET status = 'respondido', updated_at = NOW() WHERE id = :id")->execute([':id' => $ticketId]);
        
        // Notificar Respuesta
        if (class_exists('NotificationService')) {
            try {
                $notifier = new NotificationService($pdo);
                $notifier->notify($ticket['user_id'], 'ticket_reply_user', [
                    '%id%' => $ticketId, '%reply%' => nl2br($msg)
                ]);
            } catch (Exception $e) {}
        }
        header("Location: ticket_view.php?id=$ticketId"); exit;
    }
    
    // CERRAR TICKET
    if (isset($_POST['close_ticket'])) {
        $pdo->prepare("UPDATE tickets SET status = 'cerrado', updated_at = NOW() WHERE id = :id")->execute([':id' => $ticketId]);
        
        // --- NOTIFICAR CIERRE AL USUARIO ---
        if (class_exists('NotificationService')) {
            try {
                $notifier = new NotificationService($pdo);
                // Usamos notifyCustom o notify normal si creamos el caso en el servicio
                // Usaremos notify normal ya que 'ticket_closed_user' ya busca los datos del usuario en la clase
                $notifier->notify($ticket['user_id'], 'ticket_closed_user', [
                    '%id%' => $ticketId
                ]);
            } catch (Exception $e) {}
        }
        // -----------------------------------
        
        header("Location: ticket_view.php?id=$ticketId"); exit;
    }
}

// Cargar Mensajes (Igual que antes...)
$msgs = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = :tid ORDER BY created_at ASC");
$msgs->execute([':tid' => $ticketId]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $ticketId ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; display: flex; flex-direction: column; height: 90vh; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .chat-box { flex: 1; overflow-y: auto; padding: 15px; background: #f8f9fa; border: 1px solid #eee; border-radius: 8px; margin-bottom: 20px; }
        .msg { margin-bottom: 15px; padding: 10px 15px; border-radius: 8px; max-width: 80%; }
        .msg-user { background: #fff3cd; color: #856404; align-self: flex-start; margin-right: auto; }
        .msg-admin { background: #d1fae5; color: #065f46; align-self: flex-end; margin-left: auto; text-align: right; }
        .meta { font-size: 0.75em; opacity: 0.7; margin-bottom: 5px; display: block; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; height: 80px; resize: none; box-sizing: border-box; }
        .btn-send { background: #2ecc71; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .btn-close { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9em; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="tickets.php" style="text-decoration:none; color:#7f8c8d;">← Volver</a>
                <h2 style="margin:5px 0 0;">#<?= $ticketId ?>: <?= htmlspecialchars($ticket['subject']) ?></h2>
                <small>De: <?= htmlspecialchars($ticket['full_name']) ?> (<?= $ticket['email'] ?>)</small>
            </div>
            
            <?php if ($ticket['status'] !== 'cerrado'): ?>
            <form method="POST" onsubmit="return confirm('¿Seguro? El usuario no podrá responder más.');">
                <input type="hidden" name="close_ticket" value="1">
                <button class="btn-close">Cerrar Ticket</button>
            </form>
            <?php else: ?>
                <span style="background:#333; color:white; padding:5px 10px; border-radius:4px; font-size:0.8em;">TICKET CERRADO</span>
            <?php endif; ?>
        </div>

        <div class="chat-box" id="chat">
            <?php foreach ($messages as $m): ?>
                <div style="display:flex; flex-direction:column;">
                    <div class="msg <?= $m['sender_type'] === 'admin' ? 'msg-admin' : 'msg-user' ?>">
                        <span class="meta"><?= $m['sender_type'] === 'admin' ? 'Tú (Soporte)' : $ticket['full_name'] ?> • <?= date('d/m H:i', strtotime($m['created_at'])) ?></span>
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($ticket['status'] !== 'cerrado'): ?>
        <form method="POST">
            <textarea name="reply" required placeholder="Escribe una respuesta al usuario..."></textarea>
            <button type="submit" class="btn-send">Enviar Respuesta</button>
        </form>
        <?php endif; ?>
    </div>
    <script> document.getElementById('chat').scrollTop = document.getElementById('chat').scrollHeight; </script>
</body>
</html>