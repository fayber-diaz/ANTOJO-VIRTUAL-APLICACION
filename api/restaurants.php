<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();

    $category = $_GET['category'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $comuna = trim($_GET['comuna'] ?? '');

    $query = "SELECT * FROM `restaurants` WHERE `is_open` = 1";
    $params = [];

    if ($category === 'comidas_rapidas' || $category === 'salados') {
        $query .= " AND `site_type` = 'comidas_rapidas'";
    } elseif ($category === 'dulces' || $category === 'postres_all') {
        $query .= " AND `site_type` = 'dulces'";
    } elseif ($category !== 'all' && !empty($category)) {
        $query .= " AND `category` = :category";
        $params[':category'] = $category;
    }

    if (!empty($comuna) && $comuna !== 'all') {
        $query .= " AND `comuna` LIKE :comuna";
        $params[':comuna'] = '%' . $comuna . '%';
    }

    if (!empty($search)) {
        $query .= " AND (`name` LIKE :search OR `description` LIKE :search OR `star_dish` LIKE :search OR `address` LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY `rating` DESC, `reviews_count` DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $restaurants = $stmt->fetchAll();

    // Formatear precios y campos numéricos
    foreach ($restaurants as &$r) {
        $r['id'] = (int)$r['id'];
        $r['rating'] = (float)$r['rating'];
        $r['reviews_count'] = (int)$r['reviews_count'];
        $r['delivery_fee'] = (int)$r['delivery_fee'];
        $r['star_price'] = (int)$r['star_price'];
        $r['delivery_fee_formatted'] = '$' . number_format($r['delivery_fee'], 0, ',', '.');
        $r['star_price_formatted'] = '$' . number_format($r['star_price'], 0, ',', '.');
    }

    jsonResponse([
        'success' => true,
        'count' => count($restaurants),
        'data' => $restaurants
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
