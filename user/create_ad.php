<?php
/**
 * user/create_ad.php - Formulario de Anuncio
 * Limpio de caracteres especiales y con depuración activa.
 */

// 1. ACTIVAR ERRORES (Para ver si hay fallos de sintaxis o BD)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; 
require_once '../config-ciudades.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php"); exit;
}

$userId = $_SESSION['user_id']; 
global $ciudades;

// --- 1. VERIFICACIÓN DE PERFIL COMPLETO ---
// Si faltan las columnas en la BD, esto lanzará una excepción visible ahora.
try {
    $stmtProf = $pdo->prepare("SELECT full_name, document_number FROM users WHERE id = :id");
    $stmtProf->execute([':id' => $userId]);
    $prof = $stmtProf->fetch();
    
    if (empty($prof['full_name']) || empty($prof['document_number'])) {
        header("Location: profile.php?redirect=create_ad.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error de Base de Datos (Perfil): " . $e->getMessage() . "<br>Asegúrate de haber ejecutado el SQL para agregar 'full_name' y 'document_number' a la tabla 'users'.");
}

// --- 2. OBTENER PRECIOS MÍNIMOS DEL ADMIN ---
$min_cpc = 200;
$min_cpm = 5000;
try {
    $stmtC = $pdo->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('min_cpc', 'min_cpm')");
    $sysConfig = $stmtC->fetchAll(PDO::FETCH_KEY_PAIR);
    if(isset($sysConfig['min_cpc'])) $min_cpc = (float)$sysConfig['min_cpc'];
    if(isset($sysConfig['min_cpm'])) $min_cpm = (float)$sysConfig['min_cpm'];
} catch(Exception $e) {}

$ciudades_disponibles = [];
foreach ($ciudades as $slug => $data) {
    if ($slug !== 'rotaciones_base') $ciudades_disponibles[$slug] = $data['nombre'];
}

$mensaje_estado = '';
$es_error = false;
$banner_id = $_GET['id'] ?? null;
$datos_form = [];
$modo_edicion = false;
$banner_ciudades_array = []; 

// CARGAR DATOS SI ES EDICIÓN
if ($banner_id) {
    $modo_edicion = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id AND user_id = :userId");
        $stmt->execute([':id' => $banner_id, ':userId' => $userId]);
        $datos_form = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos_form) {
            $mensaje_estado = "Banner no encontrado.";
            $es_error = true;
        } else {
            $banner_ciudades_array = explode(',', $datos_form['city_slugs']);
        }
    } catch (PDOException $e) {
        $mensaje_estado = "Error: " . $e->getMessage();
        $es_error = true;
    }
}

