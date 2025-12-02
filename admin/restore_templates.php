<?php
/**
 * admin/restore_templates.php
 * Restaura todas las plantillas de notificación con textos predeterminados profesionales.
 */
require_once 'db_connect.php'; 

// Definición de todas las plantillas necesarias
$templates = [
    // 1. REGISTRO DE USUARIO
    'tpl_register_subject' => '¡Bienvenido a PicoYPlaca Ads!',
    'tpl_register_email'   => 'Hola %name%,<br><br>Gracias por registrarte en nuestra plataforma de anuncios. Tu cuenta ha sido creada exitosamente.<br><br><b>¿Qué sigue?</b><br>Ingresa a tu panel, recarga saldo y crea tu primera campaña hoy mismo.<br><br>Atentamente,<br>El Equipo de PicoYPlaca.',
    'tpl_register_wa'      => 'Hola %name%, bienvenido a PicoYPlaca Ads. Tu cuenta ha sido creada exitosamente. ¡Esperamos ver tus anuncios pronto!',

    // 2. RECUPERACIÓN DE CONTRASEÑA
    'tpl_recovery_subject' => 'Recuperar tu contraseña - PicoYPlaca Ads',
    'tpl_recovery_email'   => 'Hola %name%,<br><br>Hemos recibido una solicitud para restablecer tu contraseña.<br><br>Haz clic en el siguiente enlace para crear una nueva clave:<br><a href="%link%">Restablecer Contraseña</a><br><br>Si no solicitaste esto, puedes ignorar este correo.',
    'tpl_recovery_wa'      => 'Hola %name%, hemos recibido tu solicitud. Recupera tu acceso ingresando aquí: %link%',

    // 3. RECARGA EXITOSA
    'tpl_recharge_subject' => '✅ ¡Recarga Exitosa!',
    'tpl_recharge_email'   => 'Hola %name%,<br><br>Te confirmamos que tu recarga de <b>$%amount% COP</b> ha sido procesada correctamente.<br>Tu nuevo saldo disponible es: <b>$%balance% COP</b>.<br><br>Gracias por tu confianza.',
    'tpl_recharge_wa'      => 'Hola %name%, confirmamos tu recarga de $%amount%. Tu nuevo saldo es $%balance%. ¡Gracias!',

    // 4. ANUNCIO APROBADO
    'tpl_approve_subject'  => '🚀 ¡Tu anuncio está en vivo!',
    'tpl_approve_email'    => 'Hola %name%,<br><br>Buenas noticias: Tu anuncio titulado <b>"%ad_title%"</b> ha pasado el proceso de moderación y ya se encuentra ACTIVO en nuestra plataforma.<br><br>Puedes ver sus estadísticas en tu panel de control.',
    'tpl_approve_wa'       => 'Buenas noticias %name%: Tu anuncio "%ad_title%" fue aprobado y ya está visible para los usuarios. 🚀',

    // 5. ALERTA DE SALDO BAJO
    'tpl_low_balance_subject' => '⚠️ Alerta: Tu saldo está por agotarse',
    'tpl_low_balance_email'   => 'Hola %name%,<br><br>Tu saldo disponible ha bajado a <b>$%balance% COP</b>. <br><br>Te recomendamos realizar una recarga pronto para evitar que tus campañas se detengan automáticamente.<br><br><a href="https://picoyplacabogota.com.co/user/deposit.php">Recargar Ahora</a>',
    'tpl_low_balance_wa'      => 'Alerta %name%: Tu saldo es bajo ($%balance%). Recarga pronto para mantener tus anuncios activos. ⚠️'
];

echo "<h1>Restaurando Plantillas de Notificación...</h1>";
echo "<ul style='font-family:sans-serif; line-height:1.6;'>";

try {
    // Preparamos la sentencia SQL con ON DUPLICATE KEY UPDATE para sobrescribir o crear
    $sql = "INSERT INTO system_config (config_key, config_value) VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE config_value = :val";
    $stmt = $pdo->prepare($sql);

    foreach ($templates as $key => $val) {
        $stmt->execute([':key' => $key, ':val' => $val]);
        echo "<li>✅ Plantilla configurada: <b>$key</b></li>";
    }

    echo "</ul>";
    echo "<h3 style='color:green; font-family:sans-serif;'>¡Proceso completado con éxito!</h3>";
    echo "<p style='font-family:sans-serif;'>Ahora ve al <a href='settings.php'>Panel de Configuración</a> y verás todos los textos cargados.</p>";

} catch (PDOException $e) {
    echo "</ul>";
    echo "<h3 style='color:red; font-family:sans-serif;'>Error: " . $e->getMessage() . "</h3>";
}
?>