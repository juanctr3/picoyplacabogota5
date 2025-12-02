<?php
/**
 * user/deposit.php - Depósito Dinámico (Lee credenciales de BD)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];

// 1. VERIFICAR PERFIL
$stmtProf = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmtProf->execute([':id' => $userId]);
$userProfile = $stmtProf->fetch(PDO::FETCH_ASSOC);

if (empty($userProfile['full_name']) || empty($userProfile['document_number'])) {
    header("Location: profile.php?redirect=deposit.php"); exit;
}

// 2. CARGAR CONFIGURACIÓN GLOBAL
$configs = $pdo->query("SELECT config_key, config_value FROM system_config")->fetchAll(PDO::FETCH_KEY_PAIR);

$min_recharge = (int)($configs['min_recharge_amount'] ?? 5000);
$epayco_active = ($configs['enable_epayco'] ?? '1') === '1';
$mp_active = ($configs['enable_mercadopago'] ?? '1') === '1';

// Credenciales
$epayco_public = $configs['epayco_public_key'] ?? '';
$epayco_test = 'false'; // Producción por defecto

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recargar Saldo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 500px; text-align: center; }
        .price-input { font-size: 2.2em; color: #2d3436; font-weight: 800; border: none; border-bottom: 2px solid #dfe6e9; width: 70%; text-align: center; outline: none; padding: 5px; }
        .methods-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px; }
        .btn-pay { padding: 15px; border: none; border-radius: 8px; font-size: 1em; font-weight: bold; cursor: pointer; color: white; transition: transform 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
        .btn-pay:hover { transform: translateY(-3px); opacity: 0.9; }
        .btn-epayco { background: #f39c12; }
        .btn-mp { background: #009ee3; }
        .mp-logo { height: 24px; fill: white; }
        #loading { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 999; justify-content: center; align-items: center; flex-direction: column; }
        .back-link { display: block; margin-top: 20px; color: #888; text-decoration: none; }
        .full-width { grid-column: span 2; }
        .alert { color: red; font-weight: bold; margin-top: 10px; }
    </style>
    <script type="text/javascript" src="https://checkout.epayco.co/checkout.js"></script>
</head>
<body>
    <div id="loading"><p style="color:#333; font-weight:bold;">Procesando...</p></div>

    <div class="card">
        <h1>Recargar Billetera</h1>
        <p style="color:#777;">Hola, <strong><?= htmlspecialchars($userProfile['full_name']) ?></strong></p>
        
        <div>
            <span style="font-size: 1.5em; color: #b2bec3;">$</span>
            <input type="number" id="monto" class="price-input" value="<?= max(50000, $min_recharge) ?>" min="<?= $min_recharge ?>" step="1000">
            <p style="color:#b2bec3; font-size:0.8em;">Mínimo: $<?= number_format($min_recharge, 0, ',', '.') ?> COP</p>
        </div>

        <?php if (empty($epayco_public) && $epayco_active): ?>
            <p class="alert">⚠️ Error: Falta configurar la Llave Pública de ePayco en el Admin.</p>
        <?php endif; ?>

        <div class="methods-grid">
            <?php if ($epayco_active && !empty($epayco_public)): ?>
            <button id="btn-epayco" class="btn-pay btn-epayco <?= !$mp_active ? 'full-width' : '' ?>">
                <span style="font-size:1.5em">⚡</span>
                <span>ePayco / PSE</span>
            </button>
            <?php endif; ?>

            <?php if ($mp_active): ?>
            <button id="btn-mp" class="btn-pay btn-mp <?= !$epayco_active ? 'full-width' : '' ?>">
                <svg class="mp-logo" viewBox="0 0 50 34" xmlns="http://www.w3.org/2000/svg"><path d="M36.3 14.7h-2.9c-.5 0-.9.4-.9.9v1.9c0 .5.4.9.9.9h2.9c.5 0 .9-.4.9-.9v-1.9c0-.5-.4-.9-.9-.9zM23.8 14.7h-2.9c-.5 0-.9.4-.9.9v1.9c0 .5.4.9.9.9h2.9c.5 0 .9-.4.9-.9v-1.9c0-.5-.4-.9-.9-.9zM11.3 14.7H8.4c-.5 0-.9.4-.9.9v1.9c0 .5.4.9.9.9h2.9c.5 0 .9-.4.9-.9v-1.9c0-.5-.4-.9-.9-.9z"/><path d="M47.6 3.8h-4.4c-1.6-.1-3 .9-3.5 2.4l-5.6 15.9h-3.4l-1.9-5.6c-.5-1.5-1.9-2.5-3.5-2.4h-5.8c-1.6-.1-3 .9-3.5 2.4l-1.9 5.6h-3.4L5.2 6.2C4.7 4.7 3.3 3.7 1.7 3.8H.9c-.5 0-.9.4-.9.9v25.6c0 .5.4.9.9.9h3.8c.5 0 .9-.4.9-.9V14l2.6 7.5c.5 1.5 1.9 2.5 3.5 2.4h5.8c1.6.1 3-.9 3.5-2.4L25 11l3.9 10.4c.5 1.5 1.9 2.5 3.5 2.4h5.8c1.6.1 3-.9 3.5-2.4l2.6-7.5v16.3c0 .5.4.9.9.9h3.8c.5 0 .9-.4.9-.9V4.7c0-.5-.4-.9-.9-.9z"/></svg>
                <span>Mercado Pago</span>
            </button>
            <?php endif; ?>
        </div>
        <a href="dashboard.php" class="back-link">Cancelar</a>
    </div>

    <script>
        const MIN_AMOUNT = <?= $min_recharge ?>;
        
        // Configuración Dinámica de ePayco
        const epaycoKey = '<?= $epayco_public ?>';
        
        let handler = null;
        if(epaycoKey) {
            handler = ePayco.checkout.configure({
                key: epaycoKey,
                test: false
            });
        }

        function getMonto() {
            var m = document.getElementById('monto').value;
            if(m < MIN_AMOUNT) { alert("Mínimo $" + new Intl.NumberFormat().format(MIN_AMOUNT)); return false; }
            return m;
        }

        const btnEpayco = document.getElementById('btn-epayco');
        if (btnEpayco) {
            btnEpayco.addEventListener('click', function() {
                if(!handler) { alert("Error: Llave pública ePayco no configurada."); return; }
                var monto = getMonto();
                if(!monto) return;

                var data = {
                    name: "Recarga Saldo",
                    description: "Recarga usuario #<?= $userId ?>",
                    invoice: "EP-" + Date.now() + "-<?= $userId ?>",
                    currency: "cop",
                    amount: monto,
                    tax_base: "0", tax: "0", country: "co", lang: "es",
                    external: "false",
                    email_billing: "<?= $userProfile['email'] ?>",
                    name_billing: "<?= $userProfile['full_name'] ?>",
                    address_billing: "Bogotá",
                    type_doc_billing: "cc",
                    response: "<?= $baseUrl ?>/response.php",
                    confirmation: "<?= $baseUrl ?>/confirmation.php",
                    methodsDisable: []
                };
                handler.open(data);
            });
        }

        const btnMp = document.getElementById('btn-mp');
        if (btnMp) {
            btnMp.addEventListener('click', function() {
                var monto = getMonto();
                if(!monto) return;
                
                document.getElementById('loading').style.display = 'flex';
                fetch('create_mp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ monto: monto })
                })
                .then(r => r.json())
                .then(data => {
                    if(data.error) { alert(data.error); document.getElementById('loading').style.display = 'none'; }
                    else { window.location.href = data.init_point; }
                })
                .catch(e => {
                    alert("Error de conexión");
                    document.getElementById('loading').style.display = 'none';
                });
            });
        }
    </script>
</body>
</html>