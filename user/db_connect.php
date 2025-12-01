<?php
/**
 * user/db_connect.php - Módulo centralizado de conexión PDO para el Panel de Anunciantes.
 * Proporciona la variable $pdo a todos los scripts del directorio /user.
 */

// 1. CONFIGURACIÓN DE LA BASE DE DATOS
$dbHost = 'localhost';
$dbName = 'picoyplacabogota';   
$dbUser = 'picoyplacabogota';   
$dbPass = 'Q20BsIFHI9j8h2XoYNQm3RmQg';   
$pdo = null; 

try {
    // Conexión a la Base de Datos
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si la conexión falla, terminamos la ejecución
    http_response_code(500);
    die("Error crítico de conexión a la Base de Datos. Contacte al administrador.");
}
?>