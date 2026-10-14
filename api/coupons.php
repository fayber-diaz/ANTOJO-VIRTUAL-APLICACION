<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = getDB();
    $input = getJsonInput();

    $code = trim($input['code'] ?? ($_GET['code'] ?? ''));
    $subtotal = (int)($input['subtotal'] ?? ($_GET['subtotal'] ?? 0));

    if (empty($code)) {
        jsonResponse([
            'success' => false,
            'message' => 'Por favor ingresa un código de cupón.'
        ], 400);
    }

    $stmt = $db->prepare("SELECT * FROM `coupons` WHERE `code` = :code LIMIT 1");
    $stmt->execute([':code' => strtoupper($code)]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        jsonResponse([
            'success' => false,
            'message' => 'Código no válido en Neiva. Prueba con ANTOJONEIVA5K'
        ], 404);
    }

    if (!$coupon['is_active']) {
        jsonResponse([
            'success' => false,
            'message' => 'Este cupón se encuentra inactivo actualmente.'
        ], 400);
    }

    if ($coupon['used_count'] >= $coupon['max_uses']) {
        jsonResponse([
            'success' => false,
            'message' => 'Este cupón ha alcanzado el límite máximo de usos.'
        ], 400);
    }

    if ($subtotal > 0 && $subtotal < $coupon['min_purchase']) {
        $minFormatted = '$' . number_format($coupon['min_purchase'], 0, ',', '.');
        jsonResponse([
            'success' => false,
            'message' => "El pedido mínimo para aplicar este cupón es de {$minFormatted} COP."
        ], 400);
    }

    jsonResponse([
        'success' => true,
        'code' => $coupon['code'],
        'discount_amount' => (int)$coupon['discount_amount'],
        'discount_formatted' => '$' . number_format($coupon['discount_amount'], 0, ',', '.'),
        'description' => $coupon['description'],
        'message' => "¡Cupón {$coupon['code']} aplicado con éxito! (-$" . number_format($coupon['discount_amount'], 0, ',', '.') . " COP)"
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al validar cupón: ' . $e->getMessage()
    ], 500);
}
