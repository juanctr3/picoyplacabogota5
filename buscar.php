<?php
/**
 * buscar.php
 * Procesador del formulario de búsqueda.
 * Corrección V17: Genera URLs compatibles con la nueva estructura SEO.
 * Estructura: /pico-y-placa/ciudad-dia-de-mes-de-ano?tipo=vehiculo
 */

// 1. Configuración y utilidades
// ----------------------------------------------------------------------
require_once 'config-ciudades.php';

// Array de meses para la conversión de fecha (formato URL)
$MESES = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];

/**
 * Función de redirección simple
 * @param string $url La URL a la que redirigir.
 */
function redirigir($url = '/') {
    header('Location: ' . $url);
    exit;
}

// 2. Procesamiento de la solicitud POST
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('/');
}

// Recibir y sanear datos del formulario
$fecha_raw = $_POST['fecha'] ?? ''; // Formato esperado: YYYY-MM-DD
$ciudad = strtolower(trim($_POST['ciudad'] ?? ''));
$tipo = strtolower(trim($_POST['tipo'] ?? ''));

// 3. Validación de datos
// ----------------------------------------------------------------------

// FIX: Antes de validar, eliminamos la clave técnica si está presente en el array global
if(isset($ciudades['rotaciones_base'])) {
    unset($ciudades['rotaciones_base']);
}

// Validar que los datos existan y que la ciudad/tipo sean válidos
if (empty($fecha_raw) || empty($ciudad) || empty($tipo) || !isset($ciudades[$ciudad]) || !isset($ciudades[$ciudad]['tipos'][$tipo])) {
    // Si hay un error en los datos, redirigir al Home
    redirigir('/?error=datos_invalidos');
}

// 4. Conversión de Fecha a Formato URL Amigable
// ----------------------------------------------------------------------

// Separar YYYY-MM-DD
if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha_raw, $coincidencias)) {
    redirigir('/?error=fecha_invalida');
}

$ano = $coincidencias[1];
$mes_num = $coincidencias[2];
$dia = (int)$coincidencias[3]; 

// Convertir número de mes a nombre
$mes_nombre = $MESES[$mes_num] ?? null;

if (!$mes_nombre) {
    redirigir('/?error=mes_invalido');
}

// 5. Construcción de la URL y Redirección (CORREGIDO)
// ----------------------------------------------------------------------

// Generamos la URL base limpia (Sin el tipo al final de la ruta)
// Ejemplo: /pico-y-placa/bogota-4-de-diciembre-de-2025
$url_amigable = sprintf(
    '/pico-y-placa/%s-%d-de-%s-de-%s',
    $ciudad,
    $dia,
    $mes_nombre,
    $ano
);

// Si el tipo NO es particulares (el por defecto), lo agregamos como parámetro
// Esto evita la redirección 301 del .htaccess y carga la info correcta.
if ($tipo !== 'particulares') {
    $url_amigable .= '?tipo=' . $tipo;
}

redirigir($url_amigable);
?>