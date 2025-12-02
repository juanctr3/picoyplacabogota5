<?php
/**
 * admin/fix_db_columns.php
 * Script de Autocuración de Base de Datos
 * Agrega las columnas faltantes automáticamente.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db_connect.php'; 

echo "<h1>🛠️ Reparando Estructura de Base de Datos...</h1>";

// Lista de columnas necesarias en la tabla 'users'
$required_columns = [
    'full_name'       => "VARCHAR(100) DEFAULT NULL",
    'user_type'       => "ENUM('natural', 'juridica') DEFAULT 'natural'",
    'document_type'   => "ENUM('CC', 'NIT') DEFAULT 'CC'",
    'document_number' => "VARCHAR(20) DEFAULT NULL",
    'needs_invoice'   => "TINYINT(1) DEFAULT 0",
    'rut_file'        => "VARCHAR(255) DEFAULT NULL",
    'phone'           => "VARCHAR(25) DEFAULT NULL",
    'reset_token'     => "VARCHAR(64) NULL",
    'reset_expires'   => "DATETIME NULL"
];

try {
    // Obtener columnas actuales
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<ul>";
    foreach ($required_columns as $col => $def) {
        if (!in_array($col, $existing)) {
            // Si la columna no existe, la creamos
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
                echo "<li style='color:green'>✅ Columna <b>$col</b> creada exitosamente.</li>";
            } catch (Exception $e) {
                echo "<li style='color:red'>❌ Error creando <b>$col</b>: " . $e->getMessage() . "</li>";
            }
        } else {
            echo "<li style='color:blue'>ℹ️ Columna <b>$col</b> ya existe.</li>";
        }
    }
    echo "</ul>";
    
    echo "<h3>¡Listo! Prueba crear el anuncio nuevamente.</h3>";
    echo "<a href='../user/create_ad.php'>Ir a Crear Anuncio</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error Fatal de Conexión: " . $e->getMessage() . "</h2>";
}
?>