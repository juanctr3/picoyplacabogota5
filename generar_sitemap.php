<?php
/**
 * generar_sitemap.php
 * Genera sitemap con fechas específicas Y URLs Evergreen ("mañana").
 * Ejecutar una vez al día.
 */

date_default_timezone_set('America/Bogota');
require_once 'config-ciudades.php';

// Limpieza
if(isset($ciudades['rotaciones_base'])) { unset($ciudades['rotaciones_base']); }

$BASE_URL = 'https://picoyplacabogota.com.co'; 
$DIAS_A_GENERAR = 30;
$SITEMAP_FILE = __DIR__ . '/sitemap.xml';

$MESES = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];

$urls = [];
$now = new DateTime();

// 1. ESTRATEGIA MAÑANA (PRIORIDAD ALTA)
foreach ($ciudades as $ciudad_slug => $ciudad_data) {
    foreach ($ciudad_data['tipos'] as $tipo_slug => $tipo_data) {
        $urls[] = [
            'loc' => $BASE_URL . "/pico-y-placa/$ciudad_slug/manana/$tipo_slug",
            'lastmod' => $now->format('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '1.0' // Prioridad máxima para atacar la keyword
        ];
    }
}

// 2. FECHAS ESPECÍFICAS (Rolling 30 días)
for ($i = 0; $i < $DIAS_A_GENERAR; $i++) {
    $currentDate = clone $now;
    $currentDate->modify("+$i day");
    
    $dia = (int)$currentDate->format('d');
    $mes_num = $currentDate->format('m');
    $ano = $currentDate->format('Y');
    $mes_nombre = $MESES[$mes_num];
    $dateSlug = sprintf('%d-de-%s-de-%s', $dia, $mes_nombre, $ano);

    foreach ($ciudades as $ciudad_slug => $ciudad_data) {
        foreach ($ciudad_data['tipos'] as $tipo_slug => $tipo_data) {
            $loc = sprintf('/pico-y-placa/%s-%s/%s', $ciudad_slug, $dateSlug, $tipo_slug);
            // Prioridad descendente
            $priority = max(0.5, 0.9 - ($i / ($DIAS_A_GENERAR * 2)));

            $urls[] = [
                'loc' => $BASE_URL . $loc,
                'lastmod' => $now->format('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => number_format($priority, 2, '.', '')
            ];
        }
    }
}

// 3. ESCRIBIR XML
$xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Home
$xmlContent .= "  <url><loc>{$BASE_URL}/</loc><lastmod>" . $now->format('Y-m-d') . "</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";

foreach ($urls as $url) {
    $xmlContent .= "  <url>\n";
    $xmlContent .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
    $xmlContent .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
    $xmlContent .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
    $xmlContent .= "    <priority>" . $url['priority'] . "</priority>\n";
    $xmlContent .= "  </url>\n";
}
$xmlContent .= '</urlset>';

file_put_contents($SITEMAP_FILE, $xmlContent);
echo "Sitemap generado con éxito. Total URLs: " . count($urls);
?>
