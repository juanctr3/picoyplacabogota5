<?php
/**
 * admin/form.php - Gestión de Banners (Diseño Moderno + Contadores)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; 

// Carga segura de ciudades
$ciudadesFile = __DIR__ . '/../config-ciudades.php';
if (file_exists($ciudadesFile)) {
    require_once $ciudadesFile;
} else {
    $ciudades = []; // Fallback vacío para no romper
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

$mensaje_estado = '';
$es_error = false;
$banner_id = $_GET['id'] ?? null;
$datos_form = [];
$modo_edicion = false;
$banner_ciudades_array = []; 

// 1. CARGAR DATOS
if ($banner_id) {
    $modo_edicion = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id");
        $stmt->execute([':id' => $banner_id]);
        $datos_form = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($datos_form) {
            $banner_ciudades_array = explode(',', $datos_form['city_slugs']); 
        } else {
            $mensaje_estado = "Banner no encontrado.";
            $es_error = true;
        }
    } catch (PDOException $e) {
        $mensaje_estado = "Error BD: " . $e->getMessage();
        $es_error = true;
    }
}

// Valores por defecto
if (!$modo_edicion) {
    $datos_form = [
        'posicion' => 'top',
        'max_impresiones' => 50000,
        'max_clicks' => 500,
        'tiempo_muestra' => 10000,
        'frecuencia_factor' => 1,
        'logo_url' => ''
    ];
}

// 2. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $selected_cities = $_POST['city_slugs'] ?? [];
    
    $data_to_save = [
        'city_slugs' => implode(',', $selected_cities), 
        'titulo' => substr(trim($_POST['titulo'] ?? ''), 0, 30),
        'descripcion' => substr(trim($_POST['descripcion'] ?? ''), 0, 100), 
        'cta_url' => trim($_POST['cta_url'] ?? ''),
        'posicion' => $_POST['posicion'] ?? 'top',
        'max_impresiones' => (int)($_POST['max_impresiones'] ?? 0),
        'max_clicks' => (int)($_POST['max_clicks'] ?? 0),
        'tiempo_muestra' => (int)($_POST['tiempo_muestra'] ?? 10000),
        'frecuencia_factor' => (int)($_POST['frecuencia_factor'] ?? 1),
        'logo_url' => $_POST['logo_url_actual'] ?? '' 
    ];

    // Subida de Imagen
    if (isset($_FILES['imagen_subida']) && $_FILES['imagen_subida']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen_subida']['name'], PATHINFO_EXTENSION);
        $newFileName = time() . '_admin_' . uniqid() . '.' . $ext;
        $uploadFileDir = '../assets/uploads/banners/';
        
        if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);
        
        if(move_uploaded_file($_FILES['imagen_subida']['tmp_name'], $uploadFileDir . $newFileName)) {
            $data_to_save['logo_url'] = '/assets/uploads/banners/' . $newFileName;
        }
    }

    try {
        if ($_POST['action'] === 'create') {
            $sql = "INSERT INTO banners (user_id, city_slugs, titulo, descripcion, logo_url, cta_url, posicion, max_impresiones, max_clicks, tiempo_muestra, frecuencia_factor, is_active, is_approved)
                    VALUES (1, :city_slugs, :titulo, :descripcion, :logo_url, :cta_url, :posicion, :max_impresiones, :max_clicks, :tiempo_muestra, :frecuencia_factor, 1, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data_to_save);
            header("Location: index.php?msg=" . urlencode("Banner creado exitosamente")); exit;

        } elseif ($_POST['action'] === 'edit' && $banner_id) {
            $data_to_save['id'] = $banner_id;
            $sql = "UPDATE banners SET 
                        city_slugs = :city_slugs, titulo = :titulo, descripcion = :descripcion, 
                        logo_url = :logo_url, cta_url = :cta_url, posicion = :posicion, 
                        max_impresiones = :max_impresiones, max_clicks = :max_clicks, 
                        tiempo_muestra = :tiempo_muestra, frecuencia_factor = :frecuencia_factor
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data_to_save);
            
            $mensaje_estado = "Actualizado correctamente.";
            $datos_form = array_merge($datos_form, $data_to_save);
            $banner_ciudades_array = $selected_cities;
        }
    } catch (PDOException $e) {
        $mensaje_estado = "Error SQL: " . $e->getMessage();
        $es_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Banner</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f2f5; padding: 20px; color: #333; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
        
        h1 { color: #2c3e50; margin-bottom: 30px; border-bottom: 2px solid #3498db; padding-bottom: 10px; font-weight: 700; }
        
        /* Grid Layout */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        @media(max-width:768px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group { margin-bottom: 20px; position: relative; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.95em; }
        
        input[type="text"], input[type="number"], select, textarea { 
            width: 100%; padding: 12px 15px; border: 1px solid #dce0e4; border-radius: 8px; 
            font-size: 15px; box-sizing: border-box; transition: all 0.2s; background: #fdfdfd;
        }
        input:focus, textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); background: #fff; }
        
        /* Contadores */
        .char-count { font-size: 0.8em; color: #95a5a6; text-align: right; margin-top: 4px; display: block; font-weight: 500; }
        
        /* Ciudad Grid */
        .city-container { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; 
            background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;
            max-height: 250px; overflow-y: auto;
        }
        .city-label { display: flex; align-items: center; gap: 8px; font-size: 0.9em; cursor: pointer; padding: 5px; border-radius: 4px; transition: background 0.2s; }
        .city-label:hover { background: #e2e6ea; }
        .city-label input { margin: 0; width: auto; }

        /* Botones */
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { padding: 14px 25px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: 700; width: 100%; text-align: center; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; }
        .btn-save { background: #2ecc71; color: white; box-shadow: 0 4px 6px rgba(46,204,113,0.2); }
        .btn-save:hover { background: #27ae60; transform: translateY(-2px); }
        .btn-cancel { background: #ecf0f1; color: #7f8c8d; }
        .btn-cancel:hover { background: #bdc3c7; }

        /* Mensajes */
        .msg { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; }
        .error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .success { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }

        .img-preview { margin-top: 10px; padding: 5px; border: 1px dashed #cbd5e0; display: inline-block; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $modo_edicion ? '✏️ Editar Anuncio' : '📢 Nuevo Anuncio' ?></h1>
        
        <?php if($mensaje_estado): ?>
            <div class="msg <?= $es_error ? 'error' : 'success' ?>"><?= htmlspecialchars($mensaje_estado) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $modo_edicion ? 'edit' : 'create' ?>">
            <input type="hidden" name="logo_url_actual" value="<?= htmlspecialchars($datos_form['logo_url'] ?? '') ?>">

            <div class="form-grid">
                <div>
                    <div class="form-group">
                        <label>Título del Anuncio</label>
                        <input type="text" name="titulo" id="titulo" maxlength="30" required value="<?= htmlspecialchars($datos_form['titulo']??'') ?>" placeholder="Ej: Super Oferta">
                        <span id="count-titulo" class="char-count">30 restantes</span>
                    </div>

                    <div class="form-group">
                        <label>Descripción Corta</label>
                        <textarea name="descripcion" id="descripcion" maxlength="100" rows="3" placeholder="Describe tu producto..."><?= htmlspecialchars($datos_form['descripcion']??'') ?></textarea>
                        <span id="count-desc" class="char-count">100 restantes</span>
                    </div>

                    <div class="form-group">
                        <label>URL de Destino (https://...)</label>
                        <input type="text" name="cta_url" value="<?= htmlspecialchars($datos_form['cta_url']??'') ?>" required placeholder="https://tu-web.com">
                    </div>

                    <div class="form-group">
                        <label>Imagen del Banner</label>
                        <input type="file" name="imagen_subida" accept="image/*" style="background:#f8f9fa;">
                        <?php if(!empty($datos_form['logo_url'])): ?>
                            <div class="img-preview">
                                <img src="<?= htmlspecialchars($datos_form['logo_url']) ?>" style="max-height:80px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Ciudades Disponibles</label>
                        <div class="city-container">
                            <?php foreach($ciudades as $slug => $d): ?>
                                <?php 
                                // FIX: Verificar que exista el nombre para evitar Warning
                                if (empty($d['nombre'])) continue; 
                                ?>
                                <label class="city-label">
                                    <input type="checkbox" name="city_slugs[]" value="<?= $slug ?>" <?= in_array($slug, $banner_ciudades_array)?'checked':'' ?>>
                                    <?= htmlspecialchars($d['nombre']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-grid" style="gap:15px;">
                        <div class="form-group">
                            <label>Máx Vistas</label>
                            <input type="number" name="max_impresiones" value="<?= $datos_form['max_impresiones']??50000 ?>">
                        </div>
                        <div class="form-group">
                            <label>Máx Clics</label>
                            <input type="number" name="max_clicks" value="<?= $datos_form['max_clicks']??500 ?>">
                        </div>
                    </div>

                    <div class="form-grid" style="gap:15px;">
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
                            <label>Duración (ms)</label>
                            <input type="number" name="tiempo_muestra" value="<?= $datos_form['tiempo_muestra']??10000 ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <a href="index.php" class="btn btn-cancel">Cancelar</a>
                <button type="submit" class="btn btn-save">💾 Guardar Cambios</button>
            </div>
        </form>
    </div>

    <script>
        // Lógica de Contadores en Tiempo Real
        function initCounter(inputId, displayId, max) {
            const input = document.getElementById(inputId);
            const display = document.getElementById(displayId);
            if(!input || !display) return;

            const update = () => {
                const len = input.value.length;
                const left = max - len;
                display.textContent = `${left} caracteres restantes`;
                display.style.color = left < 5 ? '#e74c3c' : '#95a5a6';
            };
            input.addEventListener('input', update);
            update(); // Inicializar
        }

        document.addEventListener('DOMContentLoaded', () => {
            initCounter('titulo', 'count-titulo', 30);
            initCounter('descripcion', 'count-desc', 100);
        });
    </script>
</body>
</html>