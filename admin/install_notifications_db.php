<?php
/**
 * admin/install_notifications_db.php
 * Instala las configuraciones base para el sistema de notificaciones.
 */
require_once 'db_connect.php'; 

$configs = [
    // --- SMTP (Amazon SES) ---
    'smtp_host' => 'email-smtp.us-east-1.amazonaws.com',
    'smtp_user' => 'AKIAVYSEJCELS4T7AOK5',
    'smtp_pass' => 'BL0B8pq/6xm/3mJ0GuZCpHaK+DdVHHmJQ9bNTiuNWSUR',
    'smtp_from' => 'info@picoypl.com',
    'smtp_port' => '587', // Puerto estándar TLS
    'admin_email' => 'tu_email@admin.com', // Email del admin para recibir alertas

    // --- WhatsApp (smsenlinea) ---
    'wa_secret' => '',  // A configurar en el panel
    'wa_account' => '', // A configurar en el panel

    // --- Plantillas: Recarga Exitosa ---
    'tpl_recharge_subject' => '¡Recarga Exitosa! - PicoYPlaca Ads',
    'tpl_recharge_email'   => 'Hola %name%,<br>Tu recarga de $%amount% COP ha sido exitosa. Tu nuevo saldo es: $%balance%.<br>Gracias por confiar en nosotros.',
    'tpl_recharge_wa'      => 'Hola %name%, confirmamos tu recarga de $%amount% COP. Nuevo saldo: $%balance%.',

    // --- Plantillas: Anuncio Aprobado ---
    'tpl_approve_subject'  => '¡Tu anuncio está en vivo!',
    'tpl_approve_email'    => 'Hola %name%,<br>Buenas noticias: Tu anuncio "%ad_title%" ha sido aprobado y ya está en rotación.',
    'tpl_approve_wa'       => 'Hola %name%, tu anuncio "%ad_title%" fue aprobado y ya está visible.',
    
    // --- Plantillas: Saldo Bajo ---
    'tpl_low_balance_subject' => 'Alerta: Saldo Bajo',
    'tpl_low_balance_email'   => 'Hola %name%,<br>Tu saldo está por agotarse ($%balance%). Recarga pronto para no detener tus campañas.',
    'tpl_low_balance_wa'      => 'Hola %name%, alerta de saldo bajo: $%balance%. Recarga para mantener tus anuncios activos.'
];

echo "<h2>Instalando Configuraciones de Notificación...</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE config_value = :val");
    
    foreach ($configs as $key => $val) {
        $stmt->execute([':key' => $key, ':val' => $val]);
        echo "Configurada: <b>$key</b><br>";
    }
    echo "<h3 style='color:green'>¡Instalación Completa! Borra este archivo.</h3>";
    echo "<a href='settings.php'>Ir a Configurar</a>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>