<?php
/**
 * user/create_ad.php - Formulario Unificado de Creación y Edición (Modo Anunciante)
 * Implementa la carga de archivos directa y la asignación automática de user_id.
 */
session_start();
require_once 'db_connect.php'; 
require_once '../config-ciudades.php'; 

// CRÍTICO: Comprobar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'advertiser') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id']; 
// ----------------------------------------------------------------------
// RUTA DE CARGA DE ARCHIVOS Y DECLARACIÓN GLOBAL DE CIUDADES (FIX)
// ----------------------------------------------------------------------
$UPLOAD_DIR = '../uploads/uploads/banners/'; // Directorio para guardar las imágenes (Asumido en la raíz superior)
global $ciudades; // Asegura que la lista de ciudades esté disponible

// Lista de ciudades disponibles
$ciudades_disponibles = [];
foreach ($ciudades as $slug => $data) {
    if ($slug !== 'rotaciones_base') {
        $ciudades_disponibles[$slug] = $data['nombre'];
    }
}

// Lista de logos aprobados (optimización anterior)
$logos_aprobados = [
    '/favicons/apple-icon.png' => 'Semáforo Grande (Icono Principal)',
    '/favicons/favicon-32x32.png' => 'Semáforo Pequeño (32x32)',
    '/favicons/android-icon-192x192.png' => 'Semáforo (192x192)',
    '/uploads/banners/logo_filedata.png' => 'Logo de Filedata (Ejemplo)',
];

// Variables de estado y de formulario
$mensaje_estado = '';
$es_error = false;
$banner_id = $_GET['id'] ?? null;
$datos_form = [];
$modo_edicion = false;
$banner_ciudades_array = []; 

// 1. LÓGICA DE CARGA DE DATOS PARA EDICIÓN
if ($banner_id) {
    $modo_edicion = true;
    try {
        // Solo puede editar sus propios banners (Filtro user_id)
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id AND user_id = :userId");
        $stmt->execute([':id' => $banner_id, ':userId' => $userId]);
        $datos_form = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos_form) {
            $mensaje_estado = "Error: Banner no encontrado o no te pertenece.";
            $es_error = true;
            $modo_edicion = false;
            $banner_id = null;
        } else {
            $banner_ciudades_array = explode(',', $datos_form['city_slugs']);
        }
    } catch (PDOException $e) {
        $mensaje_estado = "Error al cargar datos: " . $e->getMessage();
        $es_error = true;
    }
}

// Inicializar datos para el modo Creación
if (!$modo_edicion) {
    $datos_form = [
        'city_slugs' => '',
        'titulo' => '',
        'descripcion' => '',
        'logo_url' => '', 
        'cta_url' => '',
        'posicion' => 'top',
        'max_impresiones' => 50000,
        'max_clicks' => 500,
        'tiempo_muestra' => 12000,
        'frecuencia_factor' => 2,
        'offer_cpc' => 0.15,
        'offer_cpm' => 5.00,
    ];
}


