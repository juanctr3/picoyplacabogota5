<?php
/**
 * clases/NotificationService.php
 * Servicio Central de Notificaciones (Email + WhatsApp)
 * Soporta control granular de canales activables desde Admin.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar librerías de Composer
require_once __DIR__ . '/../vendor/autoload.php'; 

class NotificationService {
    private $pdo;
    private $config;
    private $adminEmail;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
        $this->adminEmail = $this->getVal('admin_email');
    }

    /**
     * Carga toda la configuración de la BD en memoria
     */
    private function loadConfig() {
        try {
            $stmt = $this->pdo->query("SELECT config_key, config_value FROM system_config");
            $this->config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $this->config = [];
            error_log("Error cargando config notificaciones: " . $e->getMessage());
        }
    }

    private function getVal($key) {
        return $this->config[$key] ?? '';
    }

    /**
     * Verifica si se debe enviar un mensaje por un canal específico.
     * Regla: (Interruptor Maestro Encendido) Y (Interruptor de la Plantilla Encendido)
     */
    private function shouldSend($prefix, $channel) {
        // 1. Verificar Interruptor Maestro del Canal (Global)
        $globalKey = 'channel_' . $channel . '_active';
        // Si no existe, asumimos activo ('1'). Si existe y es '0', bloqueamos.
        if (isset($this->config[$globalKey]) && $this->config[$globalKey] === '0') {
            return false;
        }

        // 2. Verificar Interruptor de la Plantilla Específica
        $specificKey = $prefix . '_' . $channel . '_active';
        // Debe ser explícitamente '1' para enviar
        return ($this->getVal($specificKey) === '1');
    }

    /**
     * Notificar a un usuario registrado (Busca sus datos en BD)
     */
    public function notify($userId, $type, $data = []) {
        // Obtener datos del usuario
        $stmt = $this->pdo->prepare("SELECT email, phone, full_name FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return;

        // Preparar variables comunes
        $data['%name%'] = $user['full_name'] ?: explode('@', $user['email'])[0];
        $data['%email%'] = $user['email'];
        
        // Mapeo de eventos a prefijos de configuración en BD
        $map = [
            'register_success'  => 'tpl_register',
            'password_recovery' => 'tpl_recovery',
            'recharge_success'  => 'tpl_recharge',
            'ad_approved'       => 'tpl_approve',
            'low_balance'       => 'tpl_low_balance',
            'ad_created'        => 'tpl_ad_created_user',
            'ticket_new_user'   => 'tpl_ticket_new_user',
            'ticket_reply_user' => 'tpl_ticket_reply_user',
            'ticket_closed_user'=> 'tpl_ticket_closed_user'
        ];

        // 1. Enviar notificación al Usuario
        if (isset($map[$type])) {
            $this->dispatch($user['email'], $user['phone'], $map[$type], $data);
        }

        // 2. Enviar Alertas al Administrador (Si aplica)
        if ($type === 'register_success') $this->dispatchAdmin('tpl_admin_new_user', $data);
        if ($type === 'ad_created')       $this->dispatchAdmin('tpl_admin_new_ad', $data);
        if ($type === 'recharge_success') $this->dispatchAdmin('tpl_admin_recharge', $data);
    }

    /**
     * Notificar a destinatarios libres (Contacto, Admin, etc.)
     */
    public function notifyCustom($type, $data, $email, $phone = null) {
        $map = [
            'contact_admin'      => 'tpl_contact_admin',
            'contact_user'       => 'tpl_contact_user',
            'ticket_new_admin'   => 'tpl_ticket_new_admin',
            'ticket_reply_admin' => 'tpl_ticket_reply_admin'
        ];

        if (isset($map[$type])) {
            $this->dispatch($email, $phone, $map[$type], $data);
        }
    }

    /**
     * Motor de envío centralizado
     */
    private function dispatch($emailTo, $phoneTo, $prefix, $data) {
        // A. CANAL EMAIL
        if ($this->shouldSend($prefix, 'email') && !empty($emailTo)) {
            $subject = $this->getVal($prefix . '_subject');
            $body    = $this->getVal($prefix . '_email');
            
            if ($subject && $body) {
                $this->sendEmail($emailTo, $subject, strtr($body, $data));
            }
        }

        // B. CANAL WHATSAPP
        if ($this->shouldSend($prefix, 'wa') && !empty($phoneTo)) {
            $waMsg = $this->getVal($prefix . '_wa');
            
            if ($waMsg) {
                $this->sendWhatsApp($phoneTo, strtr($waMsg, $data));
            }
        }
    }

    /**
     * Envío específico para Admin (Usualmente solo Email)
     */
    private function dispatchAdmin($prefix, $data) {
        if (!$this->adminEmail) return;

        if ($this->shouldSend($prefix, 'email')) {
            $subject = $this->getVal($prefix . '_subject'); // Busca asunto específico
            // Si no hay asunto específico para alertas admin, usa uno genérico
            if (!$subject) $subject = "Alerta del Sistema: " . str_replace('tpl_admin_', '', $prefix);
            
            $body = $this->getVal($prefix . '_email');
            
            if ($body) {
                $this->sendEmail($this->adminEmail, $subject, strtr($body, $data));
            }
        }
    }

    // --- MOTORES DE ENVÍO ---

    private function sendEmail($to, $subject, $htmlBody) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $this->getVal('smtp_host');
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->getVal('smtp_user');
            $mail->Password   = $this->getVal('smtp_pass');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->getVal('smtp_port');
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->getVal('smtp_from'), 'PicoYPlaca Ads');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
        } catch (Exception $e) {
            error_log("Mail Error ($to): " . $mail->ErrorInfo);
        }
    }

    private function sendWhatsApp($to, $message) {
        // Validar credenciales mínimas
        if (empty($this->getVal('wa_secret')) || empty($this->getVal('wa_account'))) return;

        $url = "https://whatsapp.smsenlinea.com/api/send/whatsapp";
        
        $postData = [
            "secret"    => $this->getVal('wa_secret'),
            "account"   => $this->getVal('wa_account'),
            "recipient" => $to,
            "type"      => "text",
            "message"   => $message,
            "priority"  => 1
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false || $httpCode != 200) {
            error_log("WhatsApp Error ($to): HTTP $httpCode - " . curl_error($ch));
        }
        
        curl_close($ch);
    }
}
?>