<?php
/**
 * index.php
 * Versión 37.0 - Tracking de Instalaciones (GA4)
 */

// 1. Configuración inicial
date_default_timezone_set('America/Bogota');
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once 'config-ciudades.php';
require_once 'clases/PicoYPlaca.php';

// --- 1.1 AUTO-GENERADOR DE SITEMAP ---
$sitemapFile = __DIR__ . '/sitemap.xml';
if (!file_exists($sitemapFile) || date('Y-m-d', filemtime($sitemapFile)) !== date('Y-m-d')) {
    $BASE_URL_SM = 'https://picoyplacabogota.com.co'; 
    $DIAS_SM = 45; 
    $MESES_SM = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    $hoy_sm = date('Y-m-d');
    $xml .= "  <url><loc>{$BASE_URL_SM}/</loc><lastmod>{$hoy_sm}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
    $xml .= "  <url><loc>{$BASE_URL_SM}/pico-y-placa-bogota-hoy</loc><lastmod>{$hoy_sm}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
    $xml .= "  <url><loc>{$BASE_URL_SM}/pico-y-placa-bogota-mañana</loc><lastmod>{$hoy_sm}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";

    $now_sm = new DateTime();
    $ciudades_sm = $ciudades; 
    if(isset($ciudades_sm['rotaciones_base'])) unset($ciudades_sm['rotaciones_base']);

    for ($i = 0; $i < $DIAS_SM; $i++) {
        $curDate = clone $now_sm;
        $curDate->modify("+$i day");
        $d_sm = (int)$curDate->format('d');
        $m_num_sm = $curDate->format('m');
        $y_sm = $curDate->format('Y');
        $slug_fecha = sprintf('%d-de-%s-de-%s', $d_sm, $MESES_SM[$m_num_sm], $y_sm);

        foreach ($ciudades_sm as $c_slug => $c_data) {
            $loc = "{$BASE_URL_SM}/pico-y-placa/{$c_slug}-{$slug_fecha}";
            $prio = number_format(max(0.5, 0.9 - ($i / $DIAS_SM)), 2, '.', '');
            $xml .= "  <url><loc>{$loc}</loc><lastmod>{$hoy_sm}</lastmod><changefreq>daily</changefreq><priority>{$prio}</priority></url>\n";
        }
    }
    $xml .= '</urlset>';
    file_put_contents($sitemapFile, $xml); 
}

$picoYPlaca = new PicoYPlaca();
if(isset($ciudades['rotaciones_base'])) unset($ciudades['rotaciones_base']);

// Datos Globales
$HOY = date('Y-m-d'); 
$DEFAULT_CIUDAD_URL = 'bogota';
$DEFAULT_TIPO_URL = 'particulares';
$MULTA_VALOR = '650.000'; 
$BASE_URL = 'https://picoyplacabogota.com.co';
$SITIO_NOMBRE = 'PicoYPlacaBogota.com.co';

$MESES = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
$MESES_CORTOS = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
$DIAS_SEMANA = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];

// Enrutamiento
$es_busqueda = false; 
$es_manana = false; 
$es_hoy = false; 
$special_slug = $_GET['special_slug'] ?? null; 
$ciudad_busqueda = $_GET['ciudad_slug'] ?? $DEFAULT_CIUDAD_URL;
$tipo_busqueda = $_GET['tipo'] ?? $DEFAULT_TIPO_URL; 
$fecha_busqueda = $HOY;
$canonical_url = $BASE_URL;

// Lógica de detección de búsqueda
if ($special_slug === 'hoy') {
    $es_busqueda = true;
    $es_hoy = true;
    $fecha_busqueda = $HOY;
    $ciudad_busqueda = $_GET['ciudad_slug'] ?? 'bogota'; 
    if ($ciudad_busqueda === 'bogota') {
        $canonical_url = $BASE_URL . "/pico-y-placa-bogota-hoy";
    }
    if(isset($_GET['tipo'])) $tipo_busqueda = $_GET['tipo'];

} elseif ($special_slug === 'manana') {
    $es_busqueda = true;
    $es_manana = true;
    $fecha_busqueda = date('Y-m-d', strtotime('+1 day')); 
    $ciudad_busqueda = 'bogota'; 
    $canonical_url = $BASE_URL . "/pico-y-placa-bogota-mañana";
    if(isset($_GET['tipo'])) $tipo_busqueda = $_GET['tipo'];

} elseif (isset($_GET['dia']) && isset($_GET['mes_nombre']) && isset($_GET['ano'])) {
    $es_busqueda = true;
    $mes_num = array_search(strtolower($_GET['mes_nombre']), $MESES); 
    if ($mes_num) {
        $fecha_busqueda = $_GET['ano'].'-'.$mes_num.'-'.str_pad($_GET['dia'], 2, '0', STR_PAD_LEFT);
        $slug_fecha = sprintf('%d-de-%s-de-%s', $_GET['dia'], $_GET['mes_nombre'], $_GET['ano']);
        $canonical_url = $BASE_URL . "/pico-y-placa/{$ciudad_busqueda}-{$slug_fecha}";
        if ($fecha_busqueda === $HOY) {
            $es_hoy = true;
        }
    }
} else {
     // HOME
     if ($fecha_busqueda === $HOY && $ciudad_busqueda === $DEFAULT_CIUDAD_URL && $tipo_busqueda === $DEFAULT_TIPO_URL) {
        $es_busqueda = false; 
        $es_hoy = true; 
        $canonical_url = $BASE_URL . "/";
    }
}

