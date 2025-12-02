<?php
/**
 * admin/fix_templates.php
 * Script de Reparación Total de Plantillas
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db_connect.php'; 

echo "<h1>🛠️ Reparando Sistema de Plantillas...</h1>";

try {
    // PASO 1: Intentar actualizar la base de datos a UTF8MB4 (Soporte Emojis)
    // Si esto falla, el script continuará pero usaremos textos sin emojis por seguridad.
    try {
        $pdo->exec("ALTER TABLE system_config CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p style='color:green'>✅ Base de datos actualizada a formato moderno (utf8mb4).</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️ No se pudo convertir la BD a utf8mb4 (Seguimos en modo compatibilidad).</p>";
    }

    // PASO 2: Borrar plantillas viejas o incompletas para evitar conflictos
    $stmtDel = $pdo->prepare("DELETE FROM system_config WHERE config_key LIKE 'tpl_%'");
    $stmtDel->execute();
    echo "<p style='color:green'>✅ Limpieza de plantillas anteriores completada.</p>";

    // PASO 3: Definir las plantillas (Versión Segura y Completa)
    $templates = [
        // --- REGISTRO ---
        'tpl_register_subject' => 'Bienvenido a PicoYPlaca Ads!',
        'tpl_register_email'   => 'Hola %name%,<br><br>Gracias por registrarte en nuestra plataforma. Tu cuenta ha sido creada exitosamente.<br><br><b>Siguientes pasos:</b><br>1. Ingresa a tu panel.<br>2. Recarga saldo.<br>3. Crea tu primera campana.<br><br>Atentamente,<br>El Equipo.',
        'tpl_register_wa'      => 'Hola %name%, bienvenido a PicoYPlaca Ads. Tu cuenta ha sido creada exitosamente. Esperamos ver tus anuncios pronto.',

        // --- RECUPERACIÓN ---
        'tpl_recovery_subject' => 'Recuperar tu contrasena',
        'tpl_recovery_email'   => 'Hola %name%,<br><br>Hemos recibido una solicitud para restablecer tu contrasena.<br><br>Haz clic aqui para crear una nueva clave:<br><a href="%link%">Restablecer Password</a>',
        'tpl_recovery_wa'      => 'Hola %name%, hemos recibido tu solicitud. Recupera tu acceso ingresando aqui: %link%',

        // --- RECARGA ---
        'tpl_recharge_subject' => 'Recarga Exitosa',
        'tpl_recharge_email'   => 'Hola %name%,<br><br>Confirmamos que tu recarga de <b>$%amount% COP</b> ha sido procesada.<br>Nuevo saldo disponible: <b>$%balance% COP</b>.<br><br>Gracias.',
        'tpl_recharge_wa'      => 'Hola %name%, confirmamos tu recarga de $%amount%. Tu nuevo saldo es $%balance%. Gracias.',

        // --- APROBACIÓN ---
        'tpl_approve_subject'  => 'Tu anuncio esta activo!',
        'tpl_approve_email'    => 'Hola %name%,<br><br>Buenas noticias: Tu anuncio <b>"%ad_title%"</b> ha pasado la moderacion y ya se encuentra ACTIVO.<br><br>Revisa sus estadisticas en tu panel.',
        'tpl_approve_wa'       => 'Buenas noticias %name%: Tu anuncio "%ad_title%" fue aprobado y ya esta visible para los usuarios.',

        // --- SALDO BAJO ---
        'tpl_low_balance_subject' => 'Alerta: Saldo bajo',
        'tpl_low_balance_email'   => 'Hola %name%,<br><br>Tu saldo ha bajado a <b>$%balance% COP</b>. <br><br>Te recomendamos recargar pronto para evitar que tus anuncios se detengan.',
        'tpl_low_balance_wa'      => 'Alerta %name%: Tu saldo es bajo ($%balance%). Recarga pronto para mantener tus anuncios activos.'
    ];

    // PASO 4: Insertar una por una
    $stmtInsert = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (:key, :val)");
    
    foreach ($templates as $key => $val) {
        $stmtInsert->execute([':key' => $key, ':val' => $val]);
        echo "<li>Guardado: <b>$key</b></li>";
    }

    echo "</ul>";
    echo "<h2>🎉 ¡REPARACIÓN FINALIZADA!</h2>";
    echo "<p>Ahora ve a <a href='settings.php'>Configuración</a> y verás todas las plantillas (Email y WhatsApp).</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error Fatal: " . $e->getMessage() . "</h3>";
}
?>