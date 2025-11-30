<?php
/**
 * admin/actions.php - Lógica de Activación/Desactivación de Banners
 * CORRECCIÓN: Se fuerza el valor BOOLEAN a entero (0 o 1) para evitar el error 1366.
 */

// Este script asume que reports.php ya ha configurado la conexión PDO
// Requerimos el archivo de reportes para reutilizar la conexión y las credenciales.
require_once 'reports.php'; 

// 1. OBTENER PARÁMETROS
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$redirect_url = 'index.php';

if ($action && $id) {
    // 2. Definir el nuevo estado y forzar a entero (0 o 1)
    // 'activate' -> 1 (TRUE); 'deactivate' -> 0 (FALSE)
    $new_status_bool = ($action === 'activate') ? TRUE : FALSE;
    $new_status_int = (int)$new_status_bool; // <--- ESTA LÍNEA ES LA CLAVE DE LA CORRECCIÓN
    
    try {
        // 3. CONEXIÓN Y EJECUCIÓN DE LA ACCIÓN
        $stmt = $pdo->prepare("UPDATE banners SET is_active = :status WHERE id = :id");
        
        $stmt->execute([
            ':status' => $new_status_int, // Usamos el entero forzado (0 o 1)
            ':id' => $id
        ]);
        
        // 4. REDIRECCIÓN CON MENSAJE DE ÉXITO
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

// Redirigir al panel de gestión
header("Location: " . $redirect_url);
exit;