<?php
/**
 * admin/actions.php - Lógica de Activación/Desactivación de Banners (Admin)
 * Procesamiento de la acción y redirección forzada al panel de gestión (index.php).
 */

session_start();
// CRÍTICO: Usar el módulo de conexión directo para evitar errores de inclusión
require_once 'db_connect.php'; 

// 1. Verificación de Rol (solo Admin puede ejecutar esto)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

// 2. OBTENER PARÁMETROS
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

// Por defecto, redirigimos a la página principal de gestión
$redirect_url = 'index.php';

if ($action && $id) {
    // Definir el nuevo estado y forzar a entero (0 o 1)
    $new_status_bool = ($action === 'activate') ? TRUE : FALSE;
    $new_status_int = (int)$new_status_bool; 
    
    try {
        // Ejecución de la acción
        $stmt = $pdo->prepare("UPDATE banners SET is_active = :status WHERE id = :id");
        
        $stmt->execute([
            ':status' => $new_status_int, 
            ':id' => $id
        ]);
        
        // 3. REDIRECCIÓN CON MENSAJE DE ÉXITO
        $message = urlencode("Banner ID {$id} actualizado a " . ($new_status_bool ? 'ACTIVO' : 'INACTIVO') . ".");
        $redirect_url = "index.php?status=success&msg={$message}";

    } catch (PDOException $e) {
        $message = urlencode("Error al ejecutar la acción: " . $e->getMessage());
        $redirect_url = "index.php?status=error&msg={$message}";
    }
} else {
    $message = urlencode("Acción o ID inválido.");
    $redirect_url = "index.php?status=error&msg={$message}";
}

// Redirigir SIEMPRE al panel de gestión (index.php)
header("Location: " . $redirect_url);
exit;