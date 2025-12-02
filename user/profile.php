<?php
/**
 * user/profile.php - Perfil Completo con País
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];

$msg = ''; $msgType = '';

// 1. CARGAR DATOS
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Separar número local para mostrarlo limpio en el input
// Ej: Si phone es +57300123 y country es +57, mostramos solo 300123
$current_country = $user['country_code'] ?? '+57';
$full_phone = $user['phone'] ?? '';
$local_phone = str_replace($current_country, '', $full_phone);

// 2. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $user_type = $_POST['user_type'];
    $doc_number = trim($_POST['document_number']);
    $needs_invoice = isset($_POST['needs_invoice']) ? 1 : 0;
    
    // Teléfono
    $country_code = $_POST['country_code'];
    $phone_local = trim($_POST['phone_local']);
    $phone_complete = $country_code . $phone_local; // Unimos para guardar el completo
    
    // Archivos (Avatar y RUT) - Lógica abreviada igual a la anterior...
    $avatar_path = $user['avatar_url'] ?? ''; 
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $name = 'avt_' . $userId . '_' . time() . '.' . $ext;
        if(move_uploaded_file($_FILES['avatar']['tmp_name'], '../assets/uploads/avatars/' . $name)) 
            $avatar_path = '/assets/uploads/avatars/' . $name;
    }
    
    $rut_path = $user['rut_file'] ?? '';
    if ($needs_invoice && isset($_FILES['rut_file']) && $_FILES['rut_file']['error'] === 0) {
        $name = 'rut_' . $userId . '_' . time() . '.pdf';
        if(move_uploaded_file($_FILES['rut_file']['tmp_name'], '../assets/uploads/documents/' . $name))
            $rut_path = '/assets/uploads/documents/' . $name;
    }

    try {
        $sql = "UPDATE users SET full_name=:nm, phone=:ph, country_code=:cc, user_type=:ut, document_number=:dn, needs_invoice=:ni, rut_file=:rf, avatar_url=:av WHERE id=:id";
        $params = [
            ':nm'=>$full_name, ':ph'=>$phone_complete, ':cc'=>$country_code, 
            ':ut'=>$user_type, ':dn'=>$doc_number, ':ni'=>$needs_invoice, 
            ':rf'=>$rut_path, ':av'=>$avatar_path, ':id'=>$userId
        ];
        
        // Cambio de Clave
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) { throw new Exception("Contraseña muy corta"); }
            $sql = str_replace("WHERE", ", password_hash=:pass WHERE", $sql);
            $params[':pass'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $msg = "Perfil actualizado correctamente.";
        $msgType = "success";
        
        // Recargar para ver cambios
        $stmt->execute([':id'=>$userId]); // Re-ejecutar select es complejo aquí, mejor redirect
        if(isset($_GET['redirect'])) { header("Location: ".$_GET['redirect']); exit; }
        
        // Refrescar variables locales para la vista actual
        $user['country_code'] = $country_code;
        $local_phone = $phone_local; 
        $user['avatar_url'] = $avatar_path;
        $user['rut_file'] = $rut_path;

    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $msgType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #2c3e50; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        
        /* Estilos Teléfono */
        .phone-group { display: flex; gap: 10px; }
        .phone-group select { width: 35%; }
        .phone-group input { width: 65%; }

        .btn-save { width: 100%; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1.1em; margin-top: 15px; }
        .msg { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .avatar-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto; display:block; border:3px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" style="text-decoration:none; color:#34495e; font-weight:bold;">← Volver</a>
        <h1>👤 Editar Perfil</h1>
        <?php if($msg): ?><div class="msg <?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="text-align:center; margin-bottom:20px;">
                <?php if(!empty($user['avatar_url'])): ?>
                    <img src="<?= $user['avatar_url'] ?>" class="avatar-img">
                <?php else: ?>
                    <div class="avatar-img" style="background:#eee; display:flex; align-items:center; justify-content:center; font-size:2em;">👤</div>
                <?php endif; ?>
                <label style="cursor:pointer; color:#3498db; font-size:0.9em; display:block; margin-top:5px;">
                    Cambiar Foto <input type="file" name="avatar" accept="image/*" style="display:none;">
                </label>
            </div>

            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Celular (WhatsApp)</label>
                <div class="phone-group">
                    <select name="country_code">
                        <option value="+57" <?= $current_country=='+57'?'selected':'' ?>>🇨🇴 +57</option>
                        <option value="+1" <?= $current_country=='+1'?'selected':'' ?>>🇺🇸 +1</option>
                        <option value="+34" <?= $current_country=='+34'?'selected':'' ?>>🇪🇸 +34</option>
                        <option value="+52" <?= $current_country=='+52'?'selected':'' ?>>🇲🇽 +52</option>
                        <option value="+54" <?= $current_country=='+54'?'selected':'' ?>>🇦🇷 +54</option>
                        <option value="+56" <?= $current_country=='+56'?'selected':'' ?>>🇨🇱 +56</option>
                        <option value="+51" <?= $current_country=='+51'?'selected':'' ?>>🇵🇪 +51</option>
                        <option value="+593" <?= $current_country=='+593'?'selected':'' ?>>🇪🇨 +593</option>
                        <option value="+58" <?= $current_country=='+58'?'selected':'' ?>>🇻🇪 +58</option>
                        <option value="+55" <?= $current_country=='+55'?'selected':'' ?>>🇧🇷 +55</option>
                    </select>
                    <input type="text" name="phone_local" value="<?= htmlspecialchars($local_phone) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Tipo Persona</label>
                <select name="user_type">
                    <option value="natural" <?= ($user['user_type']??'')=='natural'?'selected':'' ?>>Natural</option>
                    <option value="juridica" <?= ($user['user_type']??'')=='juridica'?'selected':'' ?>>Empresa</option>
                </select>
            </div>

            <div class="form-group">
                <label>Documento (CC/NIT)</label>
                <input type="text" name="document_number" value="<?= htmlspecialchars($user['document_number'] ?? '') ?>" required>
            </div>

            <div class="form-group" style="background:#f9f9f9; padding:15px; border-radius:5px;">
                <label><input type="checkbox" name="needs_invoice" onclick="toggleRut(this)" <?= ($user['needs_invoice']??0)?'checked':'' ?>> Requiero Factura Electrónica</label>
                <div id="rut-box" style="display:<?= ($user['needs_invoice']??0)?'block':'none' ?>; margin-top:10px;">
                    <label>Adjuntar RUT (PDF)</label>
                    <input type="file" name="rut_file" accept="application/pdf">
                    <?php if(!empty($user['rut_file'])): ?><small style="color:green">✅ Archivo actual cargado</small><?php endif; ?>
                </div>
            </div>

            <hr>
            <div class="form-group">
                <label>Nueva Contraseña (Opcional)</label>
                <input type="password" name="new_password" placeholder="Dejar vacío para mantener la actual">
            </div>

            <button type="submit" class="btn-save">💾 Guardar Cambios</button>
        </form>
    </div>
    <script>
        function toggleRut(el) { document.getElementById('rut-box').style.display = el.checked ? 'block' : 'none'; }
    </script>
</body>
</html>