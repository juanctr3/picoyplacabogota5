<?php
/**
 * clases/NotificationService.php - V3.0 (Soporte Contacto + Admin Alert)
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; 

class NotificationService {
    private $pdo;
    private $config;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    private function loadConfig() {
        $stmt = $this->pdo->query("SELECT config_key, config_value FROM system_config");
        $this->config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private function getVal($key) {
        return $this->config[$key] ?? '';
    }

    // Método estándar para usuarios registrados
    public function notify($userId, $type, $data = []) {
        $stmt = $this->pdo->prepare("SELECT email, phone FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return;
        
        // Determinar plantillas... (Igual que antes)
        // ... (Código resumido para brevedad, usa la lógica del paso anterior aquí) ...
        // Te pondré el método notifyCustom que es el importante nuevo:
        
        $this->notifyCustom($type, $data, $user['email'], $user['phone']);
    }

    // NUEVO MÉTODO: Permite enviar a cualquier email/telefono (ej: admin o contacto)
    public function notifyCustom($type, $data, $emailDestino, $phoneDestino = null) {
        $subject = ''; $body = ''; $waMsg = '';

        // Cargar plantillas según tipo
        switch ($type) {
            case 'contact_admin':
                $subject = $this->getVal('tpl_contact_admin_subject');
                $body = $this->getVal('tpl_contact_admin_email');
                $waMsg = $this->getVal('tpl_contact_admin_wa');
                // Si es para admin, usamos el teléfono del admin si existiera en config (no existe aún, usaremos solo email por ahora o podrías agregarlo)
                break;
            case 'contact_user':
                $subject = $this->getVal('tpl_contact_user_subject');
                $body = $this->getVal('tpl_contact_user_email');
                break;
            // ... agregar casos anteriores (register, recharge, etc) copiando la lógica de selección de templates ...
            case 'register_success':
                $subject = $this->getVal('tpl_register_subject');
                $body = $this->getVal('tpl_register_email');
                $waMsg = $this->getVal('tpl_register_wa');
                break;
            // ... Repetir para los demás ...
			case 'ticket_new_user':
    $subject = $this->getVal('tpl_ticket_new_user_subject');
    $body = $this->getVal('tpl_ticket_new_user_email');
    $waMsg = $this->getVal('tpl_ticket_new_user_wa');
    break;

case 'ticket_new_admin':
    $subject = $this->getVal('tpl_ticket_new_admin_subject');
    $body = $this->getVal('tpl_ticket_new_admin_email');
    $waMsg = $this->getVal('tpl_ticket_new_admin_wa');
    break;
        
        case 'ticket_reply_user':
    $subject = $this->getVal('tpl_ticket_reply_user_subject');
    $body = $this->getVal('tpl_ticket_reply_user_email');
    $waMsg = $this->getVal('tpl_ticket_reply_user_wa');
    break;

case 'ticket_reply_admin':
    $subject = $this->getVal('tpl_ticket_reply_admin_subject');
    $body = $this->getVal('tpl_ticket_reply_admin_email');
    $waMsg = $this->getVal('tpl_ticket_reply_admin_wa');
    break;
        
        case 'ticket_closed_user':
    $subject = $this->getVal('tpl_ticket_closed_user_subject');
    $body = $this->getVal('tpl_ticket_closed_user_email');
    $waMsg = $this->getVal('tpl_ticket_closed_user_wa');
    break;
        
        }

        // Reemplazar variables
        $finalBody = strtr($body, $data);
        $finalWa = strtr($waMsg, $data);

        // Enviar
        if ($emailDestino && $body) $this->sendEmail($emailDestino, $subject, $finalBody);
        if ($phoneDestino && $waMsg) $this->sendWhatsApp($phoneDestino, $finalWa);
    }

    // ... (Métodos sendEmail y sendWhatsApp iguales a la versión anterior) ...
    // Asegúrate de copiar los métodos privados sendEmail y sendWhatsApp del código anterior.
    private function sendEmail($to, $subject, $htmlBody) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->getVal('smtp_host');
            $mail->SMTPAuth = true;
            $mail->Username = $this->getVal('smtp_user');
            $mail->Password = $this->getVal('smtp_pass');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->getVal('smtp_port');
            $mail->setFrom($this->getVal('smtp_from'), 'PicoYPlaca Ads');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->send();
        } catch (Exception $e) { error_log("Mail Error: " . $mail->ErrorInfo); }
    }

    private function sendWhatsApp($to, $message) {
        if (empty($to) || empty($this->getVal('wa_secret'))) return;
        $url = "https://whatsapp.smsenlinea.com/api/send/whatsapp";
        $postData = [
            "secret" => $this->getVal('wa_secret'), "account" => $this->getVal('wa_account'),
            "recipient" => $to, "type" => "text", "message" => $message
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
?>