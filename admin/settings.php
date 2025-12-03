<?php
/**
 * admin/settings.php - Configuración Maestra del Sistema
 * Versión Final: Incluye Finanzas, Pagos, Seguridad, Canales y TODAS las Plantillas.
 */

// 1. Configuración de errores y codificación
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();
require_once 'db_connect.php'; 

// 2. Seguridad: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

$message = '';

// 3. Procesar Guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Preparamos la sentencia SQL para guardar o actualizar
        $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE config_value = :val");
        
        // Lista de prefijos y claves permitidas para seguridad
        $allowed_prefixes = [
            'smtp_', 'wa_', 'tpl_', 'min_', 'enable_', 'epayco_', 'mp_', 'recaptcha_', 'channel_'
        ];
        $allowed_keys = ['admin_email', 'low_balance_threshold'];

        foreach ($_POST as $key => $val) {
            $es_valido = false;
            
            // Verificar si la clave empieza con un prefijo permitido
            foreach ($allowed_prefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $es_valido = true;
                    break;
                }
            }
            
            // O si es una clave exacta permitida
            if (in_array($key, $allowed_keys)) {
                $es_valido = true;
            }

            if ($es_valido) {
                $stmt->execute([':val' => trim($val), ':key' => $key]);
            }
        }
        $message = '<div class="success-msg">✅ Configuración guardada correctamente.</div>';
    } catch (PDOException $e) {
        $message = '<div class="error-msg">Error de Base de Datos: ' . $e->getMessage() . '</div>';
    }
}

// 4. Cargar configuración actual
$configs = [];
try {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) { /* Ignorar si tabla vacía */ }

