<?php
/**
 * admin/db_connect.php
 * Conexión centralizada para el panel administrativo.
 */

$dbHost = 'localhost';
$dbName = 'picoyplacabogota';   
$dbUser = 'picoyplacabogota';   
$dbPass = 'Q20BsIFHI9j8h2XoYNQm3RmQg'; 

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // En producción, es mejor no mostrar detalles técnicos del error
    die("Error crítico de conexión a la base de datos.");
}
?>