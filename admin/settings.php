<?php
/**
 * admin/settings.php - Configuración Total del Sistema (Versión Final Completa)
 */
session_start();
require_once 'db_connect.php'; 

// Seguridad: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

$message = '';

// --- PROCESAR GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Preparamos la sentencia para guardar/actualizar
        $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE config_value = :val");
        
        foreach ($_POST as $key => $val) {
            // Filtro de seguridad: Solo guardamos llaves conocidas
            if (
                strpos($key, 'smtp_') === 0 || 
                strpos($key, 'wa_') === 0 || 
                strpos($key, 'tpl_') === 0 || // Guarda todas las plantillas
                strpos($key, 'min_') === 0 || 
                strpos($key, 'enable_') === 0 || // Permite guardar los checkboxes
                strpos($key, 'epayco_') === 0 || // Permite guardar credenciales ePayco
                strpos($key, 'mp_') === 0 ||     // Permite guardar credenciales MP
                strpos($key, 'recaptcha_') === 0 || // Permite guardar credenciales Captcha
                $key === 'admin_email' || 
                $key === 'low_balance_threshold'
            ) {
                $stmt->execute([':val' => trim($val), ':key' => $key]);
            }
        }
        $message = '<div class="success-msg">✅ Configuración guardada correctamente.</div>';
    } catch (PDOException $e) {
        $message = '<div class="error-msg">Error al guardar: ' . $e->getMessage() . '</div>';
    }
}

// --- CARGAR DATOS ---
// Obtenemos todas las configuraciones existentes en un array asociativo
$configs = [];
try {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) { /* Fallo silencioso si la tabla no existe aún */ }

