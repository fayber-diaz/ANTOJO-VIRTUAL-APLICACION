<?php
/**
 * =========================================================
 * ANTOJO VIRTUAL — API de Calificaciones y Reseñas
 * Compatible con MySQL / MariaDB en XAMPP
 * Neiva, Huila
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

    if ($method === 'GET') {
        $restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
        $limit = isset($_GET['limit']) ? min(50, (int)$_GET['limit']) : 10;

        $stmt = $db->prepare("
            SELECT r.*, o.order_code 
            FROM `reviews` r
            LEFT JOIN `orders` o ON r.order_id = o.id
            WHERE r.restaurant_id = :rid AND r.is_approved = 1
            ORDER BY r.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':rid', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll();

        // Promedio general
        $stmtAvg = $db->prepare("SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM `reviews` WHERE restaurant_id = :rid AND is_approved = 1");
        $stmtAvg->execute([':rid' => $restaurantId]);
        $stats = $stmtAvg->fetch();

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_reviews' => (int)$stats['count'],
                'average_rating' => round((float)$stats['avg_rating'], 1)
            ],
            'reviews' => $reviews
        ]);
    }

    if ($method === 'POST') {
        $input = getJsonInput();

        $orderCode = trim($input['order_code'] ?? '');
        $restaurantId = isset($input['restaurant_id']) ? (int)$input['restaurant_id'] : 1;
        $customerName = trim($input['customer_name'] ?? 'Cliente Anónimo');
        $rating = isset($input['rating']) ? (float)$input['rating'] : 5.0;
        $riderRating = isset($input['rider_rating']) ? (float)$input['rider_rating'] : 5.0;
        $comment = trim($input['comment'] ?? '');
        $tags = isset($input['tags']) && is_array($input['tags']) ? implode(', ', $input['tags']) : '';

        if (!empty($tags)) {
            $comment = "[Destacados: {$tags}] " . $comment;
        }

        // Buscar order_id si se pasó order_code
        $orderId = null;
        if (!empty($orderCode)) {
            $stmtOrder = $db->prepare("SELECT id, restaurant_id, rider_id FROM `orders` WHERE order_code = :code LIMIT 1");
            $stmtOrder->execute([':code' => $orderCode]);
            $foundOrder = $stmtOrder->fetch();
            if ($foundOrder) {
                $orderId = $foundOrder['id'];
                if ($foundOrder['restaurant_id']) {
                    $restaurantId = $foundOrder['restaurant_id'];
                }
                // Si el repartidor tiene rating, actualizar su promedio en riders
                if ($foundOrder['rider_id'] && $riderRating > 0) {
                    $stmtRider = $db->prepare("
                        UPDATE `riders` 
                        SET rating = ROUND((rating * 4 + :rr) / 5, 1) 
                        WHERE id = :rid
                    ");
                    $stmtRider->execute([':rr' => $riderRating, ':rid' => $foundOrder['rider_id']]);
                }
            }
        }

        $stmt = $db->prepare("
            INSERT INTO `reviews` (`order_id`, `restaurant_id`, `customer_name`, `rating`, `comment`, `is_approved`, `created_at`)
            VALUES (:oid, :rid, :name, :rating, :comment, 1, NOW())
        ");
        $stmt->execute([
            ':oid' => $orderId,
            ':rid' => $restaurantId,
            ':name' => $customerName,
            ':rating' => max(1.0, min(5.0, $rating)),
            ':comment' => !empty($comment) ? $comment : '¡Excelente servicio y comida deliciosa de Neiva!'
        ]);

        // Actualizar promedio del restaurante
        $stmtUpdateRest = $db->prepare("
            UPDATE `restaurants` 
            SET rating = (SELECT ROUND(AVG(rating), 1) FROM `reviews` WHERE restaurant_id = :rid AND is_approved = 1),
                reviews_count = (SELECT COUNT(*) FROM `reviews` WHERE restaurant_id = :rid2 AND is_approved = 1)
            WHERE id = :rid3
        ");
        $stmtUpdateRest->execute([':rid' => $restaurantId, ':rid2' => $restaurantId, ':rid3' => $restaurantId]);

        jsonResponse([
            'success' => true,
            'message' => '¡Muchas gracias por tu calificación! Tu opinión ayuda a mejorar la gastronomía en Neiva.',
            'review_id' => $db->lastInsertId()
        ]);
    }

} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
