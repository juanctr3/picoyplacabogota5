<?php
/**
 * index.php
 * Versión 14.0 - All-in-One:
 * - Auto-generación de Sitemap (Cron Silencioso).
 * - Soporte URL "Mañana" (Evergreen).
 * - Schema JSON-LD.
 * - Botón App Flotante (PWA).
 * - Botones Compartir SVG.
 */

// 1. Configuración inicial
date_default_timezone_set('America/Bogota');
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once 'config-ciudades.php';
require_once 'clases/PicoYPlaca.php';

// --- 1.1 AUTO-GENERADOR DE SITEMAP (Lógica Diaria) ---
// Se ejecuta solo si el sitemap no existe o es de una fecha anterior a hoy.
$sitemapFile = __DIR__ . '/sitemap.xml';
if (!file_exists($sitemapFile) || date('Y-m-d', filemtime($sitemapFile)) !== date('Y-m-d')) {
    // Datos necesarios
    $BASE_URL_SM = 'https://picoyplacabogota.com.co'; 
    $DIAS_SM = 30;
    $MESES_SM = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Home
    $hoy_sm = date('Y-m-d');
    $xml .= "  <url><loc>{$BASE_URL_SM}/</loc><lastmod>{$hoy_sm}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
    
    // 1. URLs Evergreen ("Mañana") - Prioridad Alta
    // Usamos una copia de ciudades para no afectar la variable global si se modifica
    $ciudades_sm = $ciudades; 
    if(isset($ciudades_sm['rotaciones_base'])) unset($ciudades_sm['rotaciones_base']);

    foreach ($ciudades_sm as $c_slug => $c_data) {
        foreach ($c_data['tipos'] as $t_slug => $t_data) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$BASE_URL_SM}/pico-y-placa/{$c_slug}/manana/{$t_slug}</loc>\n";
            $xml .= "    <lastmod>{$hoy_sm}</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>1.0</priority>\n";
            $xml .= "  </url>\n";
        }
    }

    // 2. URLs Fechas Específicas (Próximos 30 días)
    $now_sm = new DateTime();
    for ($i = 0; $i < $DIAS_SM; $i++) {
        $curDate = clone $now_sm;
        $curDate->modify("+$i day");
        $d_sm = (int)$curDate->format('d');
        $m_num_sm = $curDate->format('m');
        $y_sm = $curDate->format('Y');
        $slug_fecha = sprintf('%d-de-%s-de-%s', $d_sm, $MESES_SM[$m_num_sm], $y_sm);

        foreach ($ciudades_sm as $c_slug => $c_data) {
            foreach ($c_data['tipos'] as $t_slug => $t_data) {
                $prio = number_format(max(0.5, 0.9 - ($i / ($DIAS_SM * 2))), 2, '.', '');
                $loc = "{$BASE_URL_SM}/pico-y-placa/{$c_slug}-{$slug_fecha}/{$t_slug}";
                
                $xml .= "  <url>\n";
                $xml .= "    <loc>{$loc}</loc>\n";
                $xml .= "    <lastmod>{$hoy_sm}</lastmod>\n";
                $xml .= "    <changefreq>daily</changefreq>\n";
                $xml .= "    <priority>{$prio}</priority>\n";
                $xml .= "  </url>\n";
            }
        }
    }
    $xml .= '</urlset>';
    file_put_contents($sitemapFile, $xml); // Guardar silenciosamente
}

// --- FIN AUTO-GENERADOR ---

$picoYPlaca = new PicoYPlaca();
if(isset($ciudades['rotaciones_base'])) unset($ciudades['rotaciones_base']);

// Datos Globales
$HOY = date('Y-m-d'); 
$DEFAULT_CIUDAD_URL = 'bogota';
$DEFAULT_TIPO_URL = 'particulares';
$MULTA_VALOR = '1.400.000';
$BASE_URL = 'https://picoyplacabogota.com.co';

$MESES = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
$MESES_CORTOS = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
$DIAS_SEMANA = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];

