<?php
/**
 * ads/server.php - Servidor de Anuncios (Rotación Ponderada)
 * Muestra todos los anuncios activos, priorizando los de mayor oferta (CPC/CPM).
 */

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

$dbHost = 'localhost';
$dbName = 'picoyplacabogota';   
$dbUser = 'picoyplacabogota';   
$dbPass = 'Q20BsIFHI9j8h2XoYNQm3RmQg';   

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $city_slug = $_GET['ciudad'] ?? '';

    // 1. OBTENER TODOS LOS CANDIDATOS ACTIVOS (Sin LIMIT 1)
    // Calculamos el "peso" (score) basado en la oferta.
    $stmt = $pdo->prepare("
        SELECT 
            b.*, u.account_balance,
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS impresiones_actuales,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS clicks_actuales,
            -- Algoritmo de Peso: (CPC * 1000) + (CPM). Ejemplo: CPC $200 COP pesa más que CPM $5000 COP.
            ((b.offer_cpc * 1000) + b.offer_cpm) AS rotation_weight 
        FROM banners b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN banner_events be ON b.id = be.banner_id
        WHERE 
            FIND_IN_SET(:city_slug, b.city_slugs) 
            AND b.is_active = TRUE
            AND b.is_approved = TRUE
            AND u.account_balance > 50 -- Mínimo saldo para rodar (50 pesos)
        GROUP BY b.id
        HAVING 
            impresiones_actuales < b.max_impresiones 
            AND clicks_actuales < b.max_clicks
    ");

    $stmt->execute([':city_slug' => $city_slug]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo json_encode(['success' => false, 'message' => 'No hay anuncios disponibles.']);
        exit;
    }

    // 2. ALGORITMO DE RULETA PONDERADA (Weighted Random)
    $totalWeight = 0;
    foreach ($candidates as $row) {
        // Asegurar que el peso sea al menos 1 para que todos tengan oportunidad
        $weight = max(1, (float)$row['rotation_weight']);
        $totalWeight += $weight;
    }

    $random = (float)rand() / (float)getrandmax() * $totalWeight;
    $selected_banner = null;

    foreach ($candidates as $row) {
        $weight = max(1, (float)$row['rotation_weight']);
        $random -= $weight;
        if ($random <= 0) {
            $selected_banner = $row;
            break;
        }
    }
    
    // Fallback por si acaso
    if (!$selected_banner) {
        $selected_banner = $candidates[0];
    }

    // 3. RETORNO EXITOSO
    echo json_encode([
        'success' => true,
        'banner' => [
            'id' => $selected_banner['id'],
            'titulo' => $selected_banner['titulo'],
            'descripcion' => $selected_banner['descripcion'],
            'logo_url' => $selected_banner['logo_url'],
            'cta_url' => $selected_banner['cta_url'],
            'posicion' => $selected_banner['posicion'],
            'tiempo_muestra' => (int)$selected_banner['tiempo_muestra'],
            'offer_cpc' => (float)$selected_banner['offer_cpc'], 
            'offer_cpm' => (float)$selected_banner['offer_cpm'],
            // Pasamos la configuración de frecuencia al frontend
            'freq_max' => (int)($selected_banner['freq_max_views'] ?? 3),
            'freq_hours' => (int)($selected_banner['freq_reset_hours'] ?? 6)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error Server Ads: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno.']);
}
?>
