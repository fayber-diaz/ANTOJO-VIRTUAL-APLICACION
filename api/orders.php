<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    if ($method === 'POST') {
        $input = getJsonInput();

        $customerName = trim($input['customer_name'] ?? 'Cliente Neiva');
        $customerPhone = trim($input['customer_phone'] ?? '+57 300 000 0000');
        $address = trim($input['delivery_address'] ?? 'Cra 5 #10-00, Neiva');
        $comuna = trim($input['comuna'] ?? 'Comuna 4 - Centro');
        $timing = in_array($input['delivery_timing'] ?? '', ['immediate', 'scheduled']) ? $input['delivery_timing'] : 'immediate';
        $instructions = trim($input['instructions'] ?? '');
        $paymentMethod = $input['payment_method'] ?? 'nequi';
        $cashChangeFor = trim($input['cash_change_for'] ?? '');
        $items = $input['items'] ?? [];
        $couponCode = trim($input['coupon_code'] ?? '');

        if (empty($items) || !is_array($items)) {
            jsonResponse(['success' => false, 'message' => 'El carrito está vacío. Agrega productos para ordenar.'], 400);
        }

        // Calcular subtotales
        $subtotal = 0;
        foreach ($items as $item) {
            $base = (int)($item['basePrice'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            
            // Sumar toppings
            $toppingsCost = 0;
            if (!empty($item['toppings']) && is_array($item['toppings'])) {
                foreach ($item['toppings'] as $top) {
                    $topPrice = (int)($top['price'] ?? 0);
                    $topCount = max(1, (int)($top['count'] ?? 1));
                    $toppingsCost += ($topPrice * $topCount);
                }
            }
            $itemTotal = ($base + $toppingsCost) * $qty;
            $subtotal += $itemTotal;
        }

        // Validar cupón si fue ingresado
        $discount = 0;
        if (!empty($couponCode)) {
            $stmtC = $db->prepare("SELECT * FROM `coupons` WHERE `code` = :code AND `is_active` = 1 LIMIT 1");
            $stmtC->execute([':code' => strtoupper($couponCode)]);
            $coupon = $stmtC->fetch();
            if ($coupon && $subtotal >= $coupon['min_purchase']) {
                $discount = (int)$coupon['discount_amount'];
                // Incrementar contador
                $db->prepare("UPDATE `coupons` SET `used_count` = `used_count` + 1 WHERE `id` = :id")->execute([':id' => $coupon['id']]);
            }
        }

        $deliveryFee = 3500;
        $tip = 1000;
        $total = max(0, $subtotal - $discount + $deliveryFee + $tip);

        // Generar código único de orden
        $orderCode = '#NV-' . rand(1000, 9999);

        // Asignar rider aleatorio de Neiva
        $stmtR = $db->query("SELECT id FROM `riders` WHERE `is_active` = 1 ORDER BY RAND() LIMIT 1");
        $rider = $stmtR->fetch();
        $riderId = $rider ? (int)$rider['id'] : null;

        // Iniciar transacción
        $db->beginTransaction();

        $stmtOrder = $db->prepare("
            INSERT INTO `orders` (
                `order_code`, `customer_name`, `customer_phone`, `delivery_address`, `comuna`,
                `delivery_timing`, `instructions`, `payment_method`, `cash_change_for`,
                `subtotal`, `discount`, `delivery_fee`, `tip`, `total`, `status`, `rider_id`
            ) VALUES (
                :code, :cname, :cphone, :addr, :comuna,
                :timing, :instructions, :pmethod, :cchange,
                :subtotal, :discount, :fee, :tip, :total, 'recibido', :rider_id
            )
        ");

        $stmtOrder->execute([
            ':code' => $orderCode,
            ':cname' => $customerName,
            ':cphone' => $customerPhone,
            ':addr' => $address,
            ':comuna' => $comuna,
            ':timing' => $timing,
            ':instructions' => $instructions,
            ':pmethod' => $paymentMethod,
            ':cchange' => $cashChangeFor,
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':fee' => $deliveryFee,
            ':tip' => $tip,
            ':total' => $total,
            ':rider_id' => $riderId
        ]);

        $orderId = (int)$db->lastInsertId();

        // Insertar items
        $stmtItem = $db->prepare("
            INSERT INTO `order_items` (
                `order_id`, `menu_item_id`, `item_name`, `restaurant_name`,
                `base_price`, `quantity`, `doneness`, `bread`,
                `toppings_json`, `sauces_json`, `notes`, `subtotal`
            ) VALUES (
                :order_id, :mid, :iname, :rname,
                :bprice, :qty, :doneness, :bread,
                :toppings, :sauces, :notes, :item_subtotal
            )
        ");

        $mainRestaurant = 'La Estación Burger';

        foreach ($items as $item) {
            $base = (int)($item['basePrice'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $toppings = $item['toppings'] ?? [];
            $sauces = $item['sauces'] ?? [];
            
            $toppingsCost = 0;
            if (is_array($toppings)) {
                foreach ($toppings as $top) {
                    $topPrice = (int)($top['price'] ?? 0);
                    $topCount = max(1, (int)($top['count'] ?? 1));
                    $toppingsCost += ($topPrice * $topCount);
                }
            }
            $itemSubtotal = ($base + $toppingsCost) * $qty;
            if (!empty($item['restaurant'])) {
                $mainRestaurant = $item['restaurant'];
            }

            $stmtItem->execute([
                ':order_id' => $orderId,
                ':mid' => !empty($item['id']) ? (int)$item['id'] : null,
                ':iname' => $item['name'] ?? 'Producto Antojo',
                ':rname' => $item['restaurant'] ?? 'La Estación Burger',
                ':bprice' => $base,
                ':qty' => $qty,
                ':doneness' => $item['doneness'] ?? null,
                ':bread' => $item['bread'] ?? null,
                ':toppings' => json_encode($toppings, JSON_UNESCAPED_UNICODE),
                ':sauces' => json_encode($sauces, JSON_UNESCAPED_UNICODE),
                ':notes' => $item['notes'] ?? '',
                ':item_subtotal' => $itemSubtotal
            ]);
        }

        // Insertar evento inicial de rastreo
        $stmtEvent = $db->prepare("
            INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`)
            VALUES (:oid, 'recibido', 'Orden Recibida y Confirmada', :desc)
        ");
        $stmtEvent->execute([
            ':oid' => $orderId,
            ':desc' => "¡Listo! Tu pedido {$orderCode} fue confirmado por {$mainRestaurant} en Neiva. Estamos alistando los ingredientes frescos."
        ]);

        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => '¡Pedido creado y confirmado exitosamente en Neiva!',
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'total' => $total,
            'total_formatted' => '$' . number_format($total, 0, ',', '.'),
            'status' => 'recibido'
        ]);

    } elseif ($method === 'GET') {
        $code = trim($_GET['code'] ?? '');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!empty($code) || $id) {
            // Consultar orden específica
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

            // Consultar eventos de rastreo
            $stmtEvents = $db->prepare("SELECT * FROM `order_tracking_events` WHERE `order_id` = :oid ORDER BY `created_at` ASC");
            $stmtEvents->execute([':oid' => $order['id']]);
            $events = $stmtEvents->fetchAll();

            $order['items'] = $items;
            $order['events'] = $events;
            $order['subtotal_formatted'] = '$' . number_format($order['subtotal'], 0, ',', '.');
            $order['discount_formatted'] = '$' . number_format($order['discount'], 0, ',', '.');
            $order['delivery_fee_formatted'] = '$' . number_format($order['delivery_fee'], 0, ',', '.');
            $order['total_formatted'] = '$' . number_format($order['total'], 0, ',', '.');

            jsonResponse(['success' => true, 'order' => $order]);
        }

        // Listado de órdenes recientes para dashboard
        $stmt = $db->query("
            SELECT o.*, 
                   COUNT(i.id) as total_items,
                   r.name as rider_name
            FROM `orders` o
            LEFT JOIN `order_items` i ON o.id = i.order_id
            LEFT JOIN `riders` r ON o.rider_id = r.id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$ord) {
            $ord['total_formatted'] = '$' . number_format($ord['total'], 0, ',', '.');
        }

        jsonResponse([
            'success' => true,
            'count' => count($orders),
            'orders' => $orders
        ]);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ], 500);
}