// Validaciones
if (!array_key_exists($ciudad_busqueda, $ciudades)) $ciudad_busqueda = $DEFAULT_CIUDAD_URL;
if (!isset($ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda])) {
    $tipo_busqueda = array_key_first($ciudades[$ciudad_busqueda]['tipos']);
}

// Consultar Restricción
$resultados = $picoYPlaca->obtenerRestriccion($ciudad_busqueda, $fecha_busqueda, $tipo_busqueda);
$nombre_festivo = $resultados['festivo'] ?? null;

$nombre_ciudad = $ciudades[$ciudad_busqueda]['nombre'];
$nombre_tipo = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['nombre_display'];

$dt = new DateTime($fecha_busqueda);
$dia_nombre = ucfirst($DIAS_SEMANA[$dt->format('N')]); 
$mes_nombre = ucfirst($MESES[$dt->format('m')]);        
$mes_nombre_min = $MESES[$dt->format('m')]; 
$dia_num    = $dt->format('j');                         
$anio       = $dt->format('Y');                         
$fecha_texto_largo = "$dia_nombre, $dia_num de $mes_nombre de $anio";
$fecha_seo_corta = "$dia_num de $mes_nombre"; 

$d_hoy_link = date('j');
$m_hoy_link = $MESES[date('m')];
$a_hoy_link = date('Y');
$url_hoy_dinamica = "/pico-y-placa/{$ciudad_busqueda}-{$d_hoy_link}-de-{$m_hoy_link}-de-{$a_hoy_link}";

$hora_ini_txt = "6:00 AM"; $hora_fin_txt = "9:00 PM";
if ($resultados['hay_pico']) {
    $rangos = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['rangos_horarios_php'] ?? [];
    if (!empty($rangos)) {
        $hora_ini_txt = $rangos[0]['inicio'];
        $ultimo_rango = end($rangos);
        $hora_fin_txt = $ultimo_rango['fin'];
    }
}

// --- SEO TEXTOS ---
$main_keyword = "Pico y Placa $nombre_ciudad"; 
$search_description_text = "";
$search_target_ts = 0;
$reloj_label_dinamico = "FALTA:"; 

if ($es_hoy) {
    $main_keyword = "Pico y Placa $nombre_ciudad Hoy";
    $titulo_h1_largo = "$main_keyword $dia_nombre $fecha_seo_corta $nombre_tipo";
    $page_title = "$titulo_h1_largo";
    $meta_description = "$main_keyword $dia_nombre $fecha_seo_corta. Revisa al instante las placas restringidas para $nombre_tipo y evita multas hoy en $nombre_ciudad.";
    if ($nombre_festivo) {
        $search_description_text = "Hoy despues de mucho nos llega un Festivo, ve a lavar tu carrito, se lo merece, anda tranquilo que es <strong>$dia_nombre</strong> Festivo y se celebra <strong>$nombre_festivo</strong> NO HAY PICO Y PLACA.";
    } elseif (!$resultados['hay_pico'] && ($dt->format('N') == 6 || $dt->format('N') == 7)) {
        $search_description_text = "Hoy es <strong>$dia_nombre</strong>... todo es paz y armonia, hasta los policias duermen 😴, sal, vuela pajarito, hoy no tienes pico y placa por que es <strong>$dia_nombre</strong>.";
    } elseif ($resultados['hay_pico']) {
        $placas_malas = implode(', ', $resultados['restricciones']);
        $placas_buenas = implode(', ', $resultados['permitidas']);
        $search_description_text = "Hoy tienen el pico y placa los vehículos de placa <strong>$placas_malas</strong> y no tienen pico y placa los afortunados de las placas <strong>$placas_buenas</strong> Felicidades por ustedes.";
    } else {
        $search_description_text = "Hoy $dia_nombre NO APLICA la medida para $nombre_tipo 🎉. Puedes circular libremente sin riesgo de multa 🚗💨.";
    }
} elseif ($es_manana) {
    $main_keyword = "Pico y Placa $nombre_ciudad Mañana";
    $titulo_h1_largo = "$main_keyword $dia_nombre $fecha_seo_corta $nombre_tipo";
    $page_title = "$titulo_h1_largo";
    $meta_description = "$main_keyword $dia_nombre $fecha_seo_corta. Evita multas y consulta las placas restringidas de $nombre_tipo para mañana en $nombre_ciudad.";
    if ($resultados['hay_pico']) {
        $search_description_text = "$main_keyword $dia_nombre aplica desde <strong>$hora_ini_txt</strong> hasta las <strong>$hora_fin_txt</strong> para $nombre_tipo. Evita los comparendos 💸 que te pueden dañar el día 😫.";
    } else {
        $search_description_text = "Mañana $dia_nombre NO APLICA la medida para $nombre_tipo 🎉. Puedes circular libremente sin riesgo de multa 🚗💨.";
    }
} else {
    $titulo_h1_largo = "$main_keyword: $dia_num de $mes_nombre_min de $anio";
    $page_title = "$titulo_h1_largo | $SITIO_NOMBRE";
    $meta_description = "$main_keyword el $dia_nombre $fecha_seo_corta para $nombre_tipo. Consulta placas restringidas, horarios y multas.";
    if ($resultados['hay_pico']) {
        $search_description_text = "El <strong>$main_keyword</strong> para $nombre_tipo el $dia_nombre $fecha_seo_corta aplica en horario de <strong>$hora_ini_txt</strong> a <strong>$hora_fin_txt</strong>.";
    } else {
        $search_description_text = "Para el $dia_nombre $fecha_seo_corta no hay medida de Pico y Placa para $nombre_tipo en $nombre_ciudad.";
    }
}

