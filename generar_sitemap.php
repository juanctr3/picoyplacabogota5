<?php
/**
 * generar_sitemap.php
 * Generador de Sitemap XML para SEO Programático.
 * - Genera URLs para los próximos 45 días.
 * - Usa la estructura simplificada: /pico-y-placa/ciudad-fecha
 * - Incluye URLs Evergreen: /hoy y /mañana
 */

date_default_timezone_set('America/Bogota');
require_once 'config-ciudades.php';

// Evitar conflictos con variables globales
if(isset($ciudades['rotaciones_base'])) { unset($ciudades['rotaciones_base']); }

$BASE_URL = 'https://picoyplacabogota.com.co'; 
$DIAS_A_GENERAR = 45; // Solicitud del usuario: 45 días
$SITEMAP_FILE = __DIR__ . '/sitemap.xml';

$MESES = ['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];

$urls = [];
$now = new DateTime();

// 1. URLs ESTRATÉGICAS (Evergreen)
// Estas son las más importantes para el posicionamiento diario
$urls[] = ['loc' => $BASE_URL . '/pico-y-placa-bogota-hoy', 'prio' => '1.0'];
$urls[] = ['loc' => $BASE_URL . '/pico-y-placa-bogota-mañana', 'prio' => '1.0'];

// 2. URLs FECHAS ESPECÍFICAS (Rolling 45 días)
for ($i = 0; $i < $DIAS_A_GENERAR; $i++) {
    $currentDate = clone $now;
    $currentDate->modify("+$i day");
    
    $dia = (int)$currentDate->format('d');
    $mes_num = $currentDate->format('m');
    $ano = $currentDate->format('Y');
    $mes_nombre = $MESES[$mes_num];
    
    // Formato Slug: 2-de-diciembre-de-2025
    $dateSlug = sprintf('%d-de-%s-de-%s', $dia, $mes_nombre, $ano);

    foreach ($ciudades as $ciudad_slug => $ciudad_data) {
        // ESTRUCTURA NUEVA: Sin el tipo de vehículo al final
        // Ejemplo: https://picoyplacabogota.com.co/pico-y-placa/bogota-2-de-diciembre-de-2025
        $loc = sprintf('/pico-y-placa/%s-%s', $ciudad_slug, $dateSlug);
        
        // Prioridad descendente (más cerca a hoy = más prioridad)
        $priority = max(0.5, 0.9 - ($i / $DIAS_A_GENERAR));

        $urls[] = [
            'loc' => $BASE_URL . $loc,
            'prio' => number_format($priority, 2, '.', '')
        ];
    }
}

// 3. GENERAR XML
$xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Home
$xmlContent .= "  <url><loc>{$BASE_URL}/</loc><lastmod>" . $now->format('Y-m-d') . "</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";

foreach ($urls as $url) {
    $xmlContent .= "  <url>\n";
    $xmlContent .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
    $xmlContent .= "    <lastmod>" . $now->format('Y-m-d') . "</lastmod>\n";
    $xmlContent .= "    <changefreq>daily</changefreq>\n";
    $xmlContent .= "    <priority>" . $url['prio'] . "</priority>\n";
    $xmlContent .= "  </url>\n";
}
$xmlContent .= '</urlset>';

// Guardar
file_put_contents($SITEMAP_FILE, $xmlContent);
// Opcional: imprimir mensaje si se ejecuta manualmente
// echo "Sitemap generado con éxito. Total URLs: " . count($urls);
?>