<?php
/**
 * admin/moderation_actions.php - Procesa la aprobación/rechazo de banners pendientes.
 * Acciones: approve (establece is_approved=TRUE, is_active=TRUE) y reject (establece is_approved=TRUE, is_active=FALSE).
 */
session_start();
require_once 'db_connect.php'; 

// 1. Verificación de Rol (CRÍTICO: Solo Admin puede moderar)
// En un entorno real, esta verificación sería más robusta.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$redirect_url = 'moderation.php';

if ($action && $id) {
    $is_approved_status = TRUE; // El banner ha sido revisado
    $is_active_status = ($action === 'approve') ? TRUE : FALSE; // Si aprueba, también activa. Si rechaza, desactiva.
    
    try {
        // 2. Ejecución de la Acción
        $stmt = $pdo->prepare("UPDATE banners SET is_approved = :is_approved, is_active = :is_active WHERE id = :id");
        
        $stmt->execute([
            ':is_approved' => $is_approved_status,
            ':is_active' => $is_active_status, 
            ':id' => $id
        ]);
        
        // 3. Redirección con mensaje
        $msg = ($action === 'approve') ? "Banner ID {$id} aprobado y activado." : "Banner ID {$id} rechazado y deshabilitado.";
        $redirect_url = "moderation.php?status=success&msg=" . urlencode($msg);

    } catch (PDOException $e) {
        $msg = "Error al ejecutar la acción: " . $e->getMessage();
        $redirect_url = "moderation.php?status=error&msg=" . urlencode($msg);
    }
} else {
    $msg = "Acción o ID inválido.";
    $redirect_url = "moderation.php?status=error&msg=" . urlencode($msg);
}

header("Location: " . $redirect_url);
exit;