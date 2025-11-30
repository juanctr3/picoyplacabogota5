<?php
/**
 * admin/form.php - Formulario Unificado de Creación y Edición de Banners
 * Solución de Error 500: Incluimos la conexión directamente.
 */
require_once 'db_connect.php'; // CONEXIÓN DIRECTA
require_once '../config-ciudades.php'; // Para la lista de ciudades

// Lista de ciudades disponibles (reutilizando la conexión del archivo de reportes)
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
    '' => 'Usar URL manual'
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
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id");
        $stmt->execute([':id' => $banner_id]);
        $datos_form = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($datos_form) {
            // Convertir la cadena de 'city_slugs' a un array para los checkboxes
            $banner_ciudades_array = explode(',', $datos_form['city_slugs']); 
        } else {
            $mensaje_estado = "Error: Banner no encontrado.";
            $es_error = true;
            $modo_edicion = false;
            $banner_id = null;
        }
    } catch (PDOException $e) {
        $mensaje_estado = "Error al cargar datos: " . $e->getMessage();
        $es_error = true;
    }
}

// Inicializar datos para el modo Creación si no hay datos cargados
if (!$modo_edicion) {
    $datos_form = [
        'city_slugs' => '',
        'titulo' => '',
        'descripcion' => '',
        'logo_url' => array_key_first($logos_aprobados), 
        'cta_url' => '',
        'posicion' => 'top',
        'max_impresiones' => 50000,
        'max_clicks' => 500,
        'tiempo_muestra' => 12000,
        'frecuencia_factor' => 2,
    ];
}


