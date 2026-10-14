<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    // Si se piden solo las opciones de personalización (toppings, panes, términos, salsas)
    if (isset($_GET['options'])) {
        $stmt = $db->query("SELECT * FROM `item_options` ORDER BY `category`, `price` ASC");
        $allOptions = $stmt->fetchAll();

        $grouped = [
            'terminos' => [],
            'panes' => [],
            'toppings' => [],
            'salsas' => []
        ];

        foreach ($allOptions as $opt) {
            $cat = $opt['category'];
            $key = match($cat) {
                'termino' => 'terminos',
                'pan' => 'panes',
                'topping' => 'toppings',
                'salsa' => 'salsas',
                default => 'toppings'
            };

            $opt['id'] = (int)$opt['id'];
            $opt['price'] = (int)$opt['price'];
            $opt['is_default'] = (bool)$opt['is_default'];
            $opt['price_formatted'] = $opt['price'] > 0 ? '+$' . number_format($opt['price'], 0, ',', '.') : 'Gratis';

            $grouped[$key][] = $opt;
        }

        jsonResponse([
            'success' => true,
            'options' => $grouped
        ]);
    }

    // Detalle de un plato específico
    if (isset($_GET['id'])) {
        $dishId = (int)$_GET['id'];
        $stmt = $db->prepare("
            SELECT m.*, r.name as restaurant_name, r.delivery_time, r.delivery_fee
            FROM `menu_items` m
            JOIN `restaurants` r ON m.restaurant_id = r.id
            WHERE m.id = :id AND m.is_available = 1
        ");
        $stmt->execute([':id' => $dishId]);
        $dish = $stmt->fetch();

        if (!$dish) {
            jsonResponse(['success' => false, 'error' => 'Plato no encontrado'], 404);
        }

        $dish['id'] = (int)$dish['id'];
        $dish['price'] = (int)$dish['price'];
        $dish['price_formatted'] = '$' . number_format($dish['price'], 0, ',', '.');
        $dish['tags_array'] = !empty($dish['tags']) ? array_map('trim', explode(',', $dish['tags'])) : [];

        jsonResponse([
            'success' => true,
            'data' => $dish
        ]);
    }

    // Listado de platos con filtros
    $category = $_GET['category'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;

    $query = "
        SELECT m.*, r.name as restaurant_name, r.badge as restaurant_badge, r.delivery_time
        FROM `menu_items` m
        JOIN `restaurants` r ON m.restaurant_id = r.id
        WHERE m.is_available = 1
    ";
    $params = [];

    if ($category !== 'all' && !empty($category)) {
        $query .= " AND m.category = :category";
        $params[':category'] = $category;
    }

    if ($restaurantId) {
        $query .= " AND m.restaurant_id = :restaurant_id";
        $params[':restaurant_id'] = $restaurantId;
    }

    if (!empty($search)) {
        $query .= " AND (m.name LIKE :search OR m.description LIKE :search OR m.tags LIKE :search OR r.name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY m.is_featured DESC, m.is_popular DESC, m.id ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $dishes = $stmt->fetchAll();

    foreach ($dishes as &$d) {
        $d['id'] = (int)$d['id'];
        $d['restaurant_id'] = (int)$d['restaurant_id'];
        $d['price'] = (int)$d['price'];
        $d['price_formatted'] = '$' . number_format($d['price'], 0, ',', '.');
        $d['tags_array'] = !empty($d['tags']) ? array_map('trim', explode(',', $d['tags'])) : [];
        $d['is_featured'] = (bool)$d['is_featured'];
        $d['is_popular'] = (bool)$d['is_popular'];
    }

    jsonResponse([
        'success' => true,
        'count' => count($dishes),
        'data' => $dishes
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