// --- RELOJ ---
if ($resultados['hay_pico']) {
    $now_ts = time(); 
    $rangos = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['rangos_horarios_php'] ?? [];
    foreach ($rangos as $r) {
        $inicio_ts = strtotime("$fecha_busqueda " . $r['inicio']);
        $fin_ts = strtotime("$fecha_busqueda " . $r['fin']);
        if ($fin_ts < $inicio_ts) $fin_ts += 86400;
        if ($now_ts < $inicio_ts) {
            $search_target_ts = $inicio_ts * 1000;
            $reloj_label_dinamico = "INICIA EN:";
            break; 
        } elseif ($now_ts >= $inicio_ts && $now_ts < $fin_ts) {
            $search_target_ts = $fin_ts * 1000;
            $reloj_label_dinamico = "TERMINA EN:";
            break; 
        }
    }
}

$keywords_list = ["$main_keyword", "pico y placa $nombre_ciudad hoy $nombre_tipo", "$main_keyword $fecha_seo_corta", "placas $nombre_ciudad"];
$meta_keywords = implode(", ", $keywords_list);

$next_event_ts = 0; $reloj_titulo = "FALTA:";
if ($fecha_busqueda === $HOY && $search_target_ts > 0) {
    $next_event_ts = $search_target_ts;
    $reloj_titulo = $reloj_label_dinamico;
}

// Proyección
$calendario_personalizado = [];
$placa_proyeccion = $_POST['placa_proyeccion'] ?? null; 
$ciudad_proyeccion = $_POST['ciudad_proyeccion'] ?? $ciudad_busqueda;
$tipo_proyeccion = $_POST['tipo_proyeccion'] ?? $tipo_busqueda;
$mostrar_proyeccion = false;
if ($placa_proyeccion !== null && is_numeric($placa_proyeccion)) {
    $mostrar_proyeccion = true;
    $fecha_p = new DateTime($HOY);
    for ($j = 0; $j < 30; $j++) {
        $f_str = $fecha_p->format('Y-m-d');
        $res_p = $picoYPlaca->obtenerRestriccion($ciudad_proyeccion, $f_str, $tipo_proyeccion);
        if ($res_p['hay_pico'] && in_array($placa_proyeccion, $res_p['restricciones'])) {
            $calendario_personalizado[] = [
                'fecha_larga' => ucfirst($DIAS_SEMANA[$fecha_p->format('N')]) . ' ' . $fecha_p->format('j') . ' de ' . $MESES[$fecha_p->format('m')],
                'horario' => $res_p['horario']
            ];
        }
        $fecha_p->modify('+1 day');
    }
}

// Calendario
$calendario = [];
$fecha_iter = new DateTime($HOY);
for ($i = 0; $i < 30; $i++) {
    $f_str = $fecha_iter->format('Y-m-d');
    $res = $picoYPlaca->obtenerRestriccion($ciudad_busqueda, $f_str, $tipo_busqueda);
    $estado_dia = $res['hay_pico'] ? 'restriccion_general' : 'libre';
    $mensaje_dia = $res['hay_pico'] ? implode('-', $res['restricciones']) : 'Libre';
    if ($res['festivo']) {
        $mensaje_dia = "<span class='festivo-label'>🎉 {$res['festivo']}</span>";
        if (!$res['hay_pico']) $estado_dia = 'libre';
    }
    $calendario[] = [
        'd' => $fecha_iter->format('j'),
        'm' => substr(ucfirst($MESES[$fecha_iter->format('m')]), 0, 3),
        'dia' => ucfirst($DIAS_SEMANA[$fecha_iter->format('N')]),
        'estado' => $estado_dia,
        'mensaje' => $mensaje_dia
    ];
    $fecha_iter->modify('+1 day');
}

$bg_search_color = $resultados['hay_pico'] ? '#fff5f5' : '#f0fff4';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <link rel="manifest" href="/favicons/manifest.json">
    <meta name="theme-color" content="<?= $es_busqueda ? $bg_search_color : ($resultados['hay_pico'] ? '#e74c3c' : '#84fab0') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    
    <link rel="stylesheet" href="/styles.css?v=36.0">
    
    <?php if($es_busqueda): ?>
    <style>
        body { background-color: <?= $bg_search_color ?>; }
        .search-description { border-left: 6px solid <?= $resultados['hay_pico'] ? '#e74c3c' : '#27ae60' ?>; }
    </style>
    <?php endif; ?>
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2L2EV10ZWW"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-2L2EV10ZWW');
    </script>
</head>
<body class="<?= $es_busqueda ? 'search-result-mode' : ($body_class_mode ?? 'home-mode') ?>" data-city-slug="<?= $ciudad_busqueda ?>">
    
    <script src="/ads/ads.js?v=3.0"></script>
    