// 2. LÓGICA DE PROCESAMIENTO DEL FORMULARIO (Creación o Edición)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Recoger datos, sanear y limitar a 25/100 (Requisito 11)
    $selected_cities = $_POST['city_slugs'] ?? [];
    $data_to_save = [
        'city_slugs' => implode(',', $selected_cities), 
        'titulo' => substr(trim($_POST['titulo'] ?? ''), 0, 25), 
        'descripcion' => substr(trim($_POST['descripcion'] ?? ''), 0, 100), 
        'logo_url' => trim($_POST['logo_url'] ?? ''), 
        'cta_url' => trim($_POST['cta_url'] ?? ''),
        'posicion' => $_POST['posicion'] ?? 'top',
        'max_impresiones' => (int)($_POST['max_impresiones'] ?? 0),
        'max_clicks' => (int)($_POST['max_clicks'] ?? 0),
        'tiempo_muestra' => (int)($_POST['tiempo_muestra'] ?? 10000),
        'frecuencia_factor' => (int)($_POST['frecuencia_factor'] ?? 1),
    ];
    
    // Si se seleccionó "Usar URL manual", tomamos el valor del campo extra
    if ($data_to_save['logo_url'] === '') {
        $data_to_save['logo_url'] = trim($_POST['logo_url_manual'] ?? '');
    }

    // Recopilar los datos del formulario para rellenar si falla la BD
    $datos_form = array_merge($datos_form, $data_to_save);
    $banner_ciudades_array = $selected_cities; // Para re-seleccionar los checkboxes

    if (empty($data_to_save['titulo']) || empty($data_to_save['cta_url']) || empty($data_to_save['logo_url']) || empty($data_to_save['city_slugs'])) {
        $mensaje_estado = 'Error: El Título, la URL, el Logo y al menos una Ciudad son obligatorios.';
        $es_error = true;
    } else {
        try {
            if ($_POST['action'] === 'create') {
                // Inserción (Creación)
                $sql = "INSERT INTO banners (city_slugs, titulo, descripcion, logo_url, cta_url, posicion, max_impresiones, max_clicks, tiempo_muestra, frecuencia_factor, is_active)
                        VALUES (:city_slugs, :titulo, :descripcion, :logo_url, :cta_url, :posicion, :max_impresiones, :max_clicks, :tiempo_muestra, :frecuencia_factor, TRUE)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data_to_save);
                $mensaje_estado = "Banner creado con éxito. ID: " . $pdo->lastInsertId();
                header("Location: index.php?status=success&msg=" . urlencode($mensaje_estado));
                exit;

            } elseif ($_POST['action'] === 'edit' && $banner_id) {
                // Actualización (Edición)
                $data_to_save['id'] = $banner_id;
                $sql = "UPDATE banners SET 
                            city_slugs = :city_slugs, titulo = :titulo, descripcion = :descripcion, 
                            logo_url = :logo_url, cta_url = :cta_url, posicion = :posicion, 
                            max_impresiones = :max_impresiones, max_clicks = :max_clicks, 
                            tiempo_muestra = :tiempo_muestra, frecuencia_factor = :frecuencia_factor
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data_to_save);
                
                $mensaje_estado = "Banner ID {$banner_id} actualizado con éxito.";
                $modo_edicion = true; 
            }
        } catch (PDOException $e) {
            $mensaje_estado = "Error de base de datos: " . $e->getMessage();
            $es_error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $modo_edicion ? 'Editar Banner ID ' . $banner_id : 'Crear Nuevo Banner' ?></title>
    <style>
        /* Estilos base reutilizados */
        body { font-family: sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #2ecc71; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .char-counter { font-size: 0.8em; color: #7f8c8d; }
        .error-box { background-color: #fcebeb; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .success-box { background-color: #e6f7e9; color: #27ae60; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        /* Estilos específicos para checkboxes */
        .city-checkbox-grid { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; padding: 10px; border-radius: 4px; }
        .city-checkbox-grid label { display: inline-flex; align-items: center; font-weight: normal; margin: 0; }
        .city-checkbox-grid input[type="checkbox"] { width: auto; margin-right: 5px; margin-top: 0; }
        #logo_url_manual_wrapper { display: none; margin-top: -15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $modo_edicion ? 'Editar Banner ID ' . $banner_id : 'Crear Nuevo Banner' ?> 📝</h1>
        <p><a href="index.php">← Volver a Gestión Central</a></p>

        <?php if ($mensaje_estado): ?>
            <div class="<?= $es_error ? 'error-box' : 'success-box' ?>">
                <?= htmlspecialchars($mensaje_estado) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="form.php<?= $modo_edicion ? '?id=' . $banner_id : '' ?>">
            <input type="hidden" name="action" value="<?= $modo_edicion ? 'edit' : 'create' ?>">

            <div class="grid">
                <div>
                    <h2>Contenido y Ciudad</h2>
                    
                    <label>Ciudades (Requisito 1 - Múltiple)</label>
                    <div class="city-checkbox-grid">
                        <?php foreach($ciudades_disponibles as $slug => $nombre): ?>
                            <label>
                                <input type="checkbox" name="city_slugs[]" value="<?= $slug ?>" 
                                    <?= in_array($slug, $banner_ciudades_array) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($nombre) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label for="titulo">Título (Máx. 25 caracteres - Req. 11)</label>
                    <input type="text" name="titulo" id="titulo" maxlength="25" required value="<?= htmlspecialchars($datos_form['titulo'] ?? '') ?>">
                    <span id="counter-titulo" class="char-counter">25 restantes</span>

                    <label for="descripcion">Descripción (Máx. 100 caracteres - Req. 11)</label>
                    <input type="text" name="descripcion" id="descripcion" maxlength="100" required value="<?= htmlspecialchars($datos_form['descripcion'] ?? '') ?>">
                    <span id="counter-descripcion" class="char-counter">100 restantes</span>

                    <label for="cta_url">URL de Destino (Link) (Requisito 9)</label>
                    <input type="text" name="cta_url" id="cta_url" placeholder="https://ejemplo.com/" required value="<?= htmlspecialchars($datos_form['cta_url'] ?? '') ?>">
                </div>

                <div>
                    <h2>Límites y Reglas</h2>
                    
                    <label for="posicion">Posición del Banner (Requisito 8)</label>
                    <select name="posicion" id="posicion" required>
                        <option value="top" <?= ($datos_form['posicion'] ?? 'top') === 'top' ? 'selected' : '' ?>>Arriba (Flotante Superior)</option>
                        <option value="bottom" <?= ($datos_form['posicion'] ?? 'top') === 'bottom' ? 'selected' : '' ?>>Abajo (Flotante Inferior)</option>
                    </select>
                    
                    <h3>Límites de Campaña</h3>
                    <label for="max_impresiones">Máx. Impresiones (Vistas) (Requisito 3)</label>
                    <input type="number" name="max_impresiones" id="max_impresiones" min="1" required value="<?= $datos_form['max_impresiones'] ?? 50000 ?>">

                    <label for="max_clicks">Máx. Clicks (Requisito 2)</label>
                    <input type="number" name="max_clicks" id="max_clicks" min="1" required value="<?= $datos_form['max_clicks'] ?? 500 ?>">

                    <h3>Tiempos y Frecuencia (Requisito 4)</h3>
                    <label for="tiempo_muestra">Duración Visible (ms)</label>
                    <input type="number" name="tiempo_muestra" id="tiempo_muestra" min="1000" required value="<?= $datos_form['tiempo_muestra'] ?? 12000 ?>">
                    <div class="char-counter">12000 ms = 12 segundos</div>

                    <label for="frecuencia_factor">Factor de Frecuencia (1 = Siempre, 3 = 1 de cada 3)</label>
                    <input type="number" name="frecuencia_factor" id="frecuencia_factor" min="1" required value="<?= $datos_form['frecuencia_factor'] ?? 2 ?>">
                </div>
                
                <div class="full-width">
                    <label for="logo_url_select">Seleccionar Logo (Optimizado)</label>
                    <select name="logo_url" id="logo_url_select" required onchange="toggleManualUrl(this.value)">
                        <?php foreach($logos_aprobados as $url => $desc): ?>
                            <option value="<?= htmlspecialchars($url) ?>" <?= ($datos_form['logo_url'] ?? '') === $url ? 'selected' : '' ?>>
                                <?= htmlspecialchars($desc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="logo_url_manual_wrapper">
                        <label for="logo_url_manual">URL Manual del Logo:</label>
                        <input type="text" name="logo_url_manual" id="logo_url_manual" placeholder="/uploads/banners/logo_custom.png" value="<?= htmlspecialchars($datos_form['logo_url'] ?? '') ?>">
                    </div>
                </div>

                <div class="full-width">
                    <button type="submit"><?= $modo_edicion ? 'Actualizar Banner' : 'Guardar Nuevo Banner' ?></button>
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

        function toggleManualUrl(selectedValue) {
            const wrapper = document.getElementById('logo_url_manual_wrapper');
            if (selectedValue === '') {
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateCounter('titulo', 'counter-titulo', 25);
            updateCounter('descripcion', 'counter-descripcion', 100); 
            
            const select = document.getElementById('logo_url_select');
            
            if (select.value === '') {
                toggleManualUrl('');
            } else {
                const isApproved = select.querySelector(`option[value='${select.value}']`);
                if (!isApproved) {
                    toggleManualUrl('');
                }
            }
        });
    </script>
</body>
</html>
