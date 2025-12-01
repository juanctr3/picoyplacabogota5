<?php
/**
 * user/user_actions.php - Acciones del Anunciante
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php");
    exit;
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];
$redirect_url = 'campaigns.php';

if ($action && $id) {
    try {
        if ($action === 'delete') {
            // --- ELIMINAR (Solo si le pertenece) ---
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = :id AND user_id = :userId");
            $stmt->execute([':id' => $id, ':userId' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                $message = urlencode("Anuncio eliminado correctamente.");
            } else {
                $message = urlencode("No se pudo eliminar. El anuncio no existe o no tienes permiso.");
            }
            $redirect_url = "campaigns.php?msg={$message}";

        } else {
            // --- PAUSAR / ACTIVAR ---
            $new_status = ($action === 'activate') ? 1 : 0;
            
            // Al activar, pasa a pendiente de aprobación si estaba inactivo por moderación
            // Aquí asumimos lógica simple: Si el usuario lo activa, vuelve a pedir aprobación (opcional)
            // O simplemente cambia el estado is_active si ya estaba aprobado.
            
            $stmt = $pdo->prepare("UPDATE banners SET is_active = :status WHERE id = :id AND user_id = :userId");
            $stmt->execute([':status' => $new_status, ':id' => $id, ':userId' => $userId]);
            
            $estado_txt = $new_status ? 'ACTIVO' : 'PAUSADO';
            $message = urlencode("Campaña actualizada a: {$estado_txt}");
            $redirect_url = "campaigns.php?msg={$message}";
        }

    } catch (PDOException $e) {
        $message = urlencode("Error: " . $e->getMessage());
        $redirect_url = "campaigns.php?msg={$message}";
    }
}

header("Location: " . $redirect_url);
exit;
?>