// 2. Lógica de Búsqueda y Enrutamiento SEO
$es_busqueda = false;
$es_manana = isset($_GET['es_manana']) && $_GET['es_manana'] == 1;

// Valores por defecto
$ciudad_busqueda = $_GET['ciudad_slug'] ?? $DEFAULT_CIUDAD_URL;
$tipo_busqueda = $_GET['tipo'] ?? $DEFAULT_TIPO_URL;
$fecha_busqueda = $HOY;

$request_uri_clean = explode('?', $_SERVER['REQUEST_URI'])[0];
$canonical_url = trim($BASE_URL . $request_uri_clean, '/');

// Caso A: URL "Mañana" (Prioridad SEO - Evergreen)
if ($es_manana) {
    $es_busqueda = true;
    $fecha_busqueda = date('Y-m-d', strtotime('+1 day'));
    
    if (!array_key_exists($ciudad_busqueda, $ciudades)) $ciudad_busqueda = $DEFAULT_CIUDAD_URL;
    if (!isset($ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda])) $tipo_busqueda = 'particulares';
    
    $canonical_url = $BASE_URL . "/pico-y-placa/$ciudad_busqueda/manana/$tipo_busqueda";

} 
// Caso B: URL Fecha Específica (Regex Legacy)
elseif (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/pico-y-placa/') === 0) {
    $slug = explode('/', trim($request_uri_clean, '/'))[1] ?? '';
    if (preg_match('/^([a-z-]+)-(\d{1,2})-de-([a-z]+)-de-(\d{4})$/', $slug, $m)) {
        $mes_num = array_search($m[3], $MESES);
        if ($mes_num) {
            $es_busqueda = true;
            $ciudad_busqueda = array_key_exists($m[1], $ciudades) ? $m[1] : $DEFAULT_CIUDAD_URL;
            $fecha_busqueda = $m[4].'-'.$mes_num.'-'.str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $tipo_busqueda = $_GET['tipo'] ?? $DEFAULT_TIPO_URL;
            if (!isset($ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda])) {
                $tipo_busqueda = key($ciudades[$ciudad_busqueda]['tipos']);
            }
        }
    }
}

if (!$es_busqueda && !$es_manana) {
    if ($fecha_busqueda === $HOY && $ciudad_busqueda === $DEFAULT_CIUDAD_URL && $tipo_busqueda === $DEFAULT_TIPO_URL) {
        $es_busqueda = false;
    }
}

// Consulta Principal
$resultados = $picoYPlaca->obtenerRestriccion($ciudad_busqueda, $fecha_busqueda, $tipo_busqueda);
$nombre_festivo = $resultados['festivo'] ?? null;

// --- LÓGICA SEO DE TEXTOS ---
$nombre_ciudad = $ciudades[$ciudad_busqueda]['nombre'];
$nombre_tipo = $ciudades[$ciudad_busqueda]['tipos'][$tipo_busqueda]['nombre_display'];
$dt = new DateTime($fecha_busqueda);

$dia_nombre = ucfirst($DIAS_SEMANA[$dt->format('N')]); 
$mes_nombre = ucfirst($MESES[$dt->format('m')]);       
$mes_corto  = ucfirst($MESES_CORTOS[$dt->format('m')]); 
$dia_num    = $dt->format('d');                        
$anio       = $dt->format('Y');                        

$fecha_texto = "$dia_nombre, $dia_num de $mes_nombre de $anio";
$fecha_seo_corta = "$dia_nombre $dia_num $mes_corto $anio"; 

$keywords_array = [
    "pico y placa", "pico y placa $nombre_ciudad", 
    "pico y placa mañana $nombre_ciudad", 
    "restricción vehicular $nombre_ciudad",
    "pico y placa $nombre_ciudad $nombre_tipo"
];
$meta_keywords = implode(", ", $keywords_array);

