<?php
/**
 * index.php
 * Versión 19.0 - Footer Restaurado + SEO H1/H2/H3 + Fix Mañana
 */

// 1. Configuración inicial
date_default_timezone_set('America/Bogota');
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once 'config-ciudades.php';
require_once 'clases/PicoYPlaca.php';

// --- 1.1 AUTO-GENERADOR DE SITEMAP (45 Días) ---
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

$MESES = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
$MESES_CORTOS = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
$DIAS_SEMANA = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];

// Lógica de Enrutamiento
$es_busqueda = false;
$special_slug = $_GET['special_slug'] ?? null; 

$ciudad_busqueda = $_GET['ciudad_slug'] ?? $DEFAULT_CIUDAD_URL;
$tipo_busqueda = $_GET['tipo'] ?? $DEFAULT_TIPO_URL; 
$fecha_busqueda = $HOY;
$canonical_url = $BASE_URL;

// --- DEFINICIÓN DE FECHA Y URL ---
if ($special_slug === 'hoy') {
    $es_busqueda = true;
    $fecha_busqueda = $HOY;
    $ciudad_busqueda = 'bogota'; 
    $canonical_url = $BASE_URL . "/pico-y-placa-bogota-hoy";
    if(isset($_GET['tipo'])) $tipo_busqueda = $_GET['tipo'];

} elseif ($special_slug === 'manana') {
    $es_busqueda = true;
    // Forzar fecha de mañana
    $fecha_busqueda = date('Y-m-d', strtotime('+1 day')); 
    $ciudad_busqueda = 'bogota';
    $canonical_url = $BASE_URL . "/pico-y-placa-bogota-mañana";
    if(isset($_GET['tipo'])) $tipo_busqueda = $_GET['tipo'];

} elseif (isset($_GET['dia']) && isset($_GET['mes_nombre']) && isset($_GET['ano'])) {
    $es_busqueda = true;
    $mes_num = array_search($_GET['mes_nombre'], $MESES);
    if ($mes_num) {
        $fecha_busqueda = $_GET['ano'].'-'.$mes_num.'-'.str_pad($_GET['dia'], 2, '0', STR_PAD_LEFT);
        $slug_fecha = sprintf('%d-de-%s-de-%s', $_GET['dia'], $_GET['mes_nombre'], $_GET['ano']);
        $canonical_url = $BASE_URL . "/pico-y-placa/{$ciudad_busqueda}-{$slug_fecha}";
    }
} else {
     if ($fecha_busqueda === $HOY && $ciudad_busqueda === $DEFAULT_CIUDAD_URL && $tipo_busqueda === $DEFAULT_TIPO_URL) {
        $es_busqueda = false;
        $canonical_url = $BASE_URL . "/";
    }
}

if (!array_key_exists($ciudad_busqueda, $ciudades)) $ciudad_busqueda = $DEFAULT_CIUDAD_URL;
if (!isset($ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda])) {
    $tipo_busqueda = array_key_first($ciudades[$ciudad_busqueda]['tipos']);
}

$resultados = $picoYPlaca->obtenerRestriccion($ciudad_busqueda, $fecha_busqueda, $tipo_busqueda);
$nombre_festivo = $resultados['festivo'] ?? null;

// Textos SEO Dinámicos
$nombre_ciudad = $ciudades[$ciudad_busqueda]['nombre'];
$nombre_tipo = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['nombre_display'];
$dt = new DateTime($fecha_busqueda);

$dia_nombre = ucfirst($DIAS_SEMANA[$dt->format('N')]); 
$mes_nombre = ucfirst($MESES[$dt->format('m')]);       
$mes_corto  = ucfirst($MESES_CORTOS[$dt->format('m')]); 
$dia_num    = $dt->format('j'); // Sin cero inicial (1-31)                       
$anio       = $dt->format('Y');                        

$fecha_texto = "$dia_nombre, $dia_num de $mes_nombre de $anio";
$fecha_seo_corta = "$dia_num de $mes_nombre"; 

