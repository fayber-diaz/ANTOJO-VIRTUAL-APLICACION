<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    if ($method === 'POST') {
        $input = getJsonInput();
        $action = $input['action'] ?? 'update_status';
        $orderId = (int)($input['order_id'] ?? 0);
        $orderCode = trim($input['order_code'] ?? '');

        if (!$orderId && !empty($orderCode)) {
            $stmtFind = $db->prepare("SELECT id FROM `orders` WHERE `order_code` = :code LIMIT 1");
            $stmtFind->execute([':code' => $orderCode]);
            $found = $stmtFind->fetch();
            if ($found) {
                $orderId = (int)$found['id'];
            }
        }

        if (!$orderId) {
            jsonResponse(['success' => false, 'message' => 'Se requiere ID o código de la orden.'], 400);
        }

        // Consultar orden actual
        $stmtCurr = $db->prepare("SELECT * FROM `orders` WHERE `id` = :id LIMIT 1");
        $stmtCurr->execute([':id' => $orderId]);
        $currentOrder = $stmtCurr->fetch();

        if (!$currentOrder) {
            jsonResponse(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }

        if ($action === 'auto_advance') {
            $currStatus = $currentOrder['status'];
            $nextStatus = match($currStatus) {
                'recibido' => 'preparando',
                'preparando' => 'en_camino',
                'en_camino' => 'entregado',
                'entregado' => 'recibido',
                default => 'recibido'
            };
            $targetStatus = $nextStatus;
        } else {
            $targetStatus = $input['status'] ?? 'preparando';
        }

        $validStatuses = ['recibido', 'preparando', 'en_camino', 'entregado', 'cancelado'];
        if (!in_array($targetStatus, $validStatuses)) {
            jsonResponse(['success' => false, 'message' => 'Estado no válido.'], 400);
        }

        // Títulos y descripciones para el timeline según el estado
        $eventInfo = match($targetStatus) {
            'recibido' => [
                'title' => 'Orden Recibida y Confirmada',
                'desc' => 'Pedido en cola de espera. Restaurante verificando ingredientes.'
            ],
            'preparando' => [
                'title' => 'Carbón al Rojo Vivo & Cocción',
                'desc' => 'El maestro parrillero está asando tu orden con sazón huilense artesanal.'
            ],
            'en_camino' => [
                'title' => 'Repartidor en Ruta por Neiva',
                'desc' => 'Tu pedido va en caliente empacado en termo térmico por la Av. Circunvalar.'
            ],
            'entregado' => [
                'title' => '¡Pedido Entregado con Éxito!',
                'desc' => 'Repartidor entregó la orden en puerta. ¡Buen provecho en Neiva!'
            ],
            'cancelado' => [
                'title' => 'Orden Cancelada',
                'desc' => 'El pedido fue cancelado a solicitud del cliente o restaurante.'
            ]
        };

        // Actualizar estado en la tabla orders
        $stmtUp = $db->prepare("UPDATE `orders` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id");
        $stmtUp->execute([':status' => $targetStatus, ':id' => $orderId]);

        // Registrar evento en el tracking timeline
        $stmtEvt = $db->prepare("
            INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`)
            VALUES (:oid, :status, :title, :desc)
        ");
        $stmtEvt->execute([
            ':oid' => $orderId,
            ':status' => $targetStatus,
            ':title' => $eventInfo['title'],
            ':desc' => $eventInfo['desc']
        ]);

        jsonResponse([
            'success' => true,
            'message' => "Estado actualizado exitosamente a: {$targetStatus}",
            'order_id' => $orderId,
            'previous_status' => $currentOrder['status'],
            'new_status' => $targetStatus,
            'new_status_title' => $eventInfo['title']
        ]);

    } elseif ($method === 'GET') {
        // Métricas de ventas y pedidos para panel de control
        $stats = [];
        
        $stats['total_orders'] = (int)$db->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
        $stats['active_orders'] = (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` IN ('recibido', 'preparando', 'en_camino')")->fetchColumn();
        $stats['delivered_orders'] = (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'entregado'")->fetchColumn();
        $stats['total_revenue'] = (int)$db->query("SELECT COALESCE(SUM(total), 0) FROM `orders` WHERE `status` != 'cancelado'")->fetchColumn();
        $stats['total_revenue_formatted'] = '$' . number_format($stats['total_revenue'], 0, ',', '.');

        // Órdenes por estado
        $ordersByStatus = [
            'recibido' => (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'recibido'")->fetchColumn(),
            'preparando' => (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'preparando'")->fetchColumn(),
            'en_camino' => (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'en_camino'")->fetchColumn(),
            'entregado' => (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'entregado'")->fetchColumn()
        ];

        // Lista de todas las órdenes recientes
        $stmtList = $db->query("
            SELECT o.*, 
                   COUNT(i.id) as items_count,
                   r.name as rider_name,
                   r.vehicle as rider_vehicle
            FROM `orders` o
            LEFT JOIN `order_items` i ON o.id = i.order_id
            LEFT JOIN `riders` r ON o.rider_id = r.id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
        $orders = $stmtList->fetchAll();

        foreach ($orders as &$ord) {
            $ord['total_formatted'] = '$' . number_format($ord['total'], 0, ',', '.');
        }

        jsonResponse([
            'success' => true,
            'metrics' => $stats,
            'counts' => $ordersByStatus,
            'orders' => $orders
        ]);
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error administrativo: ' . $e->getMessage()
    ], 500);
}