// VALORES POR DEFECTO
if (!$modo_edicion) {
    $datos_form = [
        'posicion' => 'top',
        'max_impresiones' => 50000,
        'max_clicks' => 500,
        'offer_cpc' => $min_cpc,
        'offer_cpm' => $min_cpm,
        'freq_max_views' => 3, 
        'freq_reset_hours' => 6,
        'logo_url' => ''
    ];
}

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Manejo de Imagen
    $file_path_db = $datos_form['logo_url'] ?? ''; 
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
        $safe_name = uniqid('banner_', true) . '.' . $ext;
        $upload_dir = '../assets/uploads/banners/';
        
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $safe_name)) {
            $file_path_db = '/assets/uploads/banners/' . $safe_name;
        } else {
            $mensaje_estado = "Error al subir imagen.";
            $es_error = true;
        }
    }

    if (!$es_error) {
        $selected_cities = $_POST['city_slugs'] ?? [];
        $cpc_input = (float)$_POST['offer_cpc'];
        $cpm_input = (float)$_POST['offer_cpm'];
        
        // Validación con tolerancia mínima para evitar errores de decimales
        if ($cpc_input < ($min_cpc - 0.01) || $cpm_input < ($min_cpm - 0.01)) {
             $mensaje_estado = "La oferta no puede ser menor al mínimo ($$min_cpc CPC / $$min_cpm CPM).";
             $es_error = true;
        } else {
            $data_to_save = [
                'user_id' => $userId, 
                'city_slugs' => implode(',', $selected_cities), 
                'titulo' => substr(trim($_POST['titulo']), 0, 30), 
                'descripcion' => substr(trim($_POST['descripcion']), 0, 100), 
                'logo_url' => $file_path_db,
                'cta_url' => trim($_POST['cta_url']),
                'posicion' => $_POST['posicion'] ?? 'top',
                'max_impresiones' => (int)$_POST['max_impresiones'],
                'max_clicks' => (int)$_POST['max_clicks'],
                'offer_cpc' => $cpc_input,
                'offer_cpm' => $cpm_input,
                'freq_max_views' => (int)($_POST['freq_max_views'] ?? 3),
                'freq_reset_hours' => (int)($_POST['freq_reset_hours'] ?? 6),
                'tiempo_muestra' => 10000
            ];
    
            try {
                if ($_POST['action'] === 'create') {
                    $sql = "INSERT INTO banners (user_id, city_slugs, titulo, descripcion, logo_url, cta_url, posicion, max_impresiones, max_clicks, tiempo_muestra, offer_cpc, offer_cpm, freq_max_views, freq_reset_hours, is_active, is_approved)
                            VALUES (:user_id, :city_slugs, :titulo, :descripcion, :logo_url, :cta_url, :posicion, :max_impresiones, :max_clicks, :tiempo_muestra, :offer_cpc, :offer_cpm, :freq_max_views, :freq_reset_hours, 0, 0)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data_to_save);
                    header("Location: campaigns.php?msg=" . urlencode("Campaña creada. Pendiente de aprobación."));
                    exit;
                } elseif ($_POST['action'] === 'edit') {
                    $data_to_save['id'] = $banner_id;
                    $sql = "UPDATE banners SET city_slugs=:city_slugs, titulo=:titulo, descripcion=:descripcion, logo_url=:logo_url, cta_url=:cta_url, posicion=:posicion, max_impresiones=:max_impresiones, max_clicks=:max_clicks, tiempo_muestra=:tiempo_muestra, offer_cpc=:offer_cpc, offer_cpm=:offer_cpm, freq_max_views=:freq_max_views, freq_reset_hours=:freq_reset_hours, is_approved=0 WHERE id=:id AND user_id=:user_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data_to_save);
                    header("Location: campaigns.php?msg=" . urlencode("Campaña actualizada. Pendiente de revisión."));
                    exit;
                }
            } catch (Exception $e) {
                $mensaje_estado = "Error BD: " . $e->getMessage();
                $es_error = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Gestor de Anuncio</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; margin: 0; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        
        h1 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        
        /* Grid Responsivo */
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        .section-box { background: #fff; margin-bottom: 20px; }
        .section-title { font-size: 1.1em; color: #34495e; font-weight: bold; margin-bottom: 15px; border-left: 4px solid #3498db; padding-left: 10px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; font-size: 0.9em; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1em; box-sizing: border-box; }
        input:focus { border-color: #3498db; outline: none; }
        
        .char-count { font-size: 0.8em; color: #7f8c8d; text-align: right; display: block; margin-top: 4px; }
        
        .offer-box { background: #f0f9ff; padding: 20px; border-radius: 8px; border: 1px solid #bce0fd; }
        .offer-help { font-size: 0.85em; color: #005b9f; margin-bottom: 15px; line-height: 1.4; }
        
        .city-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; max-height: 180px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 5px; }
        .city-option { display: flex; align-items: center; font-size: 0.9em; gap: 8px; cursor: pointer; padding: 5px; border-radius: 4px; transition: background 0.2s; }
        .city-option:hover { background: #f9f9f9; }
        
        .btn-save { width: 100%; padding: 15px; background: #2ecc71; color: white; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em; margin-top: 20px; transition: 0.2s; }
        .btn-save:hover { background: #27ae60; transform: translateY(-2px); }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; }
        
        .error-box { background: #fcebeb; color: #c0392b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #e74c3c; }
        
        .preview-img { max-width: 150px; margin-top: 10px; border: 1px solid #ddd; padding: 4px; border-radius: 4px; background: white; }

        /* --- MÓVIL --- */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .container { padding: 20px; }
            .grid-layout { grid-template-columns: 1fr; gap: 20px; }
            .city-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $modo_edicion ? 'Editar Campaña' : 'Nueva Campaña' ?></h1>
        
        <?php if ($mensaje_estado): ?>
            <div class="error-box">⚠️ <?= htmlspecialchars($mensaje_estado) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $modo_edicion ? 'edit' : 'create' ?>">
            
            <div class="grid-layout">
                <div>
                    <div class="section-box">
                        <div class="section-title">🎨 Diseño del Anuncio</div>
                        
                        <div class="form-group">
                            <label>Título (Nombre de tu negocio)</label>
                            <input type="text" name="titulo" id="titulo" maxlength="30" required value="<?= htmlspecialchars($datos_form['titulo'] ?? '') ?>" placeholder="Ej: Restaurante El Sabor">
                            <span id="count-titulo" class="char-count">30 restantes</span>
                        </div>

                        <div class="form-group">
                            <label>Descripción (Oferta corta)</label>
                            <textarea name="descripcion" id="descripcion" maxlength="100" rows="3" required placeholder="Ej: 20% de descuento en almuerzos hoy."><?= htmlspecialchars($datos_form['descripcion'] ?? '') ?></textarea>
                            <span id="count-desc" class="char-count">100 restantes</span>
                        </div>

                        <div class="form-group">
                            <label>Enlace de Destino (Al hacer clic)</label>
                            <input type="text" name="cta_url" required value="<?= htmlspecialchars($datos_form['cta_url'] ?? '') ?>" placeholder="https://wa.me/57...">
                        </div>

                        <div class="form-group">
                            <label>Logo o Imagen (Cuadrada 1:1)</label>
                            <input type="file" name="logo_file" accept="image/*" <?= $modo_edicion ? '' : 'required' ?>>
                            <?php if($modo_edicion && !empty($datos_form['logo_url'])): ?>
                                <div style="margin-top:5px; font-size:0.8em; color:#777;">Imagen Actual:</div>
                                <img src="<?= htmlspecialchars($datos_form['logo_url']) ?>" class="preview-img">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="section-box">
                        <div class="section-title">🎯 Segmentación & Frecuencia</div>
                        <div class="form-group">
                            <label>Ciudades (¿Dónde se verá?)</label>
                            <div class="city-grid">
                                <?php foreach($ciudades_disponibles as $slug => $nombre): ?>
                                    <label class="city-option">
                                        <input type="checkbox" name="city_slugs[]" value="<?= $slug ?>" <?= in_array($slug, $banner_ciudades_array) ? 'checked' : '' ?>>
                                        <?= $nombre ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid-layout" style="gap:10px; grid-template-columns: 1fr 1fr;">
                            <div class="form-group">
                                <label>Posición</label>
                                <div class="form-group">
    <label>Posición</label>
    <select name="posicion">
        <option value="top" <?= ($datos_form['posicion']??'')=='top'?'selected':'' ?>>Arriba (Header)</option>
        <option value="bottom" <?= ($datos_form['posicion']??'')=='bottom'?'selected':'' ?>>Abajo (Footer)</option>
        <option value="both" <?= ($datos_form['posicion']??'')=='both'?'selected':'' ?>>⭐ Ambas (Más visibilidad)</option>
    </select>
</div>
                            </div>
                            <div class="form-group">
                                <label>Vistas por Usuario</label>
                                <input type="number" name="freq_max_views" min="1" max="10" value="<?= $datos_form['freq_max_views'] ?? 3 ?>" title="Máximo de veces que un mismo usuario verá tu anuncio">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label>Reiniciar contador cada (Horas)</label>
                                <input type="number" name="freq_reset_hours" min="1" max="24" value="<?= $datos_form['freq_reset_hours'] ?? 6 ?>" title="Tiempo para volver a mostrar el anuncio al mismo usuario">
                            </div>
                        </div>
                    </div>

                    <div class="section-box offer-box">
                        <div class="section-title">💰 Tu Oferta (Subasta)</div>
                        <p class="offer-help">
                            El sistema prioriza los anuncios que pagan más. Define tu oferta base por cada click recibo y cada visualización de tu anuncio.
                        </p>

                        <div class="form-group">
                            <label>Define cuanto pagarás por Clic (CPC) - Mín: $<?= number_format($min_cpc) ?></label>
                            <input type="number" name="offer_cpc" min="<?= $min_cpc ?>" step="10" required value="<?= $datos_form['offer_cpc'] ?? $min_cpc ?>">
                        </div>

                        <div class="form-group">
                            <label>Costo por 1000 Vistas (CPM) - Mín: $<?= number_format($min_cpm) ?></label>
                            <input type="number" name="offer_cpm" min="<?= $min_cpm ?>" step="100" required value="<?= $datos_form['offer_cpm'] ?? $min_cpm ?>">
                        </div>
                    </div>

                    <div class="section-box">
                        <div class="section-title">🛑 Límites de Presupuesto</div>
                        <div style="display:flex; gap:10px;">
                            <div style="flex:1">
                                <label>Máx. Clics</label>
                                <input type="number" name="max_clicks" min="10" value="<?= $datos_form['max_clicks'] ?? 500 ?>">
                            </div>
                            <div style="flex:1">
                                <label>Máx. Vistas</label>
                                <input type="number" name="max_impresiones" min="1000" value="<?= $datos_form['max_impresiones'] ?? 50000 ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">🚀 <?= $modo_edicion ? 'Guardar Cambios' : 'Crear Campaña' ?></button>
            <a href="campaigns.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>

    <script>
        // Contador de Caracteres
        function setupCounter(inputId, displayId, max) {
            const input = document.getElementById(inputId);
            const display = document.getElementById(displayId);
            const update = () => {
                const left = max - input.value.length;
                display.textContent = left + " caracteres restantes";
                display.style.color = left < 5 ? "#e74c3c" : "#7f8c8d";
            };
            input.addEventListener('input', update);
            update();
        }
        document.addEventListener('DOMContentLoaded', () => {
            setupCounter('titulo', 'count-titulo', 30); // Límite actualizado a 30
            setupCounter('descripcion', 'count-desc', 100);
        });
    </script>
</body>
</html>