$keywords_list = [
    "pico y placa $nombre_ciudad $dia_num de $mes_nombre",
    "restricción vehicular $nombre_ciudad $dia_num de $mes_nombre",
    "horario pico y placa $nombre_ciudad",
    "pico y placa mañana $nombre_ciudad",
    "placas pico y placa $dia_num de $mes_nombre",
    "multas pico y placa $nombre_ciudad",
    "pico y placa taxis $nombre_ciudad $dia_num de $mes_nombre",
    "excepciones pico y placa $nombre_ciudad"
];
$meta_keywords = implode(", ", $keywords_list);

// Títulos optimizados
if ($special_slug === 'hoy') {
    $titulo_h1_largo = "Pico y Placa $nombre_ciudad HOY";
    $page_title = "Pico y Placa $nombre_ciudad HOY: $fecha_seo_corta | Restricción";
    $meta_description = "Consulta el Pico y Placa en $nombre_ciudad para HOY $fecha_texto.";
} elseif ($special_slug === 'manana') {
    $titulo_h1_largo = "Pico y Placa Mañana $fecha_seo_corta";
    $page_title = "Pico y Placa Mañana $fecha_seo_corta ($nombre_ciudad)";
    $meta_description = "Prepárate para mañana: Pico y Placa en $nombre_ciudad el $fecha_texto.";
} else {
    $titulo_h1_largo = "Pico y Placa $nombre_ciudad: $fecha_seo_corta";
    $page_title = "Pico y Placa $nombre_ciudad $fecha_seo_corta | $nombre_tipo";
    $meta_description = "Información oficial Pico y Placa $nombre_ciudad para el $fecha_texto.";
}

$body_class_mode = ($es_busqueda) ? 'search-mode' : 'home-mode';

// Lógica Visual
$es_restriccion_activa = false; 
$ya_paso_restriccion_hoy = false; 

if ($resultados['hay_pico']) {
    if ($fecha_busqueda === $HOY) {
        $now_ts = time();
        $rangos_check = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['rangos_horarios_php'] ?? [];
        foreach ($rangos_check as $r) {
            $i_ts = strtotime("$HOY " . $r['inicio']);
            $f_ts = strtotime("$HOY " . $r['fin']);
            if ($f_ts < $i_ts) $f_ts += 86400; 
            if ($now_ts >= $i_ts && $now_ts < $f_ts) { $es_restriccion_activa = true; break; }
        }
        if (!$es_restriccion_activa) {
            $ultimo_fin = 0;
            foreach ($rangos_check as $r) {
                $f_ts = strtotime("$HOY " . $r['fin']);
                if ($f_ts > $ultimo_fin) $ultimo_fin = $f_ts;
            }
            if ($now_ts > $ultimo_fin && $ultimo_fin > 0) $ya_paso_restriccion_hoy = true;
        }
    } else { 
        $es_restriccion_activa = true; 
    }
}

$reloj_titulo = "FALTA PARA INICIAR:";
$next_event_ts = 0; 
if ($fecha_busqueda === $HOY) {
    $now_ts = time(); 
    $rangos_hoy = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['rangos_horarios_php'] ?? [];
    if ($resultados['hay_pico'] && !empty($rangos_hoy)) {
        foreach ($rangos_hoy as $r) {
            $inicio_ts = strtotime("$HOY " . $r['inicio']);
            $fin_ts = strtotime("$HOY " . $r['fin']);
            if ($fin_ts < $inicio_ts) $fin_ts += 86400; 
            if ($now_ts >= $inicio_ts && $now_ts < $fin_ts) {
                $next_event_ts = $fin_ts * 1000;
                $reloj_titulo = "TERMINA EN:"; break;
            } elseif ($now_ts < $inicio_ts) {
                $next_event_ts = $inicio_ts * 1000;
                $reloj_titulo = "INICIA EN:"; break;
            }
        }
    }
    if ($next_event_ts == 0) {
        for ($i = 1; $i <= 15; $i++) { 
            $nd = date('Y-m-d', strtotime("$HOY +$i days"));
            $nr = $picoYPlaca->obtenerRestriccion($ciudad_busqueda, $nd, $tipo_busqueda);
            if ($nr['hay_pico']) {
                $rangos_next = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['rangos_horarios_php'] ?? [];
                if (!empty($rangos_next)) {
                    $inicio_ts = strtotime("$nd " . $rangos_next[0]['inicio']);
                    $next_event_ts = $inicio_ts * 1000;
                    $ndt = new DateTime($nd);
                    $d_nombre = $DIAS_SEMANA[$ndt->format('N')];
                    $d_num = $ndt->format('d');
                    $placas_prox = implode('-', $nr['restricciones']);
                    $reloj_titulo = "PRÓXIMA: " . mb_strtoupper("$d_nombre $d_num") . " ($placas_prox)";
                }
                break; 
            }
        }
    }
}

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

