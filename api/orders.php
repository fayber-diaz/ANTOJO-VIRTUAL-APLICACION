<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    if ($method === 'POST') {
        $input = getJsonInput();

        // Obtener usuario autenticado vía JWT (si existe)
        $authUser = JWT::getAuthUser();
        $userId = $authUser ? (int)$authUser['id'] : null;

        $customerName = trim($input['customer_name'] ?? ($authUser ? $authUser['name'] : 'Cliente Neiva'));
        $customerPhone = trim($input['customer_phone'] ?? ($authUser ? $authUser['phone'] : '+57 300 000 0000'));
        $address = trim($input['delivery_address'] ?? ($authUser ? $authUser['address'] : 'Cra 5 #10-00, Neiva'));
        $comuna = trim($input['comuna'] ?? ($authUser ? $authUser['comuna'] : 'Comuna 4 - Centro'));
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

        $customerEmail = trim($input['customer_email'] ?? ($authUser ? $authUser['email'] : 'cliente@antojo.com'));
        $paymentData = $input['payment_data'] ?? [];
        $isCash = in_array($paymentMethod, ['cash_efectivo', 'cash_datafono']);
        $paymentStatus = $isCash ? 'pending' : 'paid';

        // Calcular impuesto al consumo 8%
        $taxImpoconsumo = (int)round(($subtotal - $discount) * 0.08);

        // Iniciar transacción
        $db->beginTransaction();

        $stmtOrder = $db->prepare("
            INSERT INTO `orders` (
                `user_id`, `order_code`, `customer_name`, `customer_phone`, `customer_email`, `delivery_address`, `comuna`,
                `delivery_timing`, `instructions`, `payment_method`, `payment_status`, `cash_change_for`,
                `subtotal`, `discount`, `delivery_fee`, `tip`, `tax_impoconsumo`, `total`, `status`, `rider_id`
            ) VALUES (
                :uid, :code, :cname, :cphone, :cemail, :addr, :comuna,
                :timing, :instructions, :pmethod, :pstatus, :cchange,
                :subtotal, :discount, :fee, :tip, :tax, :total, 'recibido', :rider_id
            )
        ");

        $stmtOrder->execute([
            ':uid' => $userId,
            ':code' => $orderCode,
            ':cname' => $customerName,
            ':cphone' => $customerPhone,
            ':cemail' => $customerEmail,
            ':addr' => $address,
            ':comuna' => $comuna,
            ':timing' => $timing,
            ':instructions' => $instructions,
            ':pmethod' => $paymentMethod,
            ':pstatus' => $paymentStatus,
            ':cchange' => $cashChangeFor,
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':fee' => $deliveryFee,
            ':tip' => $tip,
            ':tax' => $taxImpoconsumo,
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

        // Registrar transacción de pago en la pasarela
        $txRef = 'PAY-NV-' . date('Ymd') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $authCode = 'AUT-' . rand(100000, 999999);
        $bankName = $paymentData['bank_name'] ?? null;
        $bankId = !empty($paymentData['bank_id']) ? (int)$paymentData['bank_id'] : null;
        $cardBrand = $paymentData['card_brand'] ?? null;
        $cardLastFour = $paymentData['card_last_four'] ?? null;
        $cardholderName = $paymentData['cardholder_name'] ?? null;
        $installments = max(1, (int)($paymentData['installments'] ?? 1));
        $payerDocType = $paymentData['payer_doc_type'] ?? 'CC';
        $payerDocNumber = $paymentData['payer_doc_number'] ?? '1075304128';

        if ($paymentMethod === 'pse' && empty($bankName)) {
            $bankName = 'Bancolombia S.A.';
        } elseif ($paymentMethod === 'nequi') {
            $bankName = 'Nequi (Bancolombia)';
        } elseif ($paymentMethod === 'daviplata') {
            $bankName = 'Daviplata (Davivienda)';
        } elseif ($paymentMethod === 'cash_efectivo') {
            $bankName = 'Efectivo contra Entrega';
        } elseif ($paymentMethod === 'cash_datafono') {
            $bankName = 'Datáfono contra Entrega';
        }

        $stmtPay = $db->prepare("
            INSERT INTO `payments` (
                `order_id`, `payment_method`, `transaction_reference`, `gateway`, `amount`, `currency`,
                `status`, `authorization_code`, `bank_id`, `bank_name`,
                `payer_doc_type`, `payer_doc_number`, `payer_person_type`, `payer_email`, `payer_phone`,
                `card_brand`, `card_last_four`, `cardholder_name`, `installments`,
                `gateway_response`, `ip_address`, `paid_at`
            ) VALUES (
                :order_id, :pmethod, :tx_ref, 'antojo_pay_gateway', :amount, 'COP',
                :status, :auth_code, :bank_id, :bank_name,
                :doc_type, :doc_num, 'natural', :email, :phone,
                :cbrand, :clast4, :cholder, :installments,
                :gw_response, :ip, :paid_at
            )
        ");

        $payStatus = $isCash ? 'pending' : 'approved';
        $paidAt = $isCash ? null : date('Y-m-d H:i:s');
        $gwResponse = json_encode([
            'status' => strtoupper($payStatus),
            'ref' => $txRef,
            'auth' => $authCode,
            'method' => $paymentMethod,
            'bank' => $bankName
        ], JSON_UNESCAPED_UNICODE);

        $stmtPay->execute([
            ':order_id' => $orderId,
            ':pmethod' => $paymentMethod,
            ':tx_ref' => $txRef,
            ':amount' => $total,
            ':status' => $payStatus,
            ':auth_code' => $isCash ? null : $authCode,
            ':bank_id' => $bankId,
            ':bank_name' => $bankName,
            ':doc_type' => $payerDocType,
            ':doc_num' => $payerDocNumber,
            ':email' => $customerEmail,
            ':phone' => $customerPhone,
            ':cbrand' => $cardBrand,
            ':clast4' => $cardLastFour,
            ':cholder' => $cardholderName,
            ':installments' => $installments,
            ':gw_response' => $gwResponse,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':paid_at' => $paidAt
        ]);

        $paymentId = (int)$db->lastInsertId();

        // Generar Factura Electrónica
        $invoiceNumber = 'FE-ANTOJO-' . date('Y') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
        $cufeCode = hash('sha384', $invoiceNumber . date('YmdHis') . $total . $customerName);
        $methodLabel = $bankName ?: (strtoupper($cardBrand ?? 'TARJETA') . ' •••• ' . ($cardLastFour ?? ''));
        $qrString = "NumFac={$invoiceNumber}&FecFac=" . date('Y-m-d') . "&NitFac=901458921-4&DocAdq={$payerDocNumber}&ValFac={$total}&CUFE=" . substr($cufeCode, 0, 16);

        $stmtInv = $db->prepare("
            INSERT INTO `invoices` (
                `order_id`, `payment_id`, `invoice_number`, `cufe_code`,
                `customer_name`, `customer_document`, `customer_email`, `customer_phone`, `customer_address`,
                `subtotal`, `discount`, `tax_impoconsumo`, `delivery_fee`, `tip`, `total`,
                `payment_method_label`, `transaction_reference`, `qr_string`, `status`
            ) VALUES (
                :oid, :pid, :inv_num, :cufe,
                :cname, :cdoc, :cemail, :cphone, :caddr,
                :subtotal, :discount, :tax, :fee, :tip, :total,
                :pmethod_label, :tx_ref, :qr, 'issued'
            )
        ");

        $stmtInv->execute([
            ':oid' => $orderId,
            ':pid' => $paymentId,
            ':inv_num' => $invoiceNumber,
            ':cufe' => $cufeCode,
            ':cname' => $customerName,
            ':cdoc' => "{$payerDocType} {$payerDocNumber}",
            ':cemail' => $customerEmail,
            ':cphone' => $customerPhone,
            ':caddr' => "{$address} ({$comuna})",
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':tax' => $taxImpoconsumo,
            ':fee' => $deliveryFee,
            ':tip' => $tip,
            ':total' => $total,
            ':pmethod_label' => $methodLabel,
            ':tx_ref' => $txRef,
            ':qr' => $qrString
        ]);

        // Insertar eventos iniciales de rastreo
        $paymentTrackingText = $isCash
            ? "Método de pago seleccionado: {$methodLabel}. Cobro contra entrega en Neiva."
            : "Pago verificado y aprobado vía {$methodLabel}. Ref: {$txRef}. Aprobación: {$authCode}.";

        $stmtEvent = $db->prepare("
            INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`)
            VALUES (:oid, 'recibido', :title, :desc)
        ");
        $stmtEvent->execute([
            ':oid' => $orderId,
            ':title' => 'Orden y Pago Registrados',
            ':desc' => "{$paymentTrackingText} {$mainRestaurant} está alistando los ingredientes frescos."
        ]);

        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => '¡Pedido creado y confirmado exitosamente en Neiva!',
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'user_id' => $userId,
            'total' => $total,
            'total_formatted' => '$' . number_format($total, 0, ',', '.'),
            'payment_status' => $paymentStatus,
            'payment' => [
                'id' => $paymentId,
                'transaction_reference' => $txRef,
                'authorization_code' => $authCode,
                'payment_method' => $paymentMethod,
                'method_label' => $methodLabel,
                'status' => $payStatus
            ],
            'invoice' => [
                'invoice_number' => $invoiceNumber,
                'cufe_code' => $cufeCode
            ],
            'status' => 'recibido'
        ]);

    } elseif ($method === 'GET') {
        // Consultar mis pedidos (Protegido por JWT)
        if (isset($_GET['my_orders'])) {
            $user = JWT::requireAuth();
            $stmt = $db->prepare("
                SELECT o.*, COUNT(i.id) as total_items
                FROM `orders` o
                LEFT JOIN `order_items` i ON o.id = i.order_id
                WHERE o.user_id = :uid OR o.customer_phone = :phone
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([':uid' => $user['id'], ':phone' => $user['phone']]);
            $orders = $stmt->fetchAll();

            foreach ($orders as &$ord) {
                $ord['total_formatted'] = '$' . number_format($ord['total'], 0, ',', '.');
            }

            jsonResponse(['success' => true, 'orders' => $orders]);
        }

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
