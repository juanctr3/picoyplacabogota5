<?php
/**
 * user/deposit.php - Recarga Multi-Pasarela (ePayco + Mercado Pago)
 */
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'];

// --- CONFIGURACIÓN EPAYCO ---
$epayco_public_key = '175f36933ac855a45ffaeecaa8e763e6';
$epayco_test_mode = 'true';
// ----------------------------
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
        h1 { color: #2c3e50; margin-bottom: 5px; }
        .subtitle { color: #7f8c8d; margin-bottom: 30px; }
        
        .price-input-group { margin-bottom: 30px; }
        .price-input { font-size: 2.2em; color: #2d3436; font-weight: 800; border: none; border-bottom: 2px solid #dfe6e9; width: 70%; text-align: center; outline: none; padding: 5px; }
        .price-input:focus { border-bottom-color: #0984e3; }
        
        .methods-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .btn-pay { 
            padding: 15px; border: none; border-radius: 8px; 
            font-size: 1em; font-weight: bold; cursor: pointer; 
            color: white; transition: transform 0.2s, opacity 0.2s;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .btn-pay:hover { transform: translateY(-3px); opacity: 0.9; }
        
        .btn-epayco { background: #f39c12; }
        .btn-mp { background: #009ee3; }
        
        .btn-label { margin-top: 5px; font-size: 0.9em; }
        
        .back-link { display: block; margin-top: 25px; text-decoration: none; color: #636e72; font-size: 0.9em; }
        
        /* Loading Overlay */
        #loading { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 99; justify-content: center; align-items: center; flex-direction: column; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
    <script type="text/javascript" src="https://checkout.epayco.co/checkout.js"></script>
</head>
<body>

    <div id="loading">
        <div class="spinner"></div>
        <p>Procesando solicitud...</p>
    </div>

    <div class="card">
        <h1>Recargar Billetera</h1>
        <p class="subtitle">Elige tu método de pago preferido</p>
        
        <div class="price-input-group">
            <span style="font-size: 1.5em; color: #b2bec3;">$</span>
            <input type="number" id="monto" class="price-input" value="50000" min="5000" step="1000">
            <p style="color:#b2bec3; font-size:0.8em; margin-top: 5px;">PESOS COLOMBIANOS (COP)</p>
        </div>

        <div class="methods-grid">
            <button id="btn-epayco" class="btn-pay btn-epayco">
                <span>⚡</span>
                <span class="btn-label">ePayco / PSE</span>
            </button>

            <button id="btn-mp" class="btn-pay btn-mp">
                <span>�?</span>
                <span class="btn-label">Mercado Pago</span>
            </button>
        </div>
        
        <a href="dashboard.php" class="back-link">Cancelar y Volver</a>
    </div>

    <script>
        // --- 1. LÓGICA EPAYCO ---
        var handler = ePayco.checkout.configure({
            key: '<?= $epayco_public_key ?>',
            test: <?= $epayco_test_mode ?>
        });

        document.getElementById('btn-epayco').addEventListener('click', function() {
            var monto = getMonto();
            if(!monto) return;

            var data = {
                name: "Recarga Saldo",
                description: "Recarga Cuenta #" + <?= $userId ?>,
                invoice: "EP-" + Date.now(),
                currency: "cop",
                amount: monto,
                tax_base: "0", tax: "0", country: "co", lang: "es",
                external: "false",
                email_billing: "<?= $userEmail ?>",
                name_billing: "Anunciante",
                response: window.location.origin + "/user/response.php", 
                confirmation: window.location.origin + "/user/confirmation.php",
                methodsDisable: []
            };
            handler.open(data);
        });

        // --- 2. LÓGICA MERCADO PAGO ---
        document.getElementById('btn-mp').addEventListener('click', function() {
            var monto = getMonto();
            if(!monto) return;

            // Mostrar loading
            document.getElementById('loading').style.display = 'flex';

            // Pedir preferencia al backend
            fetch('create_mp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ monto: monto })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    document.getElementById('loading').style.display = 'none';
                } else {
                    // Redirigir al checkout seguro de Mercado Pago
                    window.location.href = data.init_point; 
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error de conexión. Intenta nuevamente.");
                document.getElementById('loading').style.display = 'none';
            });
        });

        function getMonto() {
            var m = document.getElementById('monto').value;
            if(m < 5000) { alert("Mínimo $5.000 COP"); return false; }
            return m;
        }
    </script>
</body>
</html>