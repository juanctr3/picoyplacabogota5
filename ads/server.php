<?php
/**
 * ads/server.php - AdServer con Rotación de Inventario y Exclusión
 */
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

require_once '../user/db_connect.php'; 

$citySlug = $_GET['ciudad'] ?? '';
$requestedPos = $_GET['posicion'] ?? 'top';
$excludeStr = $_GET['exclude'] ?? ''; // IDs a excluir (separados por coma)

// Sanitizar IDs excluidos
$excludeIds = array_filter(explode(',', $excludeStr), 'is_numeric');

try {
    // 1. Construir Query Dinámica
    // Seleccionamos banners que coincidan con la ciudad, estén activos, tengan saldo
    // y coincidan con la posición solicitada O sean 'both' (ambas).
    // Además, EXCLUIMOS los que el usuario ya vio en esta sesión.
    
    $sql = "
        SELECT 
            b.*, 
            ((b.offer_cpc * 1000) + b.offer_cpm) as weight
        FROM banners b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN banner_events be ON b.id = be.banner_id
        WHERE 
            FIND_IN_SET(:city_slug, b.city_slugs) 
            AND b.is_active = 1
            AND b.is_approved = 1
            AND u.account_balance > 50
            AND (b.posicion = :req_pos OR b.posicion = 'both')
    ";

    // Agregar exclusión si hay IDs
    if (!empty($excludeIds)) {
        $sql .= " AND b.id NOT IN (" . implode(',', $excludeIds) . ")";
    }

    $sql .= " GROUP BY b.id HAVING 
              (COALESCE(SUM(CASE WHEN be.event_type='impresion' THEN 1 ELSE 0 END),0) < b.max_impresiones) AND 
              (COALESCE(SUM(CASE WHEN be.event_type='click' THEN 1 ELSE 0 END),0) < b.max_clicks)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':city_slug' => $citySlug, ':req_pos' => $requestedPos]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo json_encode(['success' => false, 'message' => 'Sin inventario disponible.']);
        exit;
    }

    // 2. Ruleta Ponderada (Weighted Random)
    // Esto asegura que los de mayor presupuesto salgan más, 
    // PERO como excluimos los ya vistos, eventualmente saldrán los pequeños.
    $totalWeight = 0;
    foreach ($candidates as $row) {
        $w = (float)$row['weight'];
        if ($w <= 0) $w = 1; 
        $totalWeight += $w;
    }

    $random = (float)rand() / (float)getrandmax() * $totalWeight;
    $selected = null;

    foreach ($candidates as $row) {
        $w = (float)$row['weight'];
        if ($w <= 0) $w = 1;
        $random -= $w;
        if ($random <= 0) {
            $selected = $row;
            break;
        }
    }
    if (!$selected) $selected = $candidates[0];

    echo json_encode([
        'success' => true,
        'banner' => [
            'id' => $selected['id'],
            'titulo' => $selected['titulo'],
            'descripcion' => $selected['descripcion'],
            'logo_url' => $selected['logo_url'],
            'cta_url' => $selected['cta_url'],
            'posicion' => $requestedPos, // Devolvemos la posición solicitada para que el JS sepa dónde ponerlo
            'tiempo_muestra' => (int)$selected['tiempo_muestra'],
            'offer_cpc' => (float)$selected['offer_cpc'],
            'offer_cpm' => (float)$selected['offer_cpm']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>