// Helper para sacar valores seguros (evita errores undefined index)
function val($key) { global $configs; return htmlspecialchars($configs[$key] ?? ''); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración del Sistema</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; }
        .container { max-width: 950px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        h1 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; font-size: 1.8em; }
        
        /* Pestañas */
        .nav-tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .nav-tab { padding: 12px 20px; cursor: pointer; background: #f8f9fa; border-radius: 8px 8px 0 0; font-weight: 600; color: #7f8c8d; transition: 0.2s; }
        .nav-tab:hover { background: #e9ecef; }
        .nav-tab.active { background: #3498db; color: white; transform: translateY(2px); }
        
        /* Contenido Pestañas */
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Formularios */
        .form-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px dashed #eee; }
        .form-section:last-child { border-bottom: none; }
        .section-title { color: #e67e22; font-size: 1.1em; font-weight: bold; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        label { display: block; margin-top: 15px; font-weight: 600; color: #34495e; font-size: 0.95em; }
        input[type="text"], input[type="number"], input[type="password"], textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #dfe6e9; border-radius: 6px; box-sizing: border-box; font-size: 1em; transition: 0.2s; }
        input:focus, textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
        
        textarea { height: 80px; font-family: 'Consolas', monospace; font-size: 0.9em; line-height: 1.4; }
        textarea.email-body { height: 120px; }
        
        .note { font-size: 0.85em; color: #7f8c8d; margin-top: 5px; }
        .var-tag { background: #e8f6fd; color: #2980b9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.9em; border: 1px solid #d6eaf8; }
        
        .btn-save { position: sticky; bottom: 20px; width: 100%; padding: 15px; background: #2ecc71; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1.1em; font-weight: bold; box-shadow: 0 4px 6px rgba(46,204,113,0.2); transition: 0.2s; }
        .btn-save:hover { background: #27ae60; transform: translateY(-2px); }
        
        .success-msg { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #28a745; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #dc3545; }
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
            <h1>⚙️ Configuración Global</h1>
            <a href="index.php" style="color:#7f8c8d; text-decoration:none;">← Volver al Panel</a>
        </div>
        
        <?= $message ?>
        
        <div class="nav-tabs">
            <div id="btn-general" class="nav-tab active" onclick="openTab('general')">General</div>
            <div id="btn-payments" class="nav-tab" onclick="openTab('payments')">Pagos</div>
            <div id="btn-security" class="nav-tab" onclick="openTab('security')">Seguridad</div>
            <div id="btn-email" class="nav-tab" onclick="openTab('email')">Email/WA</div>
            <div id="btn-tpl-user" class="nav-tab" onclick="openTab('tpl-user')">Plantillas Usuarios</div>
            <div id="btn-tpl-ads" class="nav-tab" onclick="openTab('tpl-ads')">Plantillas Anuncios</div>
        </div>

        <form method="POST">
            
            <div id="general" class="tab-content active">
                <div class="form-section">
                    <div class="section-title">💰 Financiero</div>
                    <div style="display:flex; gap:20px;">
                        <div style="flex:1">
                            <label>Monto Mínimo Recarga</label>
                            <input type="number" name="min_recharge_amount" value="<?= val('min_recharge_amount') ?: 5000 ?>">
                        </div>
                        <div style="flex:1">
                            <label>⚠️ Alerta Saldo Bajo</label>
                            <input type="number" name="low_balance_threshold" value="<?= val('low_balance_threshold') ?: 2000 ?>">
                            <div class="note">Enviar alerta cuando saldo < X</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">📊 Precios Base (Subasta)</div>
                    <div style="display:flex; gap:20px;">
                        <div style="flex:1">
                            <label>Mínimo CPC (Click)</label>
                            <input type="number" step="0.01" name="min_cpc" value="<?= val('min_cpc') ?: 200 ?>">
                        </div>
                        <div style="flex:1">
                            <label>Mínimo CPM (1000 Vistas)</label>
                            <input type="number" step="0.01" name="min_cpm" value="<?= val('min_cpm') ?: 5000 ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">🛡️ Administración</div>
                    <label>Email Admin (Notificaciones)</label>
                    <input type="text" name="admin_email" value="<?= val('admin_email') ?>">
                </div>
            </div>

            <div id="payments" class="tab-content">
                <div class="form-section">
                    <h3 class="section-title">ePayco</h3>
                    <label style="display:flex; align-items:center; gap:10px; margin-bottom:15px; cursor:pointer; background:#f9f9f9; padding:10px; border-radius:5px;">
                        <input type="hidden" name="enable_epayco" value="0">
                        <input type="checkbox" name="enable_epayco" value="1" <?= val('enable_epayco')=='1'?'checked':'' ?>>
                        Habilitar ePayco
                    </label>
                    
                    <label>P_CUST_ID_CLIENTE (ID Cliente)</label>
                    <input type="text" name="epayco_customer_id" value="<?= val('epayco_customer_id') ?>">
                    
                    <label>PUBLIC_KEY (Llave Pública)</label>
                    <input type="text" name="epayco_public_key" value="<?= val('epayco_public_key') ?>">
                    
                    <label>P_KEY (Llave Privada/Segura)</label>
                    <input type="password" name="epayco_p_key" value="<?= val('epayco_p_key') ?>">
                </div>

                <div class="form-section">
                    <h3 class="section-title">Mercado Pago</h3>
                    <label style="display:flex; align-items:center; gap:10px; margin-bottom:15px; cursor:pointer; background:#f9f9f9; padding:10px; border-radius:5px;">
                        <input type="hidden" name="enable_mercadopago" value="0">
                        <input type="checkbox" name="enable_mercadopago" value="1" <?= val('enable_mercadopago')=='1'?'checked':'' ?>>
                        Habilitar Mercado Pago
                    </label>
                    
                    <label>ACCESS_TOKEN (Producción)</label>
                    <input type="password" name="mp_access_token" value="<?= val('mp_access_token') ?>">
                </div>
            </div>

            <div id="security" class="tab-content">
                <div class="form-section">
                    <h3 class="section-title">Google ReCaptcha v2</h3>
                    <p class="note">Obtén tus claves en <a href="https://www.google.com/recaptcha/admin" target="_blank">Google ReCaptcha Admin</a>.</p>
                    <label>Site Key (Clave del sitio)</label>
                    <input type="text" name="recaptcha_site_key" value="<?= val('recaptcha_site_key') ?>">
                    <label>Secret Key (Clave secreta)</label>
                    <input type="password" name="recaptcha_secret_key" value="<?= val('recaptcha_secret_key') ?>">
                </div>
            </div>

            <div id="email" class="tab-content">
                <div class="form-section">
                    <div class="section-title">📧 Credenciales SMTP (Amazon SES / Gmail / Otro)</div>
                    <label>Host SMTP</label>
                    <input type="text" name="smtp_host" value="<?= val('smtp_host') ?>" placeholder="ej: email-smtp.us-east-1.amazonaws.com">
                    
                    <div style="display:flex; gap:20px;">
                        <div style="flex:1">
                            <label>Usuario SMTP</label>
                            <input type="text" name="smtp_user" value="<?= val('smtp_user') ?>">
                        </div>
                        <div style="flex:1">
                            <label>Contraseña SMTP</label>
                            <input type="password" name="smtp_pass" value="<?= val('smtp_pass') ?>">
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:20px;">
                        <div style="flex:1">
                            <label>Puerto</label>
                            <input type="text" name="smtp_port" value="<?= val('smtp_port') ?: 587 ?>">
                        </div>
                        <div style="flex:2">
                            <label>Email Remitente (From)</label>
                            <input type="text" name="smtp_from" value="<?= val('smtp_from') ?>" placeholder="info@tuempresa.com">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">📱 Credenciales smsenlinea.com</div>
                    <label>API Secret</label>
                    <input type="password" name="wa_secret" value="<?= val('wa_secret') ?>">
                    
                    <label>Account ID (Unique ID)</label>
                    <input type="text" name="wa_account" value="<?= val('wa_account') ?>">
                </div>
            </div>

            <div id="tpl-user" class="tab-content">
                <div class="form-section">
                    <h3 class="section-title">1. Nuevo Registro Exitoso</h3>
                    <div class="note">Variables: <span class="var-tag">%name%</span></div>
                    <label>Asunto Email</label>
                    <input type="text" name="tpl_register_subject" value="<?= val('tpl_register_subject') ?>">
                    <label>Cuerpo Email (HTML)</label>
                    <textarea class="email-body" name="tpl_register_email"><?= val('tpl_register_email') ?></textarea>
                    <label>Mensaje WhatsApp</label>
                    <textarea name="tpl_register_wa"><?= val('tpl_register_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <h3 class="section-title">2. Recuperación de Contraseña</h3>
                    <div class="note">Variables: <span class="var-tag">%name%</span>, <span class="var-tag">%link%</span></div>
                    <label>Asunto Email</label>
                    <input type="text" name="tpl_recovery_subject" value="<?= val('tpl_recovery_subject') ?>">
                    <label>Cuerpo Email (HTML)</label>
                    <textarea class="email-body" name="tpl_recovery_email"><?= val('tpl_recovery_email') ?></textarea>
                    <label>Mensaje WhatsApp</label>
                    <textarea name="tpl_recovery_wa"><?= val('tpl_recovery_wa') ?></textarea>
                </div>
            </div>

            <div id="tpl-ads" class="tab-content">
                <div class="form-section">
                    <h3 class="section-title">3. Recarga Exitosa</h3>
                    <div class="note">Variables: <span class="var-tag">%name%</span>, <span class="var-tag">%amount%</span>, <span class="var-tag">%balance%</span></div>
                    <label>Asunto Email</label>
                    <input type="text" name="tpl_recharge_subject" value="<?= val('tpl_recharge_subject') ?>">
                    <label>Cuerpo Email</label>
                    <textarea class="email-body" name="tpl_recharge_email"><?= val('tpl_recharge_email') ?></textarea>
                    <label>Mensaje WhatsApp</label>
                    <textarea name="tpl_recharge_wa"><?= val('tpl_recharge_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <h3 class="section-title">4. Anuncio Aprobado</h3>
                    <div class="note">Variables: <span class="var-tag">%name%</span>, <span class="var-tag">%ad_title%</span></div>
                    <label>Asunto Email</label>
                    <input type="text" name="tpl_approve_subject" value="<?= val('tpl_approve_subject') ?>">
                    <label>Cuerpo Email</label>
                    <textarea class="email-body" name="tpl_approve_email"><?= val('tpl_approve_email') ?></textarea>
                    <label>Mensaje WhatsApp</label>
                    <textarea name="tpl_approve_wa"><?= val('tpl_approve_wa') ?></textarea>
                </div>

                <div class="form-section">
                    <h3 class="section-title">5. Alerta de Saldo Bajo</h3>
                    <div class="note">Variables: <span class="var-tag">%name%</span>, <span class="var-tag">%balance%</span></div>
                    <label>Asunto Email</label>
                    <input type="text" name="tpl_low_balance_subject" value="<?= val('tpl_low_balance_subject') ?>">
                    <label>Cuerpo Email</label>
                    <textarea class="email-body" name="tpl_low_balance_email"><?= val('tpl_low_balance_email') ?></textarea>
                    <label>Mensaje WhatsApp</label>
                    <textarea name="tpl_low_balance_wa"><?= val('tpl_low_balance_wa') ?></textarea>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">6. Formulario de Contacto (Al Admin)</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%email%</span>, <span class="var-tag">%message%</span></div>
                    <label>Asunto Email Admin</label><input type="text" name="tpl_contact_admin_subject" value="<?= val('tpl_contact_admin_subject') ?>">
                    <label>Cuerpo Email Admin</label><textarea class="email-body" name="tpl_contact_admin_email"><?= val('tpl_contact_admin_email') ?></textarea>
                    <label>WhatsApp Admin</label><textarea name="tpl_contact_admin_wa"><?= val('tpl_contact_admin_wa') ?></textarea>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">7. Confirmación Contacto (Al Usuario)</h3>
                    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%message%</span></div>
                    <label>Asunto Email Usuario</label><input type="text" name="tpl_contact_user_subject" value="<?= val('tpl_contact_user_subject') ?>">
                    <label>Cuerpo Email Usuario</label><textarea class="email-body" name="tpl_contact_user_email"><?= val('tpl_contact_user_email') ?></textarea>
                </div>
                
                <div class="form-section">
    <h3 class="section-title">8. Nuevo Ticket (Al Usuario)</h3>
    <div class="note">Vars: %id%, %subject%, %name%</div>
    <label>Asunto</label><input type="text" name="tpl_ticket_new_user_subject" value="<?= val('tpl_ticket_new_user_subject') ?>">
    <label>Email</label><textarea class="email-body" name="tpl_ticket_new_user_email"><?= val('tpl_ticket_new_user_email') ?></textarea>
    <label>WhatsApp</label><textarea name="tpl_ticket_new_user_wa"><?= val('tpl_ticket_new_user_wa') ?></textarea>
</div>

<div class="form-section">
    <h3 class="section-title">9. Nuevo Ticket (Al Admin)</h3>
    <div class="note">Vars: %id%, %name%, %email%, %subject%, %message%</div>
    <label>Asunto</label><input type="text" name="tpl_ticket_new_admin_subject" value="<?= val('tpl_ticket_new_admin_subject') ?>">
    <label>Email</label><textarea class="email-body" name="tpl_ticket_new_admin_email"><?= val('tpl_ticket_new_admin_email') ?></textarea>
    <label>WhatsApp</label><textarea name="tpl_ticket_new_admin_wa"><?= val('tpl_ticket_new_admin_wa') ?></textarea>
</div>
    
    <div class="form-section">
    <h3 class="section-title">10. Respuesta de Soporte (Al Usuario)</h3>
    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%id%</span>, <span class="var-tag">%reply%</span></div>
    <label>Asunto</label><input type="text" name="tpl_ticket_reply_user_subject" value="<?= val('tpl_ticket_reply_user_subject') ?>">
    <label>Email</label><textarea class="email-body" name="tpl_ticket_reply_user_email"><?= val('tpl_ticket_reply_user_email') ?></textarea>
    <label>WhatsApp</label><textarea name="tpl_ticket_reply_user_wa"><?= val('tpl_ticket_reply_user_wa') ?></textarea>
</div>

<div class="form-section">
    <h3 class="section-title">11. Respuesta de Usuario (Al Admin)</h3>
    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%id%</span>, <span class="var-tag">%reply%</span></div>
    <label>Asunto</label><input type="text" name="tpl_ticket_reply_admin_subject" value="<?= val('tpl_ticket_reply_admin_subject') ?>">
    <label>Email</label><textarea class="email-body" name="tpl_ticket_reply_admin_email"><?= val('tpl_ticket_reply_admin_email') ?></textarea>
    <label>WhatsApp</label><textarea name="tpl_ticket_reply_admin_wa"><?= val('tpl_ticket_reply_admin_wa') ?></textarea>
</div>
    
    <div class="form-section">
    <h3 class="section-title">12. Ticket Cerrado (Al Usuario)</h3>
    <div class="note">Vars: <span class="var-tag">%name%</span>, <span class="var-tag">%id%</span></div>
    <label>Asunto Email</label><input type="text" name="tpl_ticket_closed_user_subject" value="<?= val('tpl_ticket_closed_user_subject') ?>">
    <label>Email</label><textarea class="email-body" name="tpl_ticket_closed_user_email"><?= val('tpl_ticket_closed_user_email') ?></textarea>
    <label>WhatsApp</label><textarea name="tpl_ticket_closed_user_wa"><?= val('tpl_ticket_closed_user_wa') ?></textarea>
</div>
            </div>
                    
                    

            <button type="submit" class="btn-save">💾 GUARDAR TODA LA CONFIGURACIÓN</button>
        </form>
    </div>
</body>
</html>