// 2. LÓGICA DE PROCESAMIENTO DEL FORMULARIO (Creación o Edición)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $file_path_db = $datos_form['logo_url'] ?? ''; 
    $file_uploaded = false;
    
    // 2.1. MANEJO DE LA CARGA DE ARCHIVOS (CRÍTICO)
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['logo_file'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        
        // Validación de Tipo y Tamaño (SEGURIDAD BÁSICA)
        if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
            if ($file['size'] < 1000000) { // Límite de 1MB
                
                // Renombrar el archivo de forma segura y moverlo
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safe_filename = uniqid('banner_', true) . '.' . $ext;
                $target_file = $UPLOAD_DIR . $safe_filename;
                
                // Asegurar que el directorio exista (solo para robustez)
                if (!is_dir($UPLOAD_DIR)) {
                    mkdir($UPLOAD_DIR, 0777, true);
                }

                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $file_path_db = '/' . $UPLOAD_DIR . $safe_filename; // Ruta pública para la BD
                    $file_uploaded = true;
                } else {
                    $mensaje_estado = "Error al mover el archivo. Verifique los permisos (chmod 777) de " . $UPLOAD_DIR;
                    $es_error = true;
                }
            } else {
                $mensaje_estado = "Error: El archivo excede el tamaño máximo de 1MB.";
                $es_error = true;
            }
        } else {
            $mensaje_estado = "Error: Tipo de archivo no permitido. Solo JPEG, PNG o GIF.";
            $es_error = true;
        }
    }

    // 2.2. Procesamiento de Datos (Solo si la carga fue exitosa o si estamos editando sin cambiar la imagen)
    if ($es_error) {
        // Si hay error en la carga, no procesamos la BD
    } else {
        
        $selected_cities = $_POST['city_slugs'] ?? [];
        $data_to_save = [
            'user_id' => $userId, 
            'city_slugs' => implode(',', $selected_cities), 
            'titulo' => substr(trim($_POST['titulo'] ?? ''), 0, 25), 
            'descripcion' => substr(trim($_POST['descripcion'] ?? ''), 0, 100), 
            'logo_url' => $file_path_db, // Usar la nueva ruta o la ruta existente
            'cta_url' => trim($_POST['cta_url'] ?? ''),
            'posicion' => $_POST['posicion'] ?? 'top',
            'max_impresiones' => (int)($_POST['max_impresiones'] ?? 0),
            'max_clicks' => (int)($_POST['max_clicks'] ?? 0),
            'tiempo_muestra' => (int)($_POST['tiempo_muestra'] ?? 10000),
            'frecuencia_factor' => (int)($_POST['frecuencia_factor'] ?? 1),
            'offer_cpc' => (float)($_POST['offer_cpc'] ?? 0.00),
            'offer_cpm' => (float)($_POST['offer_cpm'] ?? 0.00),
        ];

        // Recopilar los datos del formulario para rellenar si falla la BD
        $datos_form = array_merge($datos_form, $data_to_save);
        $banner_ciudades_array = $selected_cities; 

        if (empty($data_to_save['titulo']) || empty($data_to_save['cta_url']) || empty($data_to_save['logo_url']) || empty($data_to_save['city_slugs'])) {
            $mensaje_estado = 'Error: Todos los campos obligatorios deben ser llenados (incluyendo el Logo).';
            $es_error = true;
        } else {
            try {
                if ($_POST['action'] === 'create') {
                    // Creación: Se inserta como INACTIVO y PENDIENTE DE APROBACIÓN
                    $sql = "INSERT INTO banners (user_id, city_slugs, titulo, descripcion, logo_url, cta_url, posicion, max_impresiones, max_clicks, tiempo_muestra, frecuencia_factor, offer_cpc, offer_cpm, is_active, is_approved)
                            VALUES (:user_id, :city_slugs, :titulo, :descripcion, :logo_url, :cta_url, :posicion, :max_impresiones, :max_clicks, :tiempo_muestra, :frecuencia_factor, :offer_cpc, :offer_cpm, FALSE, FALSE)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data_to_save);
                    
                    $mensaje_estado = "Anuncio enviado para APROBACIÓN. Será activado cuando un administrador lo revise y tu saldo sea positivo.";
                    header("Location: campaigns.php?msg=" . urlencode($mensaje_estado));
                    exit;

                } elseif ($_POST['action'] === 'edit' && $banner_id) {
                    // Edición: Actualiza y pone en estado PENDIENTE DE APROBACIÓN
                    $data_to_save['id'] = $banner_id;
                    $sql = "UPDATE banners SET 
                                city_slugs = :city_slugs, titulo = :titulo, descripcion = :descripcion, 
                                logo_url = :logo_url, cta_url = :cta_url, posicion = :posicion, 
                                max_impresiones = :max_impresiones, max_clicks = :max_clicks, 
                                tiempo_muestra = :tiempo_muestra, frecuencia_factor = :frecuencia_factor,
                                offer_cpc = :offer_cpc, offer_cpm = :offer_cpm, 
                                is_approved = FALSE -- Al editar, siempre vuelve a pendiente
                            WHERE id = :id AND user_id = :user_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data_to_save);
                    
                    $mensaje_estado = "Anuncio ID {$banner_id} actualizado. Estado cambiado a PENDIENTE DE APROBACIÓN.";
                    header("Location: campaigns.php?msg=" . urlencode($mensaje_estado));
                    exit;
                }
            } catch (PDOException $e) {
                $mensaje_estado = "Error de base de datos: " . $e->getMessage();
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
    <title><?= $modo_edicion ? 'Editar Anuncio' : 'Crear Nuevo Anuncio' ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #3498db; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="file"], select { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #2ecc71; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .char-counter { font-size: 0.8em; color: #7f8c8d; }
        .error-box { background-color: #fcebeb; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .success-box { background-color: #e6f7e9; color: #27ae60; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .city-checkbox-grid { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; padding: 10px; border-radius: 4px; }
        .city-checkbox-grid label { display: inline-flex; align-items: center; font-weight: normal; margin: 0; }
        .city-checkbox-grid input[type="checkbox"] { width: auto; margin-right: 5px; margin-top: 0; }
        .info-alert { background-color: #f1c40f; color: #333; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
        .current-logo-display { margin-top: 10px; padding: 10px; border: 1px dashed #ccc; text-align: center; font-size: 0.9em; }
        .current-logo-display img { max-width: 50px; height: auto; display: block; margin: 5px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $modo_edicion ? 'Editar Anuncio ID ' . $banner_id : 'Crear Nuevo Anuncio' ?> 📝</h1>
        <p><a href="campaigns.php">← Mis Campañas</a> | Su ID de Anunciante es: <?= $userId ?></p>
        
        <?php if ($mensaje_estado): ?>
            <div class="<?= $es_error ? 'error-box' : 'success-box' ?>">
                <?= htmlspecialchars($mensaje_estado) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="create_ad.php<?= $modo_edicion ? '?id=' . $banner_id : '' ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $modo_edicion ? 'edit' : 'create' ?>">
            
            <?php if ($modo_edicion): ?>
                 <input type="hidden" name="logo_url" value="<?= htmlspecialchars($datos_form['logo_url']) ?>">
            <?php endif; ?>

            <div class="grid">
                <div>
                    <h2>Contenido y Destino</h2>
                    
                    <label>Ciudades donde mostrar (Requisito Múltiple)</label>
                    <div class="city-checkbox-grid">
                        <?php foreach($ciudades_disponibles as $slug => $nombre): ?>
                            <label>
                                <input type="checkbox" name="city_slugs[]" value="<?= $slug ?>" 
                                    <?= in_array($slug, $banner_ciudades_array) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($nombre) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label for="titulo">Título (Máx. 25 caracteres)</label>
                    <input type="text" name="titulo" id="titulo" maxlength="25" required value="<?= htmlspecialchars($datos_form['titulo'] ?? '') ?>">
                    <span id="counter-titulo" class="char-counter">25 restantes</span>

                    <label for="descripcion">Descripción (Máx. 100 caracteres)</label>
                    <input type="text" name="descripcion" id="descripcion" maxlength="100" required value="<?= htmlspecialchars($datos_form['descripcion'] ?? '') ?>">
                    <span id="counter-descripcion" class="char-counter">100 restantes</span>

                    <label for="cta_url">URL de Destino (Link)</label>
                    <input type="text" name="cta_url" id="cta_url" placeholder="https://ejemplo.com/" required value="<?= htmlspecialchars($datos_form['cta_url'] ?? '') ?>">
                </div>

                <div>
                    <h2>Oferta y Límites</h2>
                    
                    <label for="logo_file">Logo (Carga Directa - JPG/PNG/GIF, Máx. 1MB)</label>
                    <input type="file" name="logo_file" id="logo_file" accept=".jpg, .jpeg, .png, .gif" <?= $modo_edicion && empty($datos_form['logo_url']) ? 'required' : '' ?>>
                    
                    <?php if ($modo_edicion && !empty($datos_form['logo_url'])): ?>
                        <div class="current-logo-display">
                            Logo Actual: <img src="<?= htmlspecialchars($datos_form['logo_url']) ?>" alt="Logo del Banner">
                            <p>(Dejar vacío si no desea cambiar)</p>
                        </div>
                    <?php endif; ?>
                    
                    <label for="posicion">Posición del Banner</label>
                    <select name="posicion" id="posicion" required>
                        <option value="top" <?= ($datos_form['posicion'] ?? 'top') === 'top' ? 'selected' : '' ?>>Arriba (Flotante Superior)</option>
                        <option value="bottom" <?= ($datos_form['posicion'] ?? 'top') === 'bottom' ? 'selected' : '' ?>>Abajo (Flotante Inferior)</option>
                    </select>

                    <label for="tiempo_muestra">Duración Visible (ms)</label>
                    <input type="number" name="tiempo_muestra" id="tiempo_muestra" min="1000" required value="<?= $datos_form['tiempo_muestra'] ?? 12000 ?>">

                    <h3>Oferta (Prioridad)</h3>
                    <label for="offer_cpc">Valor por Click (CPC - COP)</label>
                    <input type="number" name="offer_cpc" id="offer_cpc" min="0.01" step="0.01" required value="<?= $datos_form['offer_cpc'] ?? 0.15 ?>">
                    
                    <label for="offer_cpm">Valor por 1000 Vistas (CPM - COP)</label>
                    <input type="number" name="offer_cpm" id="offer_cpm" min="0.01" step="0.01" required value="<?= $datos_form['offer_cpm'] ?? 5.00 ?>">
                    <div class="char-counter">**Mayor oferta = Mayor prioridad de aparecer.**</div>
                </div>

                <div class="full-width">
                    <h3>Límites de Presupuesto</h3>
                    <label for="max_clicks">Máx. Clicks (Presupuesto por Click)</label>
                    <input type="number" name="max_clicks" id="max_clicks" min="1" required value="<?= $datos_form['max_clicks'] ?? 500 ?>">

                    <label for="max_impresiones">Máx. Impresiones (Presupuesto por Vista)</label>
                    <input type="number" name="max_impresiones" id="max_impresiones" min="1" required value="<?= $datos_form['max_impresiones'] ?? 50000 ?>">
                </div>

                <div class="full-width">
                    <button type="submit"><?= $modo_edicion ? 'Actualizar y Enviar a Revisión' : 'Enviar Anuncio a Revisión' ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        function updateCounter(input, counterId, max) {
            const el = document.getElementById(input);
            const counter = document.getElementById(counterId);
            
            const update = () => {
                const remaining = max - el.value.length;
                counter.textContent = remaining + ' restantes';
                counter.style.color = remaining < 0 ? 'red' : '#7f8c8d';
            };
            
            el.addEventListener('input', update);
            update();
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateCounter('titulo', 'counter-titulo', 25);
            updateCounter('descripcion', 'counter-descripcion', 100); 
        });
    </script>
</body>
</html>