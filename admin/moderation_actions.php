<?php
/**
 * admin/moderation_actions.php
 * Procesa la aprobación/rechazo de banners y notifica al usuario.
 */
session_start();
require_once 'db_connect.php'; 
require_once '../clases/NotificationService.php'; // Sistema de Notificaciones

// 1. Seguridad: Solo Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$redirect_url = 'moderation.php';

if ($action && $id) {
    
    try {
        // Obtener información del banner antes de actuar (para la notificación)
        $stmtInfo = $pdo->prepare("SELECT user_id, titulo FROM banners WHERE id = :id");
        $stmtInfo->execute([':id' => $id]);
        $bannerInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if (!$bannerInfo) {
            throw new Exception("Banner no encontrado.");
        }

        // 2. Ejecutar Acción
        if ($action === 'approve') {
            // APROBAR: Marca como aprobado y activo
            $stmt = $pdo->prepare("UPDATE banners SET is_approved = TRUE, is_active = TRUE WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $msg = "Banner ID {$id} aprobado y activado exitosamente.";
            
            // --- NOTIFICAR AL USUARIO ---
            try {
                $notifier = new NotificationService($pdo);
                $notifier->notify($bannerInfo['user_id'], 'ad_approved', [
                    '%ad_title%' => $bannerInfo['titulo']
                ]);
            } catch (Exception $eNotify) {
                error_log("Error notificación aprobación: " . $eNotify->getMessage());
                $msg .= " (Pero hubo un error enviando la notificación).";
            }
            // -----------------------------

        } elseif ($action === 'reject') {
            // RECHAZAR: Marca como revisado (approved=1 para que salga de pendientes) pero inactivo
            // Nota: Dependiendo de tu lógica, podrías querer borrarlo o tener un estado 'rechazado'.
            // Aquí asumimos que 'is_approved=TRUE' significa "ya lo revisé", y 'is_active=FALSE' lo mantiene oculto.
            
            $stmt = $pdo->prepare("UPDATE banners SET is_approved = TRUE, is_active = FALSE WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $msg = "Banner ID {$id} ha sido rechazado.";
        }
        
        $redirect_url = "moderation.php?status=success&msg=" . urlencode($msg);

    } catch (PDOException $e) {
        $msg = "Error de base de datos: " . $e->getMessage();
        $redirect_url = "moderation.php?status=error&msg=" . urlencode($msg);
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $redirect_url = "moderation.php?status=error&msg=" . urlencode($msg);
    }
    
} else {
    $msg = "Acción o ID inválido.";
    $redirect_url = "moderation.php?status=error&msg=" . urlencode($msg);
}

// 3. Redirección final
header("Location: " . $redirect_url);
exit;
?>