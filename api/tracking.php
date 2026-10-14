<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    $code = trim($_GET['code'] ?? '');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (empty($code) && !$id) {
        // Por defecto tomar la última orden para demostración si no se especifica
        $stmtLatest = $db->query("SELECT id, order_code FROM `orders` ORDER BY id DESC LIMIT 1");
        $latest = $stmtLatest->fetch();
        if ($latest) {
            $code = $latest['order_code'];
        } else {
            jsonResponse(['success' => false, 'message' => 'No hay pedidos activos.'], 404);
        }
    }

    $where = !empty($code) ? "o.order_code = :val" : "o.id = :val";
    $paramVal = !empty($code) ? $code : $id;

    $stmt = $db->prepare("
        SELECT o.*, 
               r.name as rider_name, r.phone as rider_phone, r.vehicle as rider_vehicle, 
               r.plate as rider_plate, r.rating as rider_rating, r.photo as rider_photo,
               r.current_street as rider_street
        FROM `orders` o
        LEFT JOIN `riders` r ON o.rider_id = r.id
        WHERE {$where}
        LIMIT 1
    ");
    $stmt->execute([':val' => $paramVal]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Orden no encontrada.'], 404);
    }

    // Consultar items
    $stmtItems = $db->prepare("SELECT * FROM `order_items` WHERE `order_id` = :oid");
    $stmtItems->execute([':oid' => $order['id']]);
    $items = $stmtItems->fetchAll();

    foreach ($items as &$it) {
        $it['toppings'] = json_decode($it['toppings_json'] ?? '[]', true);
        $it['sauces'] = json_decode($it['sauces_json'] ?? '[]', true);
        $it['base_price_formatted'] = '$' . number_format($it['base_price'], 0, ',', '.');
        $it['subtotal_formatted'] = '$' . number_format($it['subtotal'], 0, ',', '.');
    }

    // Consultar timeline
    $stmtEvents = $db->prepare("SELECT * FROM `order_tracking_events` WHERE `order_id` = :oid ORDER BY `created_at` ASC");
    $stmtEvents->execute([':oid' => $order['id']]);
    $events = $stmtEvents->fetchAll();

    $status = $order['status'];
    $stepIndex = match($status) {
        'recibido' => 0,
        'preparando' => 1,
        'en_camino' => 2,
        'entregado' => 3,
        default => 0
    };

    $percent = match($status) {
        'recibido' => 25,
        'preparando' => 55,
        'en_camino' => 85,
        'entregado' => 100,
        default => 10
    };

    $etaMinutes = match($status) {
        'recibido' => '25-30 min',
        'preparando' => '15-20 min',
        'en_camino' => '6-10 min',
        'entregado' => '¡Entregado!',
        default => '30 min'
    };

    $statusTitle = match($status) {
        'recibido' => 'Orden Confirmada',
        'preparando' => 'En Carbón & Cocina',
        'en_camino' => 'Repartidor en Camino',
        'entregado' => '¡Pedido Entregado!',
        default => 'En Proceso'
    };

    $statusSubtext = match($status) {
        'recibido' => 'La cocina en Neiva ha confirmado los ingredientes y prendido la parrilla.',
        'preparando' => 'Tu comida se está asando con leña y carbón al punto exacto.',
        'en_camino' => 'Carlos va en ruta por la Av. Circunvalar con el termo térmico caliente.',
        'entregado' => '¡Disfruta tu antojo! Califica la experiencia del sabor huilense.',
        default => 'Procesando tu pedido en Neiva'
    };

    // Puntos de ruta simulados en Neiva (Coordenadas reales de Neiva)
    $neivaWaypoints = [
        'restaurant' => ['lat' => 2.9273, 'lng' => -75.2819, 'name' => 'La Estación Burger (Centro)'],
        'rider' => match($status) {
            'recibido' => ['lat' => 2.9273, 'lng' => -75.2819, 'street' => 'Restaurante en Centro'],
            'preparando' => ['lat' => 2.9275, 'lng' => -75.2825, 'street' => 'Cra 12 con Calle 10 (Empacando)'],
            'en_camino' => ['lat' => 2.9315, 'lng' => -75.2885, 'street' => 'Av. Circunvalar con Calle 16'],
            'entregado' => ['lat' => 2.9290, 'lng' => -75.2860, 'street' => 'En tu dirección (Cra 5 #18-42)'],
            default => ['lat' => 2.9273, 'lng' => -75.2819, 'street' => 'Centro de Neiva']
        },
        'destination' => ['lat' => 2.9290, 'lng' => -75.2860, 'name' => $order['delivery_address']]
    ];

    jsonResponse([
        'success' => true,
        'order_id' => (int)$order['id'],
        'order_code' => $order['order_code'],
        'status' => $status,
        'status_title' => $statusTitle,
        'status_subtext' => $statusSubtext,
        'step_index' => $stepIndex,
        'progress_percent' => $percent,
        'eta_minutes' => $etaMinutes,
        'created_at' => $order['created_at'],
        'customer' => [
            'name' => $order['customer_name'],
            'phone' => $order['customer_phone'],
            'address' => $order['delivery_address'],
            'comuna' => $order['comuna'],
            'instructions' => $order['instructions']
        ],
        'payment' => [
            'method' => $order['payment_method'],
            'subtotal' => (int)$order['subtotal'],
            'subtotal_formatted' => '$' . number_format($order['subtotal'], 0, ',', '.'),
            'discount' => (int)$order['discount'],
            'discount_formatted' => '$' . number_format($order['discount'], 0, ',', '.'),
            'delivery_fee' => (int)$order['delivery_fee'],
            'delivery_fee_formatted' => '$' . number_format($order['delivery_fee'], 0, ',', '.'),
            'tip' => (int)$order['tip'],
            'tip_formatted' => '$' . number_format($order['tip'], 0, ',', '.'),
            'total' => (int)$order['total'],
            'total_formatted' => '$' . number_format($order['total'], 0, ',', '.')
        ],
        'rider' => [
            'name' => $order['rider_name'] ?? 'Carlos Mendoza',
            'phone' => $order['rider_phone'] ?? '+57 312 458 9210',
            'vehicle' => $order['rider_vehicle'] ?? 'Bajaj Pulsar 200 NS - Negra',
            'plate' => $order['rider_plate'] ?? 'HUI-84E',
            'rating' => (float)($order['rider_rating'] ?? 4.9),
            'photo' => $order['rider_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80',
            'street' => $neivaWaypoints['rider']['street']
        ],
        'route' => $neivaWaypoints,
        'items' => $items,
        'timeline' => $events
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