<?php if ($es_busqueda): ?>

    <main class="search-result-container">
        <h1 class="search-title-h1"><?= $titulo_h1_largo ?></h1>

        <div class="search-description">
            <p><?= $search_description_text ?></p>
            <?php if($search_target_ts > 0): ?>
                <div class="countdown-badge">⏳ <?= $reloj_label_dinamico ?> <span id="search-timer">calculando...</span></div>
            <?php elseif($resultados['hay_pico']): ?>
                 <div class="countdown-badge" style="background:#27ae60;">✅ Medida finalizada por hoy</div>
            <?php endif; ?>
        </div>
        
        <div class="vehicle-selector-container">
            <span class="selector-label">👇 Cambiar tipo de vehículo:</span>
            <div class="vehicle-options">
                <?php foreach($ciudades[$ciudad_busqueda]['tipos'] as $keyTipo => $dataTipo): 
                    $linkTipo = "?";
                    if ($es_hoy && $ciudad_busqueda === 'bogota') {
                        $linkTipo = "/pico-y-placa-bogota-hoy?ciudad_slug={$ciudad_busqueda}&tipo={$keyTipo}&special_slug=hoy";
                    } elseif ($es_manana && $ciudad_busqueda === 'bogota') {
                        $linkTipo = "/pico-y-placa-bogota-mañana?ciudad_slug={$ciudad_busqueda}&tipo={$keyTipo}&special_slug=manana";
                    } else {
                        $mes_url = strtolower($mes_nombre_min); 
                        $linkTipo = "/pico-y-placa/{$ciudad_busqueda}-{$dia_num}-de-{$mes_url}-de-{$anio}?tipo={$keyTipo}";
                    }
                    $isActive = ($keyTipo === $tipo_busqueda) ? 'active' : '';
                ?>
                    <a href="<?= $linkTipo ?>" class="btn-vehicle-type <?= $isActive ?>"><?= $dataTipo['nombre_display'] ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($resultados['hay_pico']): ?>
            <section class="plates-section">
                <div class="plates-card restricted">
                    <h2 class="plates-header-h2">🚫 Restricción de movilidad para las placas:</h2>
                    <div class="plate-number-big"><?= implode(' - ', $resultados['restricciones']) ?></div>
                    <h3 class="info-detail-h3">Horario: <?= $resultados['horario'] ?></h3>
                </div>
                <div class="plates-card allowed">
                    <h2 class="plates-header-h2">✅ Placas sin pico y placa el <?= $dia_nombre ?>:</h2>
                    <div class="plate-number-big"><?= implode(' - ', $resultados['permitidas']) ?></div>
                    <h3 class="info-detail-h3">Pueden circular libremente</h3>
                </div>
            </section>
        <?php else: ?>
            <section class="plates-section">
                <div class="plates-card allowed">
                    <h2 class="plates-header-h2">🎉 ¡No hay restricción!</h2>
                    <div class="free-day-text"><?= $nombre_festivo ? "¡Es Festivo: $nombre_festivo!" : "Día Libre de Medida" ?></div>
                    <p style="margin-top:15px; font-size:1.1em;">Todos los vehículos de tipo <strong><?= $nombre_tipo ?></strong> pueden circular sin riesgo de multa.</p>
                </div>
             </section>
        <?php endif; ?>

        <div class="action-buttons">
            <?php if(!$es_hoy): ?>
                <a href="<?= $url_hoy_dinamica ?>" class="btn-new-action btn-primary-search" style="background: #2ecc71; border-color: #2ecc71;">📍 Ver Pico y Placa HOY en <?= $nombre_ciudad ?></a>
            <?php endif; ?>
            <a href="/?ciudad_slug=<?= $ciudad_busqueda ?>" class="btn-new-action btn-primary-search">📅 Consultar otra fecha</a>
            <a href="/" class="btn-new-action btn-secondary-search">🏚️ Inicio</a>
        </div>

        <footer class="footer-simple">
            &copy; <?= date('Y') ?> PicoYPlacaBogota.com.co - Información Informativa. <br>
            <a href="/contacto.php" style="color:#7f8c8d;">Contacto</a> | <a href="/" style="color:#7f8c8d;">Inicio</a>
        </footer>
    </main>

    <div class="floating-share-container">
        <div class="share-options" id="shareOptions">
            <a href="#" class="share-btn whatsapp" id="share-wa" target="_blank" aria-label="Compartir en WhatsApp">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.023.972 3.152.972 3.182 0 5.768-2.586 5.769-5.766.001-3.18-2.585-5.766-5.769-5.766zm9.969 5.766c0 5.504-4.478 9.982-9.982 9.982a9.92 9.92 0 0 1-4.703-1.185l-5.316 1.396 1.42-5.176a9.92 9.92 0 0 1-1.326-4.966C2.045 6.517 6.523 2.04 12.027 2.04s9.982 4.478 9.982 9.982h-.009z"/></svg>
            </a>
            <a href="#" class="share-btn facebook" id="share-fb" target="_blank" aria-label="Compartir en Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036c-2.048 0-2.732 1.35-2.732 3.075v1.272h3.662l-.615 3.667h-3.047v7.98H9.101z"/></svg>
            </a>
            <a href="#" class="share-btn xcom" id="share-x" target="_blank" aria-label="Compartir en X">
                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>
            </a>
        </div>
        <button class="main-fab-btn" id="toggleShare" aria-label="Compartir">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
        </button>
    </div>

    <button id="btn-pwa-install" class="pwa-install-fab" style="display:none;">
        📲 Instalar App
        <span class="close-pwa" id="closePwaBtn">✕</span>
    </button>
    
    <div id="ios-prompt" class="install-toast" style="display:none; position:fixed; bottom:20px; left:20px; right:20px; z-index:10000; flex-direction:column; text-align:center;">
        <div style="margin-bottom:5px;">📲 <strong>Instalar en iPhone:</strong></div>
        <div style="font-size:0.85em;">Toca <strong>Compartir</strong> y elige <strong>"Agregar a Inicio"</strong>.</div>
        <button id="btn-close-ios" class="btn-close-install" style="margin-top:10px;">Cerrar ✕</button>
    </div>

    <script>
        // --- LOGICA DEL WIDGET COMPARTIR ---
        const pageTitle = encodeURIComponent(document.title);
        const pageUrl = encodeURIComponent(window.location.href);

        document.getElementById('share-wa').href = `https://api.whatsapp.com/send?text=${pageTitle} ${pageUrl}`;
        document.getElementById('share-fb').href = `https://www.facebook.com/sharer/sharer.php?u=${pageUrl}`;
        document.getElementById('share-x').href = `https://twitter.com/intent/tweet?text=${pageTitle}&url=${pageUrl}`;

        document.getElementById('toggleShare').addEventListener('click', function() {
            document.getElementById('shareOptions').classList.toggle('active');
            this.classList.toggle('active');
        });

        // --- LOGICA PWA ACTUALIZADA ---
        let deferredPrompt;
        const btnInstall = document.getElementById('btn-pwa-install');
        const btnClosePwa = document.getElementById('closePwaBtn');
        const iosPrompt = document.getElementById('ios-prompt');
        const btnCloseIos = document.getElementById('btn-close-ios');

        // Cerrar botón flotante
        btnClosePwa.addEventListener('click', (e) => {
            e.stopPropagation(); // Evitar click en el botón padre
            btnInstall.style.display = 'none';
        });

        // Cerrar toast iOS
        btnCloseIos.addEventListener('click', () => { iosPrompt.style.display = 'none'; });

        // 1. Android / Desktop (Chrome/Edge)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Mostrar botón flotante
            btnInstall.style.display = 'flex';
            
            btnInstall.addEventListener('click', (ev) => {
                // Si hizo click en la X ya se manejó arriba, si hizo click en el resto:
                if(ev.target !== btnClosePwa) {
                    btnInstall.style.display = 'none';
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('Usuario aceptó instalar');
                        }
                        deferredPrompt = null;
                    });
                }
            });
        });

        // 2. Detectar iOS para mostrar botón que abre instrucciones
        const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        const isInStandaloneMode = ('standalone' in window.navigator) && (window.navigator.standalone);

        if (isIos && !isInStandaloneMode) {
            // En iOS no hay "prompt" automático, pero mostramos el botón para abrir las instrucciones
            btnInstall.style.display = 'flex';
            btnInstall.addEventListener('click', (ev) => {
                 if(ev.target !== btnClosePwa) {
                     iosPrompt.style.display = 'flex'; // Mostrar instrucciones
                 }
            });
        }

        // --- REGISTRO DEL SERVICE WORKER ---
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('ServiceWorker registrado con éxito: ', registration.scope);
                }, function(err) {
                    console.log('Fallo al registrar ServiceWorker: ', err);
                });
            });
        }

        // --- TRACKING DE INSTALACIÓN (NUEVO) ---
        window.addEventListener('appinstalled', (evt) => {
            console.log('PWA instalada correctamente');
            // Enviamos el evento a GA4
            gtag('event', 'pwa_install', {
                'event_category': 'Engagement',
                'event_label': 'Install Success'
            });
        });

        // --- COUNTDOWN SEARCH ---
        const TARGET_TS = <?= $search_target_ts ?>;
        const SERVER_NOW = <?= time() * 1000 ?>;
        const OFFSET = new Date().getTime() - SERVER_NOW;
        function updateSearchTimer() {
            if(TARGET_TS === 0) return;
            const now = new Date().getTime() - OFFSET;
            const diff = TARGET_TS - now;
            const el = document.getElementById('search-timer');
            if(!el) return;
            if (diff <= 0) {
                el.innerText = "00h 00m 00s"; setTimeout(() => location.reload(), 2000); return;
            }
            let days = Math.floor(diff / (1000 * 60 * 60 * 24));
            let h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            let s = Math.floor((diff % (1000 * 60)) / 1000);
            let txt = (days > 0 ? days + "d " : "") + (h<10?'0':'') + h + "h " + (m<10?'0':'') + m + "m " + (s<10?'0':'') + s + "s";
            el.innerText = txt;
        }
        setInterval(updateSearchTimer, 1000);
        updateSearchTimer();
    </script>

