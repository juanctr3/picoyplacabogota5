<?php
/**
 * admin/install_tickets_db.php
 * Script para crear automáticamente las tablas de soporte (Tickets)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db_connect.php'; 

echo "<h1>🛠️ Instalando Base de Datos de Soporte...</h1>";

try {
    // 1. Crear tabla TICKETS
    $sqlTickets = "CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(150) NOT NULL,
        status ENUM('abierto', 'respondido', 'cerrado') DEFAULT 'abierto',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sqlTickets);
    echo "<p style='color:green'>✅ Tabla <b>'tickets'</b> creada correctamente.</p>";

    // 2. Crear tabla MENSAJES
    $sqlMessages = "CREATE TABLE IF NOT EXISTS ticket_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_type ENUM('user', 'admin') DEFAULT 'user',
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sqlMessages);
    echo "<p style='color:green'>✅ Tabla <b>'ticket_messages'</b> creada correctamente.</p>";

    echo "<h3>🎉 ¡Proceso Finalizado!</h3>";
    echo "<p>Ya puedes borrar este archivo e intentar entrar a <a href='../user/tickets.php'>Mis Tickets</a>.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ Error Fatal: " . $e->getMessage() . "</h3>";
}
?>