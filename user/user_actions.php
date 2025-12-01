<?php
/**
 * user/user_actions.php - Lógica de Pausa/Reanudación de Campañas (Solo para el Usuario Logueado)
 */
session_start();
require_once 'db_connect.php'; 

// Comprobar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php");
    exit;
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];
$redirect_url = 'campaigns.php';

if ($action && $id) {
    // Definir el nuevo estado y forzar a entero (0 o 1)
    $new_status_bool = ($action === 'activate') ? TRUE : FALSE;
    $new_status_int = (int)$new_status_bool; 
    
    try {
        // Ejecución de la acción, con filtro por user_id para seguridad
        $stmt = $pdo->prepare("UPDATE banners SET is_active = :status, is_approved = FALSE WHERE id = :id AND user_id = :userId");
        
        $stmt->execute([
            ':status' => $new_status_int, 
            ':id' => $id,
            ':userId' => $userId
        ]);
        
        // Redirección
        $message = urlencode("Campaña ID {$id} actualizada a " . ($new_status_bool ? 'ACTIVO' : 'PAUSADO') . ". Si estaba ACTIVA, ahora se considera PENDIENTE DE REVISIÓN.");
        $redirect_url = "campaigns.php?msg={$message}";

    } catch (PDOException $e) {
        $message = urlencode("Error al ejecutar la acción: " . $e->getMessage());
        $redirect_url = "campaigns.php?msg={$message}";
    }
} else {
    $message = urlencode("Acción o ID inválido.");
    $redirect_url = "campaigns.php?msg={$message}";
}

header("Location: " . $redirect_url);
exit;
?>