<?php else: ?>

    <div class="floating-share-container">
        <div class="share-options" id="shareOptions">
            <a href="#" class="share-btn whatsapp" id="share-wa" target="_blank" aria-label="Compartir en WhatsApp">
                 <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.023.972 3.152.972 3.182 0 5.768-2.586 5.769-5.766.001-3.18-2.585-5.766-5.769-5.766zm9.969 5.766c0 5.504-4.478 9.982-9.982 9.982a9.92 9.92 0 0 1-4.703-1.185l-5.316 1.396 1.42-5.176a9.92 9.92 0 0 1-1.326-4.966C2.045 6.517 6.523 2.04 12.027 2.04s9.982 4.478 9.982 9.982h-.009z"/></svg>
            </a>
            <a href="#" class="share-btn facebook" id="share-fb" target="_blank" aria-label="Compartir en Facebook">
                 <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036c-2.048 0-2.732 1.35-2.732 3.075v1.272h3.662l-.615 3.667h-3.047v7.98H9.101z"/></svg>
            </a>
            <a href="#" class="share-btn xcom" id="share-x" target="_blank" aria-label="Compartir en X">
                 <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>
            </a>
        </div>
        <button class="main-fab-btn" id="toggleShare" aria-label="Compartir">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
        </button>
    </div>

    <button id="btn-pwa-install" class="pwa-install-fab" style="display:none;">
        📲 Instalar App
        <span class="close-pwa" id="closePwaBtn">✕</span>
    </button>
    
    <div id="ios-prompt" class="install-toast" style="display:none; position:fixed; bottom:20px; left:20px; right:20px; z-index:10000; flex-direction:column; text-align:center;">
        <div style="margin-bottom:5px;">📲 <strong>Instalar en iPhone:</strong></div>
        <div style="font-size:0.85em;">Toca <strong>Compartir</strong> y elige <strong>"Agregar a Inicio"</strong>.</div>
        <button id="btn-close-ios" class="btn-close-install" style="margin-top:10px;">Cerrar ✕</button>
    </div>

   <header class="app-header">
        <div class="header-content">
            <span class="car-icon">🚗</span>
            <h1 class="app-title"><?= $titulo_h1_largo ?></h1>
            <p class="app-subtitle" style="line-height:1.5; font-size:0.95rem; margin-top:10px; max-width:600px; margin-left:auto; margin-right:auto;"><?= $search_description_text ?></p>
        </div>
    </header>

    <div class="nav-tomorrow-wrapper" style="text-align:center; margin-bottom:15px; display:flex; justify-content:center; gap:10px;">
        <?php if($special_slug !== 'manana'): ?> <a href="/pico-y-placa-bogota-mañana" class="btn-tomorrow-float">🚀 Ver <strong>MAÑANA</strong></a> <?php endif; ?>
        <?php if(!$es_hoy): ?> <a href="<?= $url_hoy_dinamica ?>" class="btn-tomorrow-float" style="background: #2ecc71;">📅 Ver <strong>HOY</strong></a> <?php endif; ?>
    </div>

    <main class="app-container">
        <section class="card-dashboard search-card area-search">
            <h2 class="card-header-icon">📅 Buscar otra fecha</h2>
            <form action="/buscar.php" method="POST" class="search-form-grid">
                <div class="input-wrapper full-width">
                    <input type="date" name="fecha" value="<?= $fecha_busqueda ?>" required min="2020-01-01" max="2030-12-31" class="app-input">
                </div>
                <div class="input-wrapper">
                    <select name="ciudad" id="sel-ciudad" class="app-select">
                        <?php foreach($ciudades as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $k===$ciudad_busqueda?'selected':'' ?>><?= $v['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-wrapper">
                    <select name="tipo" id="sel-tipo" class="app-select"></select>
                </div>
                <div class="actions-wrapper full-width" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-app-primary" style="flex: 2;">Buscar</button>
                </div>
            </form>
        </section>

        <?php if (!empty($ciudades[$ciudad_busqueda]['contenido_seo'])): ?>
        <section class="seo-accordion-wrapper area-seo">
            <details class="seo-details">
                <summary class="seo-summary">
                    <h2 style="display:inline; font-size:inherit; margin:0; font-weight:600;">ℹ️ Normativa en <?= $nombre_ciudad ?></h2>
                    <span class="icon-toggle">▼</span>
                </summary>
                <div class="seo-content"><?= $ciudades[$ciudad_busqueda]['contenido_seo'] ?></div>
            </details>
        </section>
        <?php endif; ?>

        <section class="quick-stats-grid area-stats">
            <div class="stat-card purple-gradient">
                <div class="stat-icon">📅 FECHA</div>
                <div class="stat-value small-text"><?= ucfirst($DIAS_SEMANA[$dt->format('N')]) ?><br><?= $dt->format('j') ?> de <?= ucfirst($MESES[$dt->format('m')]) ?></div>
            </div>
            <div class="stat-card purple-gradient">
                <div class="stat-icon">🚫 RESTRICCIÓN</div>
                <div class="stat-value big-text"><?= $resultados['hay_pico'] ? implode(', ', $resultados['restricciones']) : "NO" ?></div>
            </div>
            <div class="stat-card purple-gradient">
                <div class="stat-icon">🕒 HORARIO</div>
                <div class="stat-value small-text"><?= $resultados['hay_pico'] ? $resultados['horario'] : 'Libre' ?></div>
            </div>
        </section>

        <?php
        $es_restriccion_activa = false;
        $ya_paso_restriccion_hoy = false;
        if ($resultados['hay_pico'] && $fecha_busqueda === $HOY) {
            $now_ts = time();
            $rangos_check = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['rangos_horarios_php'] ?? [];
            foreach ($rangos_check as $r) {
                $i_ts = strtotime("$HOY " . $r['inicio']);
                $f_ts = strtotime("$HOY " . $r['fin']);
                if ($f_ts < $i_ts) $f_ts += 86400; 
                if ($now_ts >= $i_ts && $now_ts < $f_ts) { $es_restriccion_activa = true; break; }
            }
        }
        ?>

        <section class="card-dashboard status-card area-status" style="background-color: <?= $es_restriccion_activa ? '#fff5f5' : '#f0fff4' ?>; border-left: 5px solid <?= $es_restriccion_activa ? '#d63031' : '#00b894' ?>;">
            <h2 class="status-header" style="margin-bottom:10px;">
                <?= ($resultados['hay_pico'] && !$ya_paso_restriccion_hoy) ? "🚫 HAY PICO Y PLACA" : "✅ SIN RESTRICCIÓN" ?>
            </h2>
            <?php if ($next_event_ts > 0): ?>
            <div id="countdown-section">
                <div class="timer-label">⏳ <?= $reloj_titulo ?></div>
                <div class="timer-container">
                    <div class="time-box"><span id="cd-h">00</span><small>Hs</small></div> <div class="time-sep">:</div>
                    <div class="time-box"><span id="cd-m">00</span><small>Min</small></div> <div class="time-sep">:</div>
                    <div class="time-box"><span id="cd-s">00</span><small>Seg</small></div>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <section class="city-tags-section area-cities">
            <h2>Pico y Placa próximos días</h2>
            <div class="city-tags-grid">
                <?php for($i=1; $i<=6; $i++):
                    $f_futura = new DateTime($fecha_busqueda); $f_futura->modify("+$i day");
                    $d_f = $f_futura->format('j'); $m_f = $MESES[$f_futura->format('m')]; $a_f = $f_futura->format('Y');
                    $url_futura = "/pico-y-placa/$ciudad_busqueda-$d_f-de-$m_f-de-$a_f";
                ?>
                    <a href="<?= $url_futura ?>" class="city-tag"><?= ucfirst($DIAS_SEMANA[$f_futura->format('N')]) ?> <?= $d_f ?></a>
                <?php endfor; ?>
                <h2 style="width:100%; margin-top:20px; font-size:1.3em;">Otras Ciudades</h2>
                 <?php foreach($ciudades as $k => $v): if($k === $ciudad_busqueda) continue;
                    $d_hoy_loop = date('j'); $m_hoy_loop = $MESES[date('m')]; $a_hoy_loop = date('Y');
                    $url = "/pico-y-placa/$k-$d_hoy_loop-de-$m_hoy_loop-de-$a_hoy_loop";
                ?>
                    <a href="<?= $url ?>" class="city-tag"><?= $v['nombre'] ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card-dashboard calc-card area-calc">
            <h2>Proyección Mes Pico y Placa</h2>
            <form action="#proyeccion" method="POST" class="calc-form">
                <input type="hidden" name="ciudad_proyeccion" value="<?= $ciudad_busqueda ?>">
                <input type="hidden" name="tipo_proyeccion" value="<?= $tipo_busqueda ?>">
                <label class="placa-label">Ingresa último dígito:</label>
                <div class="calc-row">
                    <input type="number" name="placa_proyeccion" placeholder="0" min="0" max="9" class="app-input big-input" value="<?= $placa_proyeccion ?>">
                    <button type="submit" class="btn-app-primary" style="margin-top:0;">Ver Días</button>
                </div>
            </form>
            <?php if ($mostrar_proyeccion): ?>
                <div id="proyeccion" class="proyeccion-result">
                    <h3>📅 Resultados para Placa <?= $placa_proyeccion ?>:</h3>
                    <?php if (empty($calendario_personalizado)): ?>
                        <p class="free-text">✅ ¡Todo libre! No tienes pico y placa.</p>
                    <?php else: ?>
                        <ul class="dates-list">
                            <?php foreach($calendario_personalizado as $dp): ?>
                                <li><strong><?= $dp['fecha_larga'] ?></strong> <br> <span style="color:#d63031;"><?= $dp['horario'] ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card-dashboard details-card area-details">
            <h2>Detalle Placas</h2>
            <div class="city-subtitle"><?= $nombre_ciudad ?> (<?= $nombre_tipo ?>)</div>
            <?php if ($resultados['hay_pico']): ?>
                <div class="plate-group">
                    <h3>🚫 Con restricción:</h3>
                    <div class="circles-container">
                        <?php foreach($resultados['restricciones'] as $p) echo "<div class='plate-circle pink'>$p</div>"; ?>
                    </div>
                </div>
                <div class="plate-group">
                    <h3>✅ Habilitadas:</h3>
                    <div class="circles-container">
                        <?php foreach($resultados['permitidas'] as $p) echo "<div class='plate-circle green'>$p</div>"; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="free-alert">✅ ¡Todo habilitado!</div>
            <?php endif; ?>
        </section>

        <section class="card-dashboard calendar-card area-calendar">
            <h2>🗓️ Calendario General (30 Días)</h2>
            <div style="text-align:center; margin-bottom:15px;">
                <button id="btn-toggle-cal" class="btn-app-flashy" onclick="toggleCalendario()">Ver Calendario Completo</button>
            </div>
            <div id="calendario-grid" class="calendario-grid" style="display:none;">
                <?php foreach($calendario as $dia): $clase_dia = ($dia['estado'] == 'libre') ? 'dia-libre' : 'dia-restriccion'; ?>
                    <div class="calendario-item <?= $clase_dia ?>">
                        <div class="cal-fecha"><span class="cal-dia-num"><?= $dia['d'] ?></span><span class="cal-mes"><?= $dia['m'] ?></span></div>
                        <div class="cal-info"><div class="cal-dia-semana"><?= $dia['dia'] ?></div><div class="cal-mensaje"><?= $dia['mensaje'] ?></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card-dashboard info-footer-card purple-gradient area-info">
            <h2>ℹ️ Información Legal</h2>
            <div class="info-grid">
                <div class="info-item"><strong>🚗 Exentos:</strong><br>Eléctricos, híbridos, gas.</div>
                <div class="info-item"><strong>🏠 Fin de Semana:</strong><br>Generalmente libre.</div>
                <div class="info-item"><strong>🎉 Festivos:</strong><br>Libre (Salvo Regionales).</div>
                <div class="info-item"><strong>⚠️ Multa:</strong><br>$<?= $MULTA_VALOR ?></div>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <span>&copy; <?= date('Y') ?> PicoYPlaca Bogotá</span> | <a href="https://picoyplacabogota.com.co/user/login.php">📢 Anuncie Aquí</a> | <a href="/contacto.php">Contacto</a>
        </div>
    </footer>

    <script>
        const NEXT_EVENT_TS = <?= $next_event_ts ?>; 
        const SERVER_TIME_MS = <?= time() * 1000 ?>;
        const CLIENT_OFFSET = new Date().getTime() - SERVER_TIME_MS;
        const DATA_CIUDADES = <?= json_encode($ciudades) ?>;
        const TIPO_ACTUAL = '<?= $tipo_busqueda ?>';
        const CIUDAD_ACTUAL = '<?= $ciudad_busqueda ?>';

        function updateClock() {
            if(NEXT_EVENT_TS === 0) return;
            const now = new Date().getTime() - CLIENT_OFFSET;
            const diff = NEXT_EVENT_TS - now;
            if (diff < 0) { setTimeout(() => location.reload(), 2000); return;
            }
            let h = Math.floor(diff / (1000 * 60 * 60));
            let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            let s = Math.floor((diff % (1000 * 60)) / 1000);
            const elH = document.getElementById('cd-h');
            if(elH) {
                elH.textContent = h < 10 ? '0'+h : h;
                document.getElementById('cd-m').textContent = m < 10 ? '0'+m : m;
                document.getElementById('cd-s').textContent = s < 10 ? '0'+s : s;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        function initFormulario() {
            const selC = document.getElementById('sel-ciudad');
            const selT = document.getElementById('sel-tipo');
            if(!selC || !selT) return;
            const upd = () => {
                const c = selC.value;
                const t = DATA_CIUDADES[c]?.tipos || {};
                selT.innerHTML = '';
                for(let k in t) {
                    let o = document.createElement('option');
                    o.value = k; o.textContent = t[k].nombre_display; selT.appendChild(o);
                }
                if(c === CIUDAD_ACTUAL && t[TIPO_ACTUAL]) selT.value = TIPO_ACTUAL;
            };
            selC.addEventListener('change', upd);
            upd();
        }

        // --- INICIALIZACIÓN COMPARTIDA Y PWA ---
        document.addEventListener('DOMContentLoaded', () => { 
            initFormulario(); 
            initShareAndPWA(); 
        });

        function toggleCalendario() {
            const g = document.getElementById('calendario-grid');
            g.style.display = (g.style.display==='none') ? 'grid' : 'none';
        }

        function initShareAndPWA() {
            // --- WIDGET COMPARTIR ---
            const pageTitle = encodeURIComponent(document.title);
            const pageUrl = encodeURIComponent(window.location.href);

            document.getElementById('share-wa').href = `https://api.whatsapp.com/send?text=${pageTitle} ${pageUrl}`;
            document.getElementById('share-fb').href = `https://www.facebook.com/sharer/sharer.php?u=${pageUrl}`;
            document.getElementById('share-x').href = `https://twitter.com/intent/tweet?text=${pageTitle}&url=${pageUrl}`;

            document.getElementById('toggleShare').addEventListener('click', function() {
                document.getElementById('shareOptions').classList.toggle('active');
                this.classList.toggle('active');
            });

            // --- PWA LOGICA ---
            let deferredPrompt;
            const btnInstall = document.getElementById('btn-pwa-install');
            const btnClosePwa = document.getElementById('closePwaBtn');
            const iosPrompt = document.getElementById('ios-prompt');
            const btnCloseIos = document.getElementById('btn-close-ios');

            btnClosePwa.addEventListener('click', (e) => {
                e.stopPropagation();
                btnInstall.style.display = 'none';
            });
            btnCloseIos.addEventListener('click', () => { iosPrompt.style.display = 'none'; });

            // Android
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                btnInstall.style.display = 'flex'; // Mostrar botón flotante
                
                btnInstall.addEventListener('click', (ev) => {
                    if(ev.target !== btnClosePwa) {
                        btnInstall.style.display = 'none';
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then((choiceResult) => {
                            deferredPrompt = null;
                        });
                    }
                });
            });

            // iOS
            const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
            const isInStandaloneMode = ('standalone' in window.navigator) && (window.navigator.standalone);

            if (isIos && !isInStandaloneMode) {
                btnInstall.style.display = 'flex';
                btnInstall.addEventListener('click', (ev) => {
                     if(ev.target !== btnClosePwa) {
                         iosPrompt.style.display = 'flex';
                     }
                });
            }
        }
        
        // --- REGISTRO DEL SERVICE WORKER (IMPORTANTE PARA INSTALAR APP) ---
        if ('serviceWorker' in navigator) {
          window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
              console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
              console.log('ServiceWorker registration failed: ', err);
            });
          });
        }
        
        // --- TRACKING DE INSTALACIÓN (GA4) ---
        window.addEventListener('appinstalled', (evt) => {
            console.log('App instalada con éxito');
            gtag('event', 'pwa_install', {
                'event_category': 'Engagement',
                'event_label': 'Install Success'
            });
        });
    </script>
<?php endif; ?>
</body>
</html>	