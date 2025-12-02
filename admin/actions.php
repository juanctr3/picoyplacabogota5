<?php
/**
 * admin/actions.php - Gestión de Acciones Admin (Activar/Pausar/Eliminar)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$redirect_url = 'index.php';

if ($action && $id) {
    try {
        if ($action === 'delete') {
            // --- ELIMINAR ---
            // Opcional: Si quieres borrar la imagen del servidor, primero haz un SELECT para obtener la ruta y usa unlink()
            
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $message = urlencode("Banner ID {$id} eliminado correctamente.");
            $redirect_url = "index.php?status=success&msg={$message}";
            
        } else {
            // --- ACTIVAR / DESACTIVAR ---
            $new_status = ($action === 'activate') ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE banners SET is_active = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $id]);
            
            $estado_txt = $new_status ? 'ACTIVO' : 'INACTIVO';
            $message = urlencode("Banner ID {$id} actualizado a {$estado_txt}.");
            $redirect_url = "index.php?status=success&msg={$message}";
        }

    } catch (PDOException $e) {
        $message = urlencode("Error de base de datos: " . $e->getMessage());
        $redirect_url = "index.php?status=error&msg={$message}";
    }
} else {
    $message = urlencode("Acción o ID inválido.");
    $redirect_url = "index.php?status=error&msg={$message}";
}

header("Location: " . $redirect_url);
exit;
?>