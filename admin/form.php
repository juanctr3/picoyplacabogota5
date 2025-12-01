<?php
/**
 * admin/form.php - Gestión de Banners (Versión SaaS con Upload)
 */
require_once 'auth.php';
require_once 'db_connect.php'; 
require_once '../config-ciudades.php'; 

// Lista de ciudades para los checkboxes
$ciudades_disponibles = [];
foreach ($ciudades as $slug => $data) {
    if ($slug !== 'rotaciones_base') {
        $ciudades_disponibles[$slug] = $data['nombre'];
    }
}

$mensaje_estado = '';
$es_error = false;
$banner_id = $_GET['id'] ?? null;
$datos_form = [];
$modo_edicion = false;
$banner_ciudades_array = []; 

// 1. CARGAR DATOS SI ES EDICIÓN
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
        'frecuencia_factor' => 2,
        'logo_url' => '' // Importante iniciar vacío
    ];
}

// 2. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Recoger datos básicos
    $selected_cities = $_POST['city_slugs'] ?? [];
    
    // Configuración Base
    $data_to_save = [
        'city_slugs' => implode(',', $selected_cities), 
        'titulo' => substr(trim($_POST['titulo'] ?? ''), 0, 25), 
        'descripcion' => substr(trim($_POST['descripcion'] ?? ''), 0, 100), 
        'cta_url' => trim($_POST['cta_url'] ?? ''),
        'posicion' => $_POST['posicion'] ?? 'top',
        'max_impresiones' => (int)($_POST['max_impresiones'] ?? 0),
        'max_clicks' => (int)($_POST['max_clicks'] ?? 0),
        'tiempo_muestra' => (int)($_POST['tiempo_muestra'] ?? 10000),
        'frecuencia_factor' => (int)($_POST['frecuencia_factor'] ?? 1),
        // Por defecto mantenemos la URL anterior (campo oculto)
        'logo_url' => $_POST['logo_url_actual'] ?? '' 
    ];

    // --- LÓGICA DE SUBIDA DE IMAGEN (SaaS) ---
    if (isset($_FILES['imagen_subida']) && $_FILES['imagen_subida']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen_subida']['tmp_name'];
        $fileName = $_FILES['imagen_subida']['name'];
        $fileSize = $_FILES['imagen_subida']['size'];
        $fileType = $_FILES['imagen_subida']['type'];
        
        // Limpiar nombre y extraer extensión
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        // Extensiones permitidas
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'webp', 'jpeg');
        
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Nombre único: timestamp_nombre.ext
            $newFileName = time() . '_' . $data_to_save['titulo'] . '.' . $fileExtension;
            // Eliminar caracteres raros del nombre
            $newFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $newFileName);
            
            // Rutas
            $uploadFileDir = '../assets/uploads/banners/';
            
            // Crear carpeta si no existe (intento por PHP)
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Guardar la ruta pública en la BD
                $data_to_save['logo_url'] = '/assets/uploads/banners/' . $newFileName;
            } else {
                $mensaje_estado = 'Error al mover el archivo a la carpeta de destino. Verifica permisos.';
                $es_error = true;
            }
        } else {
            $mensaje_estado = 'Formato de imagen no válido. Usa JPG, PNG o WEBP.';
            $es_error = true;
        }
    }
    // ------------------------------------------

    // Validar campos obligatorios (La imagen es obligatoria si no hay una previa)
    if (!$es_error) {
        if (empty($data_to_save['titulo']) || empty($data_to_save['cta_url']) || empty($data_to_save['city_slugs'])) {
            $mensaje_estado = 'Faltan datos obligatorios (Título, Ciudad o URL).';
            $es_error = true;
        } elseif (empty($data_to_save['logo_url'])) {
            $mensaje_estado = 'Debes subir una imagen para el anuncio.';
            $es_error = true;
        }
    }

    // Guardar en BD si no hay errores
    if (!$es_error) {
        try {
            if ($_POST['action'] === 'create') {
                $sql = "INSERT INTO banners (city_slugs, titulo, descripcion, logo_url, cta_url, posicion, max_impresiones, max_clicks, tiempo_muestra, frecuencia_factor, is_active)
                        VALUES (:city_slugs, :titulo, :descripcion, :logo_url, :cta_url, :posicion, :max_impresiones, :max_clicks, :tiempo_muestra, :frecuencia_factor, TRUE)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data_to_save);
                header("Location: index.php?status=success&msg=" . urlencode("Anuncio creado correctamente."));
                exit;

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
                
                $mensaje_estado = "Anuncio actualizado.";
                // Recargar datos para ver la imagen nueva
                $datos_form = array_merge($datos_form, $data_to_save);
                $banner_ciudades_array = $selected_cities;
            }
        } catch (PDOException $e) {
            $mensaje_estado = "Error SQL: " . $e->getMessage();
            $es_error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modo_edicion ? 'Editar Anuncio' : 'Nuevo Anuncio' ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f8; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        h1 { color: #2d3436; border-bottom: 2px solid #0984e3; padding-bottom: 15px; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #636e72; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;}
        input[type="file"] { padding: 10px; background: #f1f2f6; border-radius: 6px; width: 100%; box-sizing: border-box;}
        
        button { background-color: #00b894; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; width: 100%; }
        button:hover { background-color: #00a884; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .msg-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .error { background: #ff7675; color: white; }
        .success { background: #55efc4; color: #2d3436; }

        /* Preview de Imagen */
        .img-preview { margin-top: 10px; padding: 10px; border: 1px dashed #ccc; text-align: center; border-radius: 6px;}
        .img-preview img { max-width: 100%; max-height: 100px; object-fit: contain; }

        /* Checkboxes Ciudades */
        .city-box { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; padding: 15px; border: 1px solid #eee; border-radius: 6px; max-height: 200px; overflow-y: auto; }
        .city-option { display: flex; align-items: center; gap: 8px; font-size: 0.9em; cursor: pointer; }
        .city-option input { margin: 0; width: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $modo_edicion ? '✏️ Editar Anuncio' : '📢 Nuevo Anuncio' ?></h1>
        
        <?php if ($mensaje_estado): ?>
            <div class="msg-box <?= $es_error ? 'error' : 'success' ?>">
                <?= htmlspecialchars($mensaje_estado) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="form.php<?= $modo_edicion ? '?id=' . $banner_id : '' ?>">
            <input type="hidden" name="action" value="<?= $modo_edicion ? 'edit' : 'create' ?>">
            <input type="hidden" name="logo_url_actual" value="<?= htmlspecialchars($datos_form['logo_url'] ?? '') ?>">

            <div class="grid-2">
                <div>
                    <div class="form-group">
                        <label>Título (Empresa) - Máx 25 letras</label>
                        <input type="text" name="titulo" maxlength="25" required value="<?= htmlspecialchars($datos_form['titulo'] ?? '') ?>" placeholder="Ej: Taller Juan">
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción - Máx 100 letras</label>
                        <input type="text" name="descripcion" maxlength="100" required value="<?= htmlspecialchars($datos_form['descripcion'] ?? '') ?>" placeholder="Ej: 20% dto en cambio de aceite">
                    </div>

                    <div class="form-group">
                        <label>Enlace de Destino (WhatsApp o Web)</label>
                        <input type="text" name="cta_url" required value="<?= htmlspecialchars($datos_form['cta_url'] ?? '') ?>" placeholder="https://wa.me/57...">
                    </div>

                    <div class="form-group">
                        <label>📷 Imagen del Anuncio (Logo/Producto)</label>
                        <input type="file" name="imagen_subida" accept="image/*">
                        <?php if (!empty($datos_form['logo_url'])): ?>
                            <div class="img-preview">
                                <p style="margin:0 0 5px 0; font-size:0.8em;">Imagen Actual:</p>
                                <img src="<?= htmlspecialchars($datos_form['logo_url']) ?>" alt="Preview">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>🌍 Ciudades (Selecciona al menos una)</label>
                        <div class="city-box">
                            <?php foreach($ciudades_disponibles as $slug => $nombre): ?>
                                <label class="city-option">
                                    <input type="checkbox" name="city_slugs[]" value="<?= $slug ?>" 
                                        <?= in_array($slug, $banner_ciudades_array) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($nombre) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Límite Vistas (0=∞)</label>
                            <input type="number" name="max_impresiones" value="<?= $datos_form['max_impresiones'] ?? 50000 ?>">
                        </div>
                        <div class="form-group">
                            <label>Límite Clics (0=∞)</label>
                            <input type="number" name="max_clicks" value="<?= $datos_form['max_clicks'] ?? 500 ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Duración (Miliseg)</label>
                            <input type="number" name="tiempo_muestra" value="<?= $datos_form['tiempo_muestra'] ?? 12000 ?>">
                        </div>
                         <div class="form-group">
                            <label>Posición</label>
                            <select name="posicion">
                                <option value="top" <?= ($datos_form['posicion']??'')=='top'?'selected':'' ?>>Arriba</option>
                                <option value="bottom" <?= ($datos_form['posicion']??'')=='bottom'?'selected':'' ?>>Abajo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit">💾 Guardar Anuncio</button>
            <div style="text-align:center; margin-top:15px;">
                <a href="index.php" style="color:#636e72; text-decoration:none;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>