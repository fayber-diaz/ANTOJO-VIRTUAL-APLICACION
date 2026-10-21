<?php
/**
 * =========================================================
 * ANTOJO VIRTUAL — API de Pasarela de Pagos Multibanco
 * Compatible con XAMPP / MariaDB / MySQL
 * Neiva, Huila • Neumorfismo Soft UI
 * 
 * Endpoints:
 *  GET  ?banks=1         -> Lista de 18 entidades bancarias colombianas (PSE)
 *  GET  ?methods=1       -> Métodos de pago soportados
 *  GET  ?comunas=1       -> Zonas de Neiva con tarifas y tiempos
 *  GET  ?receipt=<ref>   -> Comprobante oficial de pago y factura electrónica
 *  POST                  -> Procesar transacción (Tarjeta, PSE, Nequi, Daviplata, Efectivo)
 * =========================================================
 */

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

    // -----------------------------------------------------
    // PETICIONES GET
    // -----------------------------------------------------
    if ($method === 'GET') {

        // 1. Lista de bancos colombianos para PSE
        if (isset($_GET['banks'])) {
            $stmt = $db->query("SELECT id, code, name, short_name, type, icon_svg FROM `banks` WHERE is_active = 1 ORDER BY sort_order ASC");
            $banks = $stmt->fetchAll();
            jsonResponse([
                'success' => true,
                'count' => count($banks),
                'banks' => $banks
            ]);
        }

        // 2. Métodos de pago disponibles
        if (isset($_GET['methods'])) {
            $stmt = $db->query("SELECT id, slug, name, description, icon, type, fee_percentage FROM `payment_methods` WHERE is_active = 1 ORDER BY sort_order ASC");
            $methods = $stmt->fetchAll();
            jsonResponse([
                'success' => true,
                'methods' => $methods
            ]);
        }

        // 3. Comunas y tarifas de entrega en Neiva
        if (isset($_GET['comunas'])) {
            $stmt = $db->query("SELECT id, code, name, description, delivery_fee, estimated_minutes FROM `comunas` WHERE is_active = 1 ORDER BY id ASC");
            $comunas = $stmt->fetchAll();
            jsonResponse([
                'success' => true,
                'comunas' => $comunas
            ]);
        }

        // 4. Consultar comprobante y factura por Referencia de Pago o ID de Orden
        $receiptRef = trim($_GET['receipt'] ?? '');
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

        if (!empty($receiptRef) || $orderId) {
            $where = !empty($receiptRef) ? "p.transaction_reference = :val" : "p.order_id = :val";
            $paramVal = !empty($receiptRef) ? $receiptRef : $orderId;

            $stmt = $db->prepare("
                SELECT 
                    p.*,
                    o.order_code, o.customer_name, o.customer_phone, o.customer_email,
                    o.delivery_address, o.comuna, o.status as order_status,
                    o.subtotal, o.discount, o.delivery_fee, o.tip, o.total,
                    inv.invoice_number, inv.cufe_code, inv.issued_at, inv.qr_string
                FROM `payments` p
                INNER JOIN `orders` o ON p.order_id = o.id
                LEFT JOIN `invoices` inv ON o.id = inv.order_id
                WHERE {$where}
                ORDER BY p.id DESC
                LIMIT 1
            ");
            $stmt->execute([':val' => $paramVal]);
            $receipt = $stmt->fetch();

            if (!$receipt) {
                jsonResponse(['success' => false, 'message' => 'Comprobante de transacción no encontrado.'], 404);
            }

            // Traer ítems de la orden asociada
            $stmtItems = $db->prepare("SELECT item_name, restaurant_name, base_price, quantity, subtotal, doneness, bread, toppings_json, notes FROM `order_items` WHERE order_id = :oid");
            $stmtItems->execute([':oid' => $receipt['order_id']]);
            $items = $stmtItems->fetchAll();

            foreach ($items as &$it) {
                $it['toppings'] = !empty($it['toppings_json']) ? json_decode($it['toppings_json'], true) : [];
            }
            $receipt['items'] = $items;

            // Formatos de moneda legibles
            $receipt['amount_formatted'] = '$' . number_format($receipt['amount'], 0, ',', '.');
            $receipt['subtotal_formatted'] = '$' . number_format($receipt['subtotal'], 0, ',', '.');
            $receipt['discount_formatted'] = '$' . number_format($receipt['discount'], 0, ',', '.');
            $receipt['delivery_fee_formatted'] = '$' . number_format($receipt['delivery_fee'], 0, ',', '.');
            $receipt['tip_formatted'] = '$' . number_format($receipt['tip'], 0, ',', '.');

            jsonResponse([
                'success' => true,
                'receipt' => $receipt
            ]);
        }

        // Si no se pasaron filtros específicos, retornar información general de la pasarela
        jsonResponse([
            'success' => true,
            'gateway' => 'AntojoPay Gateway v2.0 (Neiva - Colombia)',
            'currency' => 'COP',
            'support' => ['PSE', 'Tarjetas Visa/Mastercard/Amex/Diners', 'Nequi', 'Daviplata', 'Efectivo', 'Datáfono']
        ]);
    }

    // -----------------------------------------------------
    // PETICIÓN POST: PROCESAR PAGO
    // -----------------------------------------------------
    if ($method === 'POST') {
        $input = getJsonInput();

        $orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
        $paymentMethod = trim($input['payment_method'] ?? 'pse');
        $amount = isset($input['amount']) ? (int)$input['amount'] : 0;

        // Comprobar orden existente o requerida
        if ($orderId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Se requiere un ID de orden válido para procesar el pago.'], 400);
        }

        $stmtOrder = $db->prepare("SELECT * FROM `orders` WHERE id = :id LIMIT 1");
        $stmtOrder->execute([':id' => $orderId]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'La orden especificada no existe.'], 404);
        }

        if ($amount <= 0) {
            $amount = (int)$order['total'];
        }

        // Generar referencia única de transacción
        $txRef = 'PAY-NV-' . date('Ymd') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $authCode = 'AUT-' . rand(100000, 999999);
        $status = 'approved'; // Por defecto aprobada en la simulación

        // Variables según método
        $bankId = null;
        $bankName = null;
        $payerDocType = trim($input['payer_doc_type'] ?? 'CC');
        $payerDocNumber = trim($input['payer_doc_number'] ?? ($input['document_number'] ?? '1075304128'));
        $payerPersonType = in_array($input['payer_person_type'] ?? '', ['natural', 'juridica']) ? $input['payer_person_type'] : 'natural';
        $payerEmail = trim($input['payer_email'] ?? ($order['customer_email'] ?? 'cliente@antojo.com'));
        $payerPhone = trim($input['payer_phone'] ?? ($order['customer_phone'] ?? '+57 314 289 9012'));
        
        $cardBrand = null;
        $cardLastFour = null;
        $cardholderName = null;
        $installments = 1;

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Método: Tarjeta de Crédito / Débito
        if ($paymentMethod === 'card') {
            $cardNumber = preg_replace('/\D/', '', $input['card_number'] ?? '');
            $cardholderName = strtoupper(trim($input['cardholder_name'] ?? 'MATEO RODRIGUEZ'));
            $installments = max(1, min(36, (int)($input['installments'] ?? 1)));

            // Detección de franquicia
            if (str_starts_with($cardNumber, '4')) {
                $cardBrand = 'visa';
            } elseif (preg_match('/^(5[1-5]|2[2-7])/', $cardNumber)) {
                $cardBrand = 'mastercard';
            } elseif (preg_match('/^(34|37)/', $cardNumber)) {
                $cardBrand = 'amex';
            } elseif (preg_match('/^(30[0-5]|36|38)/', $cardNumber)) {
                $cardBrand = 'diners';
            } else {
                $cardBrand = 'visa'; // Default fallback
            }

            $cardLastFour = strlen($cardNumber) >= 4 ? substr($cardNumber, -4) : '4242';

            // Si el número de tarjeta termina en 0000, simular tarjeta declinada para pruebas de error
            if ($cardLastFour === '0000') {
                $status = 'declined';
            }
        }

        // 2. Método: PSE
        elseif ($paymentMethod === 'pse') {
            $bankId = isset($input['bank_id']) ? (int)$input['bank_id'] : 1;
            
            // Buscar nombre del banco
            $stmtB = $db->prepare("SELECT name, short_name FROM `banks` WHERE id = :id LIMIT 1");
            $stmtB->execute([':id' => $bankId]);
            $bank = $stmtB->fetch();
            $bankName = $bank ? $bank['name'] : 'Bancolombia S.A.';
        }

        // 3. Método: Nequi
        elseif ($paymentMethod === 'nequi') {
            $bankName = 'Nequi (Bancolombia)';
            $payerPhone = trim($input['phone_number'] ?? $order['customer_phone']);
        }

        // 4. Método: Daviplata
        elseif ($paymentMethod === 'daviplata') {
            $bankName = 'Daviplata (Davivienda)';
            $payerPhone = trim($input['phone_number'] ?? $order['customer_phone']);
        }

        // 5. Métodos contra entrega
        elseif ($paymentMethod === 'cash_efectivo') {
            $bankName = 'Efectivo contra Entrega';
            $status = 'pending'; // El pago se completará al recibir
        } elseif ($paymentMethod === 'cash_datafono') {
            $bankName = 'Datáfono inalámbrico Redeban/Credibanco';
            $status = 'pending';
        }

        // Iniciar transacción en Base de Datos
        $db->beginTransaction();

        $gatewayResponse = json_encode([
            'gateway' => 'antojo_pay_simulator',
            'status' => strtoupper($status),
            'reference' => $txRef,
            'auth_code' => $authCode,
            'method' => $paymentMethod,
            'bank' => $bankName,
            'card_brand' => $cardBrand,
            'card_last4' => $cardLastFour,
            'installments' => $installments,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);

        // Guardar registro en payments
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
                :doc_type, :doc_num, :person_type, :email, :phone,
                :cbrand, :clast4, :cholder, :installments,
                :gw_response, :ip, :paid_at
            )
        ");

        $paidAt = ($status === 'approved') ? date('Y-m-d H:i:s') : null;

        $stmtPay->execute([
            ':order_id' => $orderId,
            ':pmethod' => $paymentMethod,
            ':tx_ref' => $txRef,
            ':amount' => $amount,
            ':status' => $status,
            ':auth_code' => ($status === 'approved') ? $authCode : null,
            ':bank_id' => $bankId,
            ':bank_name' => $bankName,
            ':doc_type' => $payerDocType,
            ':doc_num' => $payerDocNumber,
            ':person_type' => $payerPersonType,
            ':email' => $payerEmail,
            ':phone' => $payerPhone,
            ':cbrand' => $cardBrand,
            ':clast4' => $cardLastFour,
            ':cholder' => $cardholderName,
            ':installments' => $installments,
            ':gw_response' => $gatewayResponse,
            ':ip' => $clientIp,
            ':paid_at' => $paidAt
        ]);

        $paymentId = (int)$db->lastInsertId();

        // Si fue aprobada o es contra entrega, actualizar el estado del pedido
        $orderPaymentStatus = ($status === 'approved') ? 'paid' : ($status === 'declined' ? 'declined' : 'pending');
        
        $stmtUpOrder = $db->prepare("
            UPDATE `orders` 
            SET `payment_method` = :pmethod,
                `payment_status` = :pstatus,
                `customer_email` = COALESCE(:email, `customer_email`)
            WHERE `id` = :id
        ");
        $stmtUpOrder->execute([
            ':pmethod' => $paymentMethod,
            ':pstatus' => $orderPaymentStatus,
            ':email' => $payerEmail,
            ':id' => $orderId
        ]);

        // Registrar evento en la línea de tiempo de tracking
        $eventTitle = ($status === 'approved') 
            ? 'Pago Aprobado y Confirmado' 
            : (($status === 'pending') ? 'Método Contra Entrega Seleccionado' : 'Pago Declinado por el Banco');
        
        $methodDesc = $bankName ?: (strtoupper($cardBrand ?? '') . ' •••• ' . ($cardLastFour ?? ''));
        $eventDesc = ($status === 'approved')
            ? "Transacción exitosa por {$methodDesc}. Ref: {$txRef}. Código de autorización: {$authCode}."
            : (($status === 'pending') ? "El repartidor cobrará al entregar el pedido en Neiva mediante {$methodDesc}." : "El banco rechazó la transacción. Por favor intenta con otra tarjeta o banco.");

        $stmtEvent = $db->prepare("
            INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`)
            VALUES (:oid, :status, :title, :desc)
        ");
        $stmtEvent->execute([
            ':oid' => $orderId,
            ':status' => 'recibido',
            ':title' => $eventTitle,
            ':desc' => $eventDesc
        ]);

        // Generar Factura Electrónica (DIAN) si está pagado o contra entrega
        $invoiceNumber = 'FE-ANTOJO-' . date('Y') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
        $cufeSeed = $invoiceNumber . date('YmdHis') . $amount . $order['customer_name'] . 'NIT-901458921-4';
        $cufeCode = hash('sha384', $cufeSeed);

        // Calcular impuesto al consumo 8%
        $taxImpoconsumo = (int)round(($order['subtotal'] - (int)$order['discount']) * 0.08);

        // Guardar o actualizar factura
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
            ON DUPLICATE KEY UPDATE 
                `payment_id` = VALUES(`payment_id`),
                `payment_method_label` = VALUES(`payment_method_label`),
                `transaction_reference` = VALUES(`transaction_reference`)
        ");

        $methodLabel = $bankName ?: (strtoupper($cardBrand ?? 'TARJETA') . ' •••• ' . ($cardLastFour ?? ''));
        $qrString = "NumFac={$invoiceNumber}&FecFac=" . date('Y-m-d') . "&NitFac=901458921-4&DocAdq={$payerDocNumber}&ValFac={$amount}&CUFE=" . substr($cufeCode, 0, 16);

        $stmtInv->execute([
            ':oid' => $orderId,
            ':pid' => $paymentId,
            ':inv_num' => $invoiceNumber,
            ':cufe' => $cufeCode,
            ':cname' => $order['customer_name'],
            ':cdoc' => "{$payerDocType} {$payerDocNumber}",
            ':cemail' => $payerEmail,
            ':cphone' => $order['customer_phone'],
            ':caddr' => $order['delivery_address'] . ' (' . $order['comuna'] . ')',
            ':subtotal' => $order['subtotal'],
            ':discount' => $order['discount'],
            ':tax' => $taxImpoconsumo,
            ':fee' => $order['delivery_fee'],
            ':tip' => $order['tip'],
            ':total' => $amount,
            ':pmethod_label' => $methodLabel,
            ':tx_ref' => $txRef,
            ':qr' => $qrString
        ]);

        $db->commit();

        jsonResponse([
            'success' => ($status === 'approved' || $status === 'pending'),
            'status' => $status,
            'message' => ($status === 'approved') 
                ? '¡Pago aprobado exitosamente! Tu pedido ya está en cocina.' 
                : (($status === 'pending') ? 'Pedido confirmado. Pagas al momento de recibir.' : 'Transacción declinada por la entidad financiera.'),
            'payment' => [
                'id' => $paymentId,
                'transaction_reference' => $txRef,
                'authorization_code' => $authCode,
                'amount' => $amount,
                'amount_formatted' => '$' . number_format($amount, 0, ',', '.'),
                'payment_method' => $paymentMethod,
                'method_label' => $methodLabel,
                'bank_name' => $bankName,
                'card_brand' => $cardBrand,
                'card_last_four' => $cardLastFour,
                'installments' => $installments,
                'status' => $status,
                'paid_at' => $paidAt ?: date('Y-m-d H:i:s')
            ],
            'invoice' => [
                'invoice_number' => $invoiceNumber,
                'cufe_code' => $cufeCode,
                'tax_impoconsumo' => '$' . number_format($taxImpoconsumo, 0, ',', '.'),
                'qr_string' => $qrString
            ],
            'order' => [
                'order_id' => $orderId,
                'order_code' => $order['order_code'],
                'customer_name' => $order['customer_name'],
                'total_formatted' => '$' . number_format($amount, 0, ',', '.')
            ]
        ]);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse([
        'success' => false,
        'message' => 'Error en la pasarela de pagos: ' . $e->getMessage()
    ], 500);
}