if ($es_manana) {
    $titulo_h1_largo = "Pico y Placa MAÑANA en $nombre_ciudad";
    $page_title = "Pico y Placa MAÑANA en $nombre_ciudad ($fecha_seo_corta) | $nombre_tipo";
    $meta_description = "⚠️ Atención: Pico y Placa MAÑANA $fecha_seo_corta en $nombre_ciudad. Placas restringidas: " . implode('-', $resultados['restricciones']) . ". Horario: " . $resultados['horario'] . ". Evita multas.";
} elseif ($fecha_busqueda === $HOY) {
    $titulo_h1_largo = "Pico y Placa HOY en $nombre_ciudad";
    $page_title = "Pico y Placa HOY en $nombre_ciudad $fecha_seo_corta | $nombre_tipo";
    $estado_texto = $resultados['hay_pico'] ? "TIENE restricción" : "NO TIENE restricción";
    $meta_description = "Consulta el Pico y Placa en $nombre_ciudad para $nombre_tipo hoy $fecha_texto. Estado: $estado_texto. Horarios y mapa actualizado.";
} else {
    $titulo_h1_largo = "Pico y Placa en $nombre_ciudad, $fecha_seo_corta";
    $page_title = "Pico y Placa en $nombre_ciudad el $fecha_seo_corta | $nombre_tipo";
    $meta_description = "Prográmate: Pico y Placa para el $fecha_texto en $nombre_ciudad. Vehículos $nombre_tipo. Verifica si tienes restricción.";
}

$body_class_mode = ($es_busqueda || $es_manana) ? 'search-mode' : 'home-mode';

// Estado en Tiempo Real
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
            if ($now_ts >= $i_ts && $now_ts < $f_ts) {
                $es_restriccion_activa = true; 
                break;
            }
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

// Reloj
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
                $reloj_titulo = "TERMINA EN:";
                break;
            } elseif ($now_ts < $inicio_ts) {
                $next_event_ts = $inicio_ts * 1000;
                $reloj_titulo = "INICIA EN:";
                break;
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

// Calculadora
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
                'fecha_larga' => ucfirst($DIAS_SEMANA[$fecha_p->format('N')]) . ' ' . $fecha_p->format('d') . ' de ' . $MESES[$fecha_p->format('m')],
                'horario' => $res_p['horario']
            ];
        }
        $fecha_p->modify('+1 day');
    }
}

// Calendario General
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
        'd' => $fecha_iter->format('d'),
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
    
    <link rel="stylesheet" href="/styles.css?v=12.0">
    
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
      "name": "Pico y Placa <?= $nombre_ciudad ?> - <?= $nombre_tipo ?>",
      "serviceType": "Restricción Vehicular",
      "serviceOperator": {
        "@type": "GovernmentOrganization",
        "name": "Alcaldía de <?= $nombre_ciudad ?>"
      },
      "areaServed": {
        "@type": "City",
        "name": "<?= $nombre_ciudad ?>"
      },
      "hoursAvailable": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": "https://schema.org/<?= date('l', strtotime($fecha_busqueda)) ?>",
        "opens": "<?= explode('-', $resultados['horario'])[0] ?? '00:00' ?>",
        "closes": "<?= explode('-', $resultados['horario'])[1] ?? '23:59' ?>"
      },
      "description": "Restricción de movilidad para vehículos tipo <?= $nombre_tipo ?> con placas terminadas en <?= implode(', ', $resultados['restricciones']) ?> el día <?= $fecha_seo_corta ?>.",
      "isSimilarTo": [
        "https://www.movilidadbogota.gov.co/", 
        "https://www.medellin.gov.co/"
      ]
    }
    </script>