// Función helper para mostrar valores en el HTML
function val($key) { global $configs; return htmlspecialchars($configs[$key] ?? ''); }
// Función helper para checkboxes
function isChecked($key) { global $configs; return (isset($configs[$key]) && $configs[$key] === '1') ? 'checked' : ''; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        h1 { color: #2c3e50; border-bottom: 2px solid #f39c12; padding-bottom: 15px; margin-bottom: 25px; }
        
        /* Navegación por Pestañas */
        .nav-tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .nav-tab { padding: 12px 20px; cursor: pointer; background: #f8f9fa; border-radius: 8px 8px 0 0; font-weight: 600; color: #7f8c8d; transition: 0.2s; border: 1px solid transparent; }
        .nav-tab:hover { background: #e9ecef; }
        .nav-tab.active { background: #3498db; color: white; border-color: #3498db; }
        
        /* Contenido */
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Estilos de Formulario */
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-col { flex: 1; }
        
        label { display: block; margin-top: 15px; font-weight: 600; color: #34495e; font-size: 0.95em; margin-bottom: 5px; }
        input[type="text"], input[type="number"], input[type="password"], textarea { width: 100%; padding: 10px; border: 1px solid #dfe6e9; border-radius: 6px; box-sizing: border-box; font-size: 1em; transition: 0.2s; }
        input:focus, textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
        
        textarea { height: 70px; font-family: monospace; font-size: 0.9em; }
        .email-body { height: 120px; }
        
        .section-title { color: #e67e22; font-size: 1.2em; font-weight: bold; margin: 30px 0 15px 0; padding-bottom: 5px; border-bottom: 1px dashed #ccc; }
        .note { font-size: 0.85em; color: #7f8c8d; margin-top: 3px; }
        .var-tag { background: #e8f6fd; color: #2980b9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.85em; border: 1px solid #d6eaf8; display: inline-block; margin-right: 3px; }
        
        /* Botones y Mensajes */
        .btn-save { position: sticky; bottom: 20px; width: 100%; padding: 15px; background: #2ecc71; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1.1em; font-weight: bold; box-shadow: 0 4px 10px rgba(46,204,113,0.3); transition: 0.2s; z-index: 100; }
        .btn-save:hover { background: #27ae60; transform: translateY(-2px); }
        
        .success-msg { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #28a745; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #dc3545; }
        
        /* Toggle Switch */
        .switch-wrapper { background: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px solid #eee; display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .switch-wrapper label { margin: 0; cursor: pointer; flex: 1; }
        .switch-wrapper input { width: auto; margin: 0; }
        .channel-group { display: flex; gap: 10px; margin-bottom: 10px; }
        .channel-item { flex: 1; background: #fff; border: 1px solid #eee; padding: 8px; border-radius: 5px; display: flex; align-items: center; gap: 8px; }
    </style>
    <script>
        function openTab(name) {
            localStorage.setItem('activeTab', name);
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(name).classList.add('active');
            document.getElementById('btn-' + name).classList.add('active');
        }
        window.onload = function() { openTab(localStorage.getItem('activeTab') || 'general'); };
    </script>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>⚙️ Configuración del Sistema</h1>
            <a href="index.php" style="color:#7f8c8d; text-decoration:none; font-weight:bold;">← Volver</a>
        </div>
        
        <?= $message ?>
        
        <div class="nav-tabs">
            <div id="btn-general" class="nav-tab active" onclick="openTab('general')">General & Pagos</div>
            <div id="btn-connect" class="nav-tab" onclick="openTab('connect')">Conexiones (Email/WA)</div>
            <div id="btn-tpl-user" class="nav-tab" onclick="openTab('tpl-user')">Plantillas Usuarios</div>
            <div id="btn-tpl-ads" class="nav-tab" onclick="openTab('tpl-ads')">Plantillas Anuncios</div>
            <div id="btn-tpl-support" class="nav-tab" onclick="openTab('tpl-support')">Plantillas Soporte</div>
        </div>

        <form method="POST">
            
            <div id="general" class="tab-content active">
                <div class="form-row">
                    <div class="form-col">
                        <h3 class="section-title">💰 Financiero</h3>
                        <label>Monto Mínimo Recarga</label>
                        <input type="number" name="min_recharge_amount" value="<?= val('min_recharge_amount') ?: 5000 ?>">
                        
                        <label>⚠️ Alerta Saldo Bajo</label>
                        <input type="number" name="low_balance_threshold" value="<?= val('low_balance_threshold') ?: 2000 ?>">
                    </div>
                    <div class="form-col">
                        <h3 class="section-title">🛡️ Administración</h3>
                        <label>Email Admin (Para notificaciones)</label>
                        <input type="text" name="admin_email" value="<?= val('admin_email') ?>">
                    </div>
                </div>

                <h3 class="section-title">📊 Precios Base (Subasta)</h3>
                <div class="form-row">
                    <div class="form-col"><label>Mínimo CPC (Click)</label><input type="number" step="0.01" name="min_cpc" value="<?= val('min_cpc') ?: 200 ?>"></div>
                    <div class="form-col"><label>Mínimo CPM (1000 Vistas)</label><input type="number" step="0.01" name="min_cpm" value="<?= val('min_cpm') ?: 5000 ?>"></div>
                </div>

                <h3 class="section-title">💳 Pasarelas de Pago</h3>
                <div class="form-row">
                    <div class="form-col">
                        <div class="switch-wrapper">
                            <input type="hidden" name="enable_epayco" value="0">
                            <input type="checkbox" name="enable_epayco" value="1" <?= isChecked('enable_epayco') ?>>
                            <label>Habilitar ePayco</label>
                        </div>
                        <label>ID Cliente (P_CUST_ID)</label><input type="text" name="epayco_customer_id" value="<?= val('epayco_customer_id') ?>">
                        <label>P_KEY (Privada)</label><input type="password" name="epayco_p_key" value="<?= val('epayco_p_key') ?>">
                        <label>Public Key</label><input type="text" name="epayco_public_key" value="<?= val('epayco_public_key') ?>">
                    </div>
                    <div class="form-col">
                        <div class="switch-wrapper">
                            <input type="hidden" name="enable_mercadopago" value="0">
                            <input type="checkbox" name="enable_mercadopago" value="1" <?= isChecked('enable_mercadopago') ?>>
                            <label>Habilitar Mercado Pago</label>
                        </div>
                        <label>Access Token (Producción)</label><input type="password" name="mp_access_token" value="<?= val('mp_access_token') ?>">
                    </div>
                </div>
                
                <h3 class="section-title">🔒 Seguridad (Captcha)</h3>
                <div class="form-row">
                    <div class="form-col"><label>Site Key</label><input type="text" name="recaptcha_site_key" value="<?= val('recaptcha_site_key') ?>"></div>
                    <div class="form-col"><label>Secret Key</label><input type="password" name="recaptcha_secret_key" value="<?= val('recaptcha_secret_key') ?>"></div>
                </div>
            </div>

            <div id="connect" class="tab-content">
                <h3 class="section-title">🎛️ Control Maestro de Canales</h3>
                <div class="form-row">
                    <div class="form-col switch-wrapper">
                        <input type="hidden" name="channel_email_active" value="0">
                        <input type="checkbox" name="channel_email_active" value="1" <?= isChecked('channel_email_active') ?>>
                        <label>📤 Activar envío de Emails (Global)</label>
                    </div>
                    <div class="form-col switch-wrapper">
                        <input type="hidden" name="channel_wa_active" value="0">
                        <input type="checkbox" name="channel_wa_active" value="1" <?= isChecked('channel_wa_active') ?>>
                        <label>📱 Activar envío de WhatsApp (Global)</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <h3 class="section-title">📧 SMTP (Correo)</h3>
                        <label>Host</label><input type="text" name="smtp_host" value="<?= val('smtp_host') ?>">
                        <label>Usuario</label><input type="text" name="smtp_user" value="<?= val('smtp_user') ?>">
                        <label>Contraseña</label><input type="password" name="smtp_pass" value="<?= val('smtp_pass') ?>">
                        <label>Puerto</label><input type="text" name="smtp_port" value="<?= val('smtp_port') ?: 587 ?>">
                        <label>Email Remitente</label><input type="text" name="smtp_from" value="<?= val('smtp_from') ?>">
                    </div>
                    <div class="form-col">
                        <h3 class="section-title">📱 WhatsApp API</h3>
                        <label>API Secret</label><input type="password" name="wa_secret" value="<?= val('wa_secret') ?>">
                        <label>Account ID</label><input type="text" name="wa_account" value="<?= val('wa_account') ?>">
                    </div>
                </div>
            </div>

            <div id="tpl-user" class="tab-content">
                
                <div class="form-section">
                    <h3 class="section-title">1. Nuevo Registro Exitoso</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span></div>
                    <label>Asunto Email</label><input type="text" name="tpl_register_subject" value="<?= val('tpl_register_subject') ?>">
                    
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_register_email_active" value="1" <?= isChecked('tpl_register_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_register_wa_active" value="1" <?= isChecked('tpl_register_wa_active') ?>> WhatsApp</div>
                    </div>
                    
                    <label>Cuerpo Email</label><textarea class="email-body" name="tpl_register_email"><?= val('tpl_register_email') ?></textarea>
                    <label>Mensaje WhatsApp</label><textarea name="tpl_register_wa"><?= val('tpl_register_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <h3 class="section-title">2. Recuperación de Contraseña</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%link%</span></div>
                    
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_recovery_email_active" value="1" <?= isChecked('tpl_recovery_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_recovery_wa_active" value="1" <?= isChecked('tpl_recovery_wa_active') ?>> WhatsApp</div>
                    </div>
                    
                    <label>Asunto</label><input type="text" name="tpl_recovery_subject" value="<?= val('tpl_recovery_subject') ?>">
                    <label>Email</label><textarea class="email-body" name="tpl_recovery_email"><?= val('tpl_recovery_email') ?></textarea>
                    <label>WhatsApp</label><textarea name="tpl_recovery_wa"><?= val('tpl_recovery_wa') ?></textarea>
                </div>
            </div>

            <div id="tpl-ads" class="tab-content">
                
                <div class="form-section">
                    <h3 class="section-title">3. Recarga Exitosa</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%amount%</span>, <span class="var-tag">%balance%</span></div>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_recharge_email_active" value="1" <?= isChecked('tpl_recharge_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_recharge_wa_active" value="1" <?= isChecked('tpl_recharge_wa_active') ?>> WhatsApp</div>
                    </div>
                    <label>Asunto</label><input type="text" name="tpl_recharge_subject" value="<?= val('tpl_recharge_subject') ?>">
                    <label>Email</label><textarea class="email-body" name="tpl_recharge_email"><?= val('tpl_recharge_email') ?></textarea>
                    <label>WhatsApp</label><textarea name="tpl_recharge_wa"><?= val('tpl_recharge_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <h3 class="section-title">4. Anuncio Aprobado</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%ad_title%</span></div>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_approve_email_active" value="1" <?= isChecked('tpl_approve_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_approve_wa_active" value="1" <?= isChecked('tpl_approve_wa_active') ?>> WhatsApp</div>
                    </div>
                    <label>Asunto</label><input type="text" name="tpl_approve_subject" value="<?= val('tpl_approve_subject') ?>">
                    <label>Email</label><textarea class="email-body" name="tpl_approve_email"><?= val('tpl_approve_email') ?></textarea>
                    <label>WhatsApp</label><textarea name="tpl_approve_wa"><?= val('tpl_approve_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <h3 class="section-title">5. Alerta Saldo Bajo</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%balance%</span></div>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_low_balance_email_active" value="1" <?= isChecked('tpl_low_balance_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_low_balance_wa_active" value="1" <?= isChecked('tpl_low_balance_wa_active') ?>> WhatsApp</div>
                    </div>
                    <label>Asunto</label><input type="text" name="tpl_low_balance_subject" value="<?= val('tpl_low_balance_subject') ?>">
                    <label>Email</label><textarea class="email-body" name="tpl_low_balance_email"><?= val('tpl_low_balance_email') ?></textarea>
                    <label>WhatsApp</label><textarea name="tpl_low_balance_wa"><?= val('tpl_low_balance_wa') ?></textarea>
                </div>
            </div>

            <div id="tpl-support" class="tab-content">
                
                <h3 class="section-title">🔔 Alertas al Admin</h3>
                
                <div class="form-section">
                    <strong>Formulario de Contacto Web</strong> (Vars: %name%, %email%, %message%)
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_contact_admin_email_active" value="1" <?= isChecked('tpl_contact_admin_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_contact_admin_wa_active" value="1" <?= isChecked('tpl_contact_admin_wa_active') ?>> WhatsApp</div>
                    </div>
                    <input type="text" name="tpl_contact_admin_subject" value="<?= val('tpl_contact_admin_subject') ?>" placeholder="Asunto">
                    <textarea class="email-body" name="tpl_contact_admin_email" placeholder="Cuerpo Email"><?= val('tpl_contact_admin_email') ?></textarea>
                    <textarea name="tpl_contact_admin_wa" placeholder="Mensaje WA"><?= val('tpl_contact_admin_wa') ?></textarea>
                </div>
                
                <hr>

                <div class="form-section">
                    <strong>Confirmación Contacto (Al Usuario)</strong>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_contact_user_email_active" value="1" <?= isChecked('tpl_contact_user_email_active') ?>> Email</div>
                    </div>
                    <input type="text" name="tpl_contact_user_subject" value="<?= val('tpl_contact_user_subject') ?>" placeholder="Asunto">
                    <textarea class="email-body" name="tpl_contact_user_email"><?= val('tpl_contact_user_email') ?></textarea>
                </div>

                <hr>
                
                <h3 class="section-title">🎫 Tickets de Soporte</h3>

                <div class="form-section">
                    <strong>Nuevo Ticket (Al Admin)</strong>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_new_admin_email_active" value="1" <?= isChecked('tpl_ticket_new_admin_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_new_admin_wa_active" value="1" <?= isChecked('tpl_ticket_new_admin_wa_active') ?>> WhatsApp</div>
                    </div>
                    <input type="text" name="tpl_ticket_new_admin_subject" value="<?= val('tpl_ticket_new_admin_subject') ?>">
                    <textarea class="email-body" name="tpl_ticket_new_admin_email"><?= val('tpl_ticket_new_admin_email') ?></textarea>
                    <textarea name="tpl_ticket_new_admin_wa"><?= val('tpl_ticket_new_admin_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <strong>Respuesta de Usuario (Al Admin)</strong>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_reply_admin_email_active" value="1" <?= isChecked('tpl_ticket_reply_admin_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_reply_admin_wa_active" value="1" <?= isChecked('tpl_ticket_reply_admin_wa_active') ?>> WhatsApp</div>
                    </div>
                    <input type="text" name="tpl_ticket_reply_admin_subject" value="<?= val('tpl_ticket_reply_admin_subject') ?>">
                    <textarea class="email-body" name="tpl_ticket_reply_admin_email"><?= val('tpl_ticket_reply_admin_email') ?></textarea>
                    <textarea name="tpl_ticket_reply_admin_wa"><?= val('tpl_ticket_reply_admin_wa') ?></textarea>
                </div>

                <hr>

                <div class="form-section">
                    <strong>Nuevo Ticket (Al Usuario - Confirmación)</strong>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_new_user_email_active" value="1" <?= isChecked('tpl_ticket_new_user_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_new_user_wa_active" value="1" <?= isChecked('tpl_ticket_new_user_wa_active') ?>> WhatsApp</div>
                    </div>
                    <input type="text" name="tpl_ticket_new_user_subject" value="<?= val('tpl_ticket_new_user_subject') ?>">
                    <textarea class="email-body" name="tpl_ticket_new_user_email"><?= val('tpl_ticket_new_user_email') ?></textarea>
                    <textarea name="tpl_ticket_new_user_wa"><?= val('tpl_ticket_new_user_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <strong>Respuesta de Soporte (Al Usuario)</strong>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_reply_user_email_active" value="1" <?= isChecked('tpl_ticket_reply_user_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_reply_user_wa_active" value="1" <?= isChecked('tpl_ticket_reply_user_wa_active') ?>> WhatsApp</div>
                    </div>
                    <input type="text" name="tpl_ticket_reply_user_subject" value="<?= val('tpl_ticket_reply_user_subject') ?>">
                    <textarea class="email-body" name="tpl_ticket_reply_user_email"><?= val('tpl_ticket_reply_user_email') ?></textarea>
                    <textarea name="tpl_ticket_reply_user_wa"><?= val('tpl_ticket_reply_user_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <strong>Ticket Cerrado (Al Usuario)</strong>
                    <div class="channel-group">
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_closed_user_email_active" value="1" <?= isChecked('tpl_ticket_closed_user_email_active') ?>> Email</div>
                        <div class="channel-item"><input type="checkbox" name="tpl_ticket_closed_user_wa_active" value="1" <?= isChecked('tpl_ticket_closed_user_wa_active') ?>> WhatsApp</div>
                    </div>
                    <input type="text" name="tpl_ticket_closed_user_subject" value="<?= val('tpl_ticket_closed_user_subject') ?>">
                    <textarea class="email-body" name="tpl_ticket_closed_user_email"><?= val('tpl_ticket_closed_user_email') ?></textarea>
                    <textarea name="tpl_ticket_closed_user_wa"><?= val('tpl_ticket_closed_user_wa') ?></textarea>
                </div>

            </div>

            <button type="submit" class="btn-save">💾 GUARDAR CONFIGURACIÓN COMPLETA</button>
        </form>
    </div>
</body>
</html>