$calendario = [];
$fecha_iter = new DateTime($HOY);
for ($i = 0; $i < 30; $i++) {
    $f_str = $fecha_iter->format('Y-m-d');
    $res = $picoYPlaca->obtenerRestriccion($ciudad_busqueda, $f_str, $tipo_busqueda);
    $estado_dia = $res['hay_pico'] ? 'restriccion_general' : 'libre';
    $mensaje_dia = $res['hay_pico'] ? 'Restringe: ' . implode('-', $res['restricciones']) : 'Sin restricción';
    if ($res['festivo']) {
        $mensaje_dia .= "<br><span class='festivo-mini'>🎉 {$res['festivo']}</span>";
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
    <meta name="theme-color" content="#84fab0">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-icon-57x57.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/favicons/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="/styles.css?v=19.0">
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2L2EV10ZWW"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-2L2EV10ZWW');
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "GovernmentService",
      "name": "Pico y Placa <?= $nombre_ciudad ?>",
      "areaServed": { "@type": "City", "name": "<?= $nombre_ciudad ?>" },
      "datePublished": "<?= $fecha_busqueda ?>",
      "description": "Restricción para placas terminadas en <?= implode(', ', $resultados['restricciones']) ?>."
    }
    </script>
</head>
<body class="<?= $body_class_mode ?>" data-city-slug="<?= $ciudad_busqueda ?>">
    <script src="/ads/ads.js?v=3.0"></script>
    <div id="install-wrapper">
        <div id="android-prompt" class="install-toast" style="display:none">
            <div style="display:flex; align-items:center;">
                <span style="font-size:1.5em; margin-right:10px;">📲</span>
                <div>
                    <div style="font-weight:bold; font-size:0.9em;">Instalar App</div>
                    <div style="font-size:0.75em; opacity:0.8;">Acceso rápido</div>
                </div>
            </div>
            <div style="display:flex; align-items:center;">
                <button id="btn-install-action" class="btn-install-action">INSTALAR</button>
                <button id="btn-close-install" class="btn-close-install">✕</button>
            </div>
        </div>
        <div id="ios-prompt" class="install-toast" style="display:none; flex-direction:column; text-align:center;">
            <div style="margin-bottom:5px;">📲 <strong>Instalar en iPhone:</strong></div>
            <div style="font-size:0.85em;">Toca <strong>Compartir</strong> y elige <strong>"Agregar a Inicio"</strong>.</div>
            <button id="btn-close-ios" class="btn-close-install" style="margin-top:10px;">Cerrar ✕</button>
        </div>
    </div>

   <header class="app-header">
        <div class="header-content">
            <span class="car-icon">🚗</span>
            <h1 class="app-title"><?= $titulo_h1_largo ?></h1>
            <p class="app-subtitle">Info para <strong style="color:#000000; text-transform:uppercase;"><?= $nombre_tipo ?></strong></p>
        </div>
    </header>

    <div class="nav-tomorrow-wrapper" style="text-align:center; margin-bottom:15px; display:flex; justify-content:center; gap:10px;">
        <?php if($special_slug !== 'manana'): ?>
        <a href="/pico-y-placa-bogota-mañana" class="btn-tomorrow-float">🚀 Ver <strong>MAÑANA</strong></a>
        <?php endif; ?>
        <?php if($special_slug !== 'hoy'): ?>
        <a href="/pico-y-placa-bogota-hoy" class="btn-tomorrow-float" style="background: #2ecc71;">📅 Ver <strong>HOY</strong></a>
        <?php endif; ?>
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
                    <a href="/" class="btn-app-secondary" style="flex: 1; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center;">🏠 Inicio</a>
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
                <div class="seo-content">
                    <?= $ciudades[$ciudad_busqueda]['contenido_seo'] ?>
                </div>
            </details>
        </section>
        <?php endif; ?>

        <?php if($nombre_festivo): ?>
            <section class="festivo-alert-card area-festivo">
                <h2 style="margin:0; font-size:1.1em; color:inherit;">🎉 ¡ES FESTIVO!</h2>
                <p style="margin:5px 0 0;">Celebramos: <em><?= $nombre_festivo ?></em>. <?= !$resultados['hay_pico'] ? "✅ ¡Disfruta! No hay Pico y Placa." : "⚠️ Atención a restricciones." ?></p>
            </section>
        <?php endif; ?>

        <section class="quick-stats-grid area-stats">
            <div class="stat-card purple-gradient">
                <div class="stat-icon">📅 FECHA</div>
                <div class="stat-value small-text">
                    <?= ucfirst($DIAS_SEMANA[$dt->format('N')]) ?><br>
                    <?= $dt->format('j') ?> de <?= ucfirst($MESES[$dt->format('m')]) ?>
                </div>
            </div>
            <div class="stat-card purple-gradient">
                <div class="stat-icon">🚫 RESTRICCIÓN</div>
                <div class="stat-value big-text">
                    <?= $resultados['hay_pico'] ? implode(', ', $resultados['restricciones']) : "NO" ?>
                </div>
            </div>
            <div class="stat-card purple-gradient">
                <div class="stat-icon">🕒 HORARIO</div>
                <div class="stat-value small-text">
                    <?= $resultados['hay_pico'] ? $resultados['horario'] : 'Libre' ?>
                </div>
            </div>
        </section>

        <section class="card-dashboard status-card area-status" style="background-color: <?= $es_restriccion_activa ? '#fff5f5' : '#f0fff4' ?>; border-left: 5px solid <?= $es_restriccion_activa ? '#d63031' : '#00b894' ?>;">
            <h2 class="status-header" style="margin-bottom:10px;">
                <?= ($resultados['hay_pico'] && !$ya_paso_restriccion_hoy) ? "🚫 HAY PICO Y PLACA" : "✅ SIN RESTRICCIÓN" ?>
            </h2>
            <?php if ($next_event_ts > 0): ?>
            <div id="countdown-section">
                <div class="timer-label">⏳ <?= $reloj_titulo ?></div>
                <div class="timer-container">
                    <div class="time-box"><span id="cd-h">00</span><small>Hs</small></div>
                    <div class="time-sep">:</div>
                    <div class="time-box"><span id="cd-m">00</span><small>Min</small></div>
                    <div class="time-sep">:</div>
                    <div class="time-box"><span id="cd-s">00</span><small>Seg</small></div>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <section class="city-tags-section area-cities">
            <h2>Pico y Placa próximos días</h2>
            <div class="city-tags-grid">
                <?php 
                for($i=1; $i<=6; $i++):
                    $f_futura = new DateTime($fecha_busqueda);
                    $f_futura->modify("+$i day");
                    $d_f = $f_futura->format('j');
                    $m_f = $MESES[$f_futura->format('m')];
                    $a_f = $f_futura->format('Y');
                    $url_futura = "/pico-y-placa/$ciudad_busqueda-$d_f-de-$m_f-de-$a_f";
                ?>
                    <a href="<?= $url_futura ?>" class="city-tag">
                        <?= ucfirst($DIAS_SEMANA[$f_futura->format('N')]) ?> <?= $d_f ?>
                    </a>
                <?php endfor; ?>
                
                <h2 style="width:100%; margin-top:20px; font-size:1.3em;">Otras Ciudades</h2>
                 <?php foreach($ciudades as $k => $v): 
                    if($k === $ciudad_busqueda) continue;
                    $d_hoy = date('j'); $m_hoy = $MESES[date('m')]; $a_hoy = date('Y');
                    $url = "/pico-y-placa/$k-$d_hoy-de-$m_hoy-de-$a_hoy";
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
                <?php foreach($calendario as $dia): 
                    $clase_dia = ($dia['estado'] == 'libre') ? 'dia-libre' : 'dia-restriccion';
                ?>
                    <div class="calendario-item <?= $clase_dia ?>">
                        <div class="cal-fecha">
                            <span class="cal-dia-num"><?= $dia['d'] ?></span>
                            <span class="cal-mes"><?= $dia['m'] ?></span>
                        </div>
                        <div class="cal-info">
                            <div class="cal-dia-semana"><?= $dia['dia'] ?></div>
                            <div class="cal-mensaje"><?= $dia['mensaje'] ?></div>
                        </div>
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

    <?php
        $placas_texto = $resultados['hay_pico'] ? implode('-', $resultados['restricciones']) : "NO TIENE";
        $msj_base = "⚠️ Pico y Placa $nombre_ciudad ($fecha_seo_corta): $placas_texto. Info: $canonical_url";
        $link_wa = "https://api.whatsapp.com/send?text=" . urlencode($msj_base);
        $link_fb = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($canonical_url);
        $link_x  = "https://twitter.com/intent/tweet?text=" . urlencode($msj_base);
    ?>
    <div class="share-floating-bar">
        <span class="share-label">Compartir</span>
        <a href="<?= $link_wa ?>" target="_blank" class="btn-icon-share bg-whatsapp"><svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></a>
        <a href="<?= $link_fb ?>" target="_blank" class="btn-icon-share bg-facebook" title="Facebook"><svg viewBox="0 0 24 24"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036c-2.148 0-2.971.956-2.971 3.594v.376h3.428l-.581 3.667h-2.847v7.98c3.072-.53 5.622-2.567 6.853-5.415 1.23-2.848 1.002-6.093-.613-8.723a9.825 9.825 0 0 0-5.074-4.497c-2.992-.882-6.223-.258-8.64 1.67-2.417 1.928-3.696 4.927-3.42 8.022.276 3.095 2.08 5.84 4.823 7.341 1.362.745 2.87 1.119 4.377 1.097-.444.044-.891.077-1.344.077Z"/></svg></a>
        <a href="<?= $link_x ?>" target="_blank" class="btn-icon-share bg-x" title="X"><svg viewBox="0 0 24 24"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg></a>
    </div>

    <footer class="main-footer">
        <div class="footer-content">
            <span>&copy; <?= date('Y') ?> PicoYPlaca Bogotá</span>
            <span>|</span>
            <a href="https://picoyplacabogota.com.co/user/login.php">📢 Anuncie Aquí</a>
            <span>|</span>
            <a href="/contacto.php">Contacto</a>
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
            if (diff < 0) { setTimeout(() => location.reload(), 2000); return; }
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
        document.addEventListener('DOMContentLoaded', () => { initFormulario(); initPWA(); });
        let deferredPrompt;
        function initPWA() {
            const w = document.getElementById('install-wrapper');
            const ap = document.getElementById('android-prompt');
            const ip = document.getElementById('ios-prompt');
            const btn = document.getElementById('btn-install-action');
            const ca = document.getElementById('btn-close-install');
            const ci = document.getElementById('btn-close-ios');
            const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
            const isInStandaloneMode = ('standalone' in window.navigator) && (window.navigator.standalone);

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault(); deferredPrompt = e; w.style.display = 'flex'; ap.style.display = 'flex';
                btn.addEventListener('click', () => { ap.style.display = 'none'; deferredPrompt.prompt(); });
                ca.addEventListener('click', () => { w.style.display = 'none'; });
            });
            if (isIos && !isInStandaloneMode) {
                w.style.display = 'flex'; ip.style.display = 'flex';
                ci.addEventListener('click', () => { w.style.display = 'none'; });
            }
        }
        function toggleCalendario() {
            const g = document.getElementById('calendario-grid');
            g.style.display = (g.style.display==='none') ? 'grid' : 'none';
        }
    </script>
</body>
</html>