</head>
<body class="<?= $body_class_mode ?>" data-city-slug="<?= $ciudad_busqueda ?>">

    <script src="/ads/client.js?v=2.0"></script>

    <div id="install-wrapper">
        <div id="android-prompt" class="install-toast" style="display:none">
            <div style="display:flex; align-items:center;">
                <span style="font-size:1.5em; margin-right:10px;">📲</span>
                <div style="text-align:left;">
                    <div style="font-weight:bold; font-size:0.9em;">Instalar App</div>
                    <div style="font-size:0.75em; opacity:0.8;">Acceso rápido sin internet</div>
                </div>
            </div>
            <div style="display:flex; align-items:center;">
                <button id="btn-install-action" class="btn-install-action">INSTALAR</button>
                <button id="btn-close-install" class="btn-close-install">✕</button>
            </div>
        </div>

        <div id="ios-prompt" class="install-toast" style="display:none; flex-direction:column; text-align:center;">
            <div style="margin-bottom:5px;">📲 <strong>Instalar en iPhone:</strong></div>
            <div style="font-size:0.85em;">Toca el botón <strong>Compartir</strong> y elige <strong>"Agregar a Inicio"</strong>.</div>
            <button id="btn-close-ios" class="btn-close-install" style="margin-top:10px;">Cerrar ✕</button>
        </div>
    </div>

   <header class="app-header">
        <div class="header-content">
            <span class="car-icon">🚗</span>
            <h1 class="app-title"><?= $titulo_h1_largo ?></h1>
            <p class="app-subtitle">Estas viendo el pico y placa para <strong style="color:#000000; text-transform:uppercase;"><?= $nombre_tipo ?></strong> del día <?= $fecha_seo_corta ?> </p>
        </div>
    </header>

    <?php if(!$es_manana): ?>
    <div class="nav-tomorrow-wrapper">
        <a href="/pico-y-placa/<?= $ciudad_busqueda ?>/manana/<?= $tipo_busqueda ?>" class="btn-tomorrow-float">
            📅 Ver Pico y Placa <strong>MAÑANA</strong>
        </a>
    </div>
    <?php endif; ?>

    <main class="app-container">
        
        <section class="card-dashboard search-card area-search">
            <div class="card-header-icon">📅 Buscar por Fecha</div>
            <form action="/buscar.php" method="POST" class="search-form-grid">
                <div class="input-wrapper full-width">
                    <input type="date" name="fecha" value="<?= $fecha_busqueda ?>" required min="2020-01-01" max="2030-12-31" class="app-input">
                </div>
                <div class="input-wrapper">
                    <select name="ciudad" id="sel-ciudad" class="app-select">
                        <?php 
                        $ciudades_ord = $ciudades;
                        uasort($ciudades_ord, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
                        foreach($ciudades_ord as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $k===$ciudad_busqueda?'selected':'' ?>><?= $v['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-wrapper">
                    <select name="tipo" id="sel-tipo" class="app-select"></select>
                </div>
                
                <div class="actions-wrapper full-width" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-app-primary" style="flex: 2;">Buscar</button>
                    <?php if($es_busqueda || $es_manana): ?>
                        <a href="/" class="btn-app-secondary" style="flex: 1; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center;">🏠 Inicio</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        <?php if (!empty($ciudades[$ciudad_busqueda]['contenido_seo'])): ?>
        <section class="seo-accordion-wrapper area-seo">
            <details class="seo-details">
                <summary class="seo-summary">
                    ℹ️ <strong>Normativa, Multas y Excepciones en <?= $nombre_ciudad ?></strong>
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
                🎉 <strong>¡ES FESTIVO!</strong><br>
                Celebramos: <em><?= $nombre_festivo ?></em>.<br>
                <?php if(!$resultados['hay_pico']): ?>
                    ✅ ¡Disfruta! No hay Pico y Placa.
                <?php else: ?>
                    ⚠️ Atención: Aunque es festivo, verifica restricciones.
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="quick-stats-grid area-stats">
            <div class="stat-card purple-gradient">
                <div class="stat-icon">📅 FECHA</div>
                <div class="stat-value small-text">
                    <?= ucfirst($DIAS_SEMANA[$dt->format('N')]) ?><br>
                    <?= $dt->format('d') ?> de <?= ucfirst($MESES[$dt->format('m')]) ?>
                </div>
            </div>
            
            <div class="stat-card purple-gradient">
                <div class="stat-icon">🚫 RESTRICCIÓN</div>
                <div class="stat-value big-text">
                    <?php 
                    if ($resultados['hay_pico']) {
                        echo implode(', ', $resultados['restricciones']);
                    } else {
                        echo "NO";
                    }
                    ?>
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
            <div class="status-header">
                <?php if ($resultados['hay_pico'] && !$ya_paso_restriccion_hoy): ?>
                    <span class="status-check restricted">🚫</span> <span class="status-text restricted">HAY PICO Y PLACA</span>
                <?php elseif ($ya_paso_restriccion_hoy): ?>
                    <span class="status-check free">🏁</span> <span class="status-text free">YA TERMINÓ POR HOY</span>
                <?php else: ?>
                    <span class="status-check free">✅</span> <span class="status-text free">SIN RESTRICCIÓN</span>
                <?php endif; ?>
            </div>

            <?php if ($next_event_ts > 0): ?>
            <div id="countdown-section">
                <div class="timer-label" id="lbl-reloj">⏳ <?= $reloj_titulo ?></div>
                <div class="timer-container">
                    <div class="time-box"><span id="cd-h">00</span><small>HORAS</small></div>
                    <div class="time-sep">:</div>
                    <div class="time-box"><span id="cd-m">00</span><small>MINUTOS</small></div>
                    <div class="time-sep">:</div>
                    <div class="time-box"><span id="cd-s">00</span><small>SEG</small></div>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <section class="city-tags-section area-cities">
            <h3>Tu ciudad</h3>
            <div class="city-tags-grid">
                <?php foreach($ciudades_ord as $k => $v): 
                    $active = ($k === $ciudad_busqueda) ? 'active' : '';
                    $d_hoy = date('j'); $m_hoy = $MESES[date('m')]; $a_hoy = date('Y');
                    $url = "/pico-y-placa/$k-$d_hoy-de-$m_hoy-de-$a_hoy/$DEFAULT_TIPO_URL";
                ?>
                    <a href="<?= $url ?>" class="city-tag <?= $active ?>"><?= $v['nombre'] ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card-dashboard calc-card area-calc">
            <h3>Proyección Mes Pico y Placa</h3>
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
                    <h4>📅 Días con restricción (Placa <?= $placa_proyeccion ?>):</h4>
                    <?php if (empty($calendario_personalizado)): ?>
                        <p class="free-text">✅ ¡Todo libre! No tienes pico y placa en los próximos 30 días.</p>
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
            <h3>Detalle Placas</h3>
            <div class="city-subtitle"><?= $nombre_ciudad ?> (<?= $nombre_tipo ?>)</div>
            
            <?php if ($resultados['hay_pico']): ?>
                <div class="plate-group">
                    <div class="plate-label">🚫 Con restricción:</div>
                    <div class="circles-container">
                        <?php foreach($resultados['restricciones'] as $p) echo "<div class='plate-circle pink'>$p</div>"; ?>
                    </div>
                </div>
                <div class="plate-group">
                    <div class="plate-label">✅ Habilitadas:</div>
                    <div class="circles-container">
                        <?php foreach($resultados['permitidas'] as $p) echo "<div class='plate-circle green'>$p</div>"; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="free-alert">✅ ¡Todo el parque automotor habilitado!</div>
            <?php endif; ?>
        </section>

        <section class="card-dashboard calendar-card area-calendar">
            <h3>🗓️ Calendario General (30 Días)</h3>
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
            <h3>ℹ️ Información Legal</h3>
            <div class="info-grid">
                <div class="info-item"><strong>🚗 Exentos:</strong><br>Eléctricos, híbridos, gas.</div>
                <div class="info-item"><strong>🏠 Fin de Semana:</strong><br>Generalmente libre.</div>
                <div class="info-item"><strong>🎉 Festivos:</strong><br>Libre (Salvo Regionales).</div>
                <div class="info-item"><strong>⚠️ Multa:</strong><br>$<?= $MULTA_VALOR ?></div>
            </div>
        </section>

    </main>

    <?php
        // Construir mensaje dinámico para WhatsApp
        $placas_texto = $resultados['hay_pico'] ? implode('-', $resultados['restricciones']) : "NO TIENE";
        $msj_wa = "⚠️ *Pico y Placa $nombre_ciudad* \n📅 $fecha_seo_corta \n🚫 Restricción: *$placas_texto* \nℹ️ Info completa aquí: $canonical_url";
        $link_wa = "https://api.whatsapp.com/send?text=" . urlencode($msj_wa);
        $link_fb = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($canonical_url);
        $link_x  = "https://twitter.com/intent/tweet?text=" . urlencode("Pico y Placa en $nombre_ciudad: $placas_texto. Info: $canonical_url");
    ?>
    
    <?php
        // Mensaje dinámico
        $placas_texto = $resultados['hay_pico'] ? implode('-', $resultados['restricciones']) : "NO TIENE";
        $msj_base = "⚠️ Pico y Placa $nombre_ciudad ($fecha_seo_corta): $placas_texto. Info: $canonical_url";
        
        $link_wa = "https://api.whatsapp.com/send?text=" . urlencode($msj_base);
        $link_fb = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($canonical_url);
        $link_x  = "https://twitter.com/intent/tweet?text=" . urlencode($msj_base);
    ?>
    
    <div class="share-floating-bar">
        <span class="share-label">Compartir</span>
        
        <a href="<?= $link_wa ?>" target="_blank" class="btn-icon-share bg-whatsapp" title="Enviar por WhatsApp">
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
        </a>

        <a href="<?= $link_fb ?>" target="_blank" class="btn-icon-share bg-facebook" title="Compartir en Facebook">
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036c-2.148 0-2.971.956-2.971 3.594v.376h3.428l-.581 3.667h-2.847v7.98c3.072-.53 5.622-2.567 6.853-5.415 1.23-2.848 1.002-6.093-.613-8.723a9.825 9.825 0 0 0-5.074-4.497c-2.992-.882-6.223-.258-8.64 1.67-2.417 1.928-3.696 4.927-3.42 8.022.276 3.095 2.08 5.84 4.823 7.341 1.362.745 2.87 1.119 4.377 1.097-.444.044-.891.077-1.344.077Z"/></svg>
        </a>

        <a href="<?= $link_x ?>" target="_blank" class="btn-icon-share bg-x" title="Postear en X">
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>
        </a>
    </div>

    <footer class="app-footer">
        <p>Pico y PL - Colombia 2025 | Versión 13.0</p>
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
        
        document.addEventListener('DOMContentLoaded', () => {
            initFormulario();
            initPWA();
        });
        
        let deferredPrompt;

    function initPWA() {
        const wrapper = document.getElementById('install-wrapper');
        const androidPrompt = document.getElementById('android-prompt');
        const iosPrompt = document.getElementById('ios-prompt');
        const installBtn = document.getElementById('btn-install-action');
        const closeAndroid = document.getElementById('btn-close-install');
        const closeIos = document.getElementById('btn-close-ios');

        const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        const isInStandaloneMode = ('standalone' in window.navigator) && (window.navigator.standalone);

        // 1. Lógica Android / Chrome
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            wrapper.style.display = 'flex'; // Mostrar contenedor padre
            androidPrompt.style.display = 'flex'; // Mostrar toast Android
            
            installBtn.addEventListener('click', () => {
                androidPrompt.style.display = 'none';
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    deferredPrompt = null;
                });
            });
            
            closeAndroid.addEventListener('click', () => {
                wrapper.style.display = 'none';
            });
        });

        // 2. Lógica iOS
        if (isIos && !isInStandaloneMode) {
            wrapper.style.display = 'flex';
            iosPrompt.style.display = 'flex';
            
            closeIos.addEventListener('click', () => {
                wrapper.style.display = 'none';
            });
        }
    }
        function toggleCalendario() {
            const grid = document.getElementById('calendario-grid');
            if(grid.style.display==='none') grid.style.display='grid'; else grid.style.display='none';
        }
    </script>
</body>
</html>