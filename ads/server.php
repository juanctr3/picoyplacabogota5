<?php
/**
 * ads/server.php - Módulo de Servidor de Anuncios SaaS (Priorización por Oferta)
 * Calcula la Prioridad Dinámica (Bid Priority) y filtra por saldo y aprobación.
 */

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

// 1. CONFIGURACIÓN DE LA BASE DE DATOS
$dbHost = 'localhost';
$dbName = 'picoyplacabogota';   
$dbUser = 'picoyplacabogota';   
$dbPass = 'Q20BsIFHI9j8h2XoYNQm3RmQg';   

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $city_slug = $_GET['ciudad'] ?? '';

    // 2. CONSULTA A LA BD CON PRIORIZACIÓN DINÁMICA POR OFERTA
    // Priorización: Se calcula el valor de la oferta (offer_cpc * 1000 + offer_cpm * 0.1)
    $stmt = $pdo->prepare("
        SELECT 
            b.*, u.account_balance,
            COALESCE(SUM(CASE WHEN be.event_type = 'impresion' THEN 1 ELSE 0 END), 0) AS impresiones_actuales,
            COALESCE(SUM(CASE WHEN be.event_type = 'click' THEN 1 ELSE 0 END), 0) AS clicks_actuales,
            -- Cálculo de Prioridad Dinámica (Bid Priority): El que más paga primero
            (b.offer_cpc * 1000 + b.offer_cpm * 0.1) AS bid_priority 
        FROM banners b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN banner_events be ON b.id = be.banner_id
        WHERE 
            FIND_IN_SET(:city_slug, b.city_slugs) 
            AND b.is_active = TRUE
            AND b.is_approved = TRUE -- Solo banners aprobados
            AND u.account_balance > 0 -- Saldo debe ser positivo
        GROUP BY b.id, b.titulo, u.account_balance, b.offer_cpc, b.offer_cpm 
        ORDER BY 
            bid_priority DESC, -- 1. El que más paga primero
            LENGTH(b.city_slugs) ASC, -- 2. Desempate: Más específico
            b.id ASC 
        LIMIT 1
    ");

    $stmt->execute([':city_slug' => $city_slug]);
    $active_banner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($active_banner) {
        $banner_id = (int)$active_banner['id'];
        
        // 3. APLICAR LÓGICA DE LÍMITES Y DESACTIVACIÓN AUTOMÁTICA
        if (
            (int)$active_banner['impresiones_actuales'] >= (int)$active_banner['max_impresiones'] ||
            (int)$active_banner['clicks_actuales'] >= (int)$active_banner['max_clicks']
        ) {
            
            // Si el límite se excede, DESACTIVAMOS el banner
            $update_stmt = $pdo->prepare("UPDATE banners SET is_active = FALSE WHERE id = :id");
            $update_stmt->execute([':id' => $banner_id]);

            error_log("Campaña ID: {$banner_id} DESACTIVADA automáticamente. Límites excedidos.");
            echo json_encode(['success' => false, 'message' => 'Campaña finalizada.']);

        } else {
            // 4. RETORNO EXITOSO
            echo json_encode([
                'success' => true,
                'banner' => [
                    'id' => $active_banner['id'],
                    'titulo' => $active_banner['titulo'],
                    'descripcion' => $active_banner['descripcion'],
                    'logo_url' => $active_banner['logo_url'],
                    'cta_url' => $active_banner['cta_url'],
                    'posicion' => $active_banner['posicion'],
                    'tiempo_muestra' => (int)$active_banner['tiempo_muestra'],
                    'frecuencia_factor' => (int)$active_banner['frecuencia_factor'],
                    // Pasar la oferta para que el log.php sepa cuánto cobrar
                    'offer_cpc' => (float)$active_banner['offer_cpc'], 
                    'offer_cpm' => (float)$active_banner['offer_cpm']
                ]
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay banners activos para esta ciudad.']);
    }

} catch (PDOException $e) {
    error_log("Error en el servidor de anuncios: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de servidor.']);
}
?>