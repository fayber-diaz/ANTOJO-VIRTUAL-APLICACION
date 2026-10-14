<?php
// =========================================================
// API de Autenticación JWT — Antojo Virtual, Neiva, Huila
// Endpoints:
//   POST ?action=login     — Iniciar sesión
//   POST ?action=register  — Crear cuenta
//   POST ?action=refresh   — Renovar access token con refresh token
//   POST ?action=logout    — Cerrar sesión (revoca tokens)
//   GET  ?action=me        — Perfil del usuario autenticado
//   GET  ?action=verify    — Verificar validez del token actual
// =========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Expose-Headers: X-Token-Expires-In');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$action = $_GET['action'] ?? ($_POST['action'] ?? 'me');
$method = $_SERVER['REQUEST_METHOD'];
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

try {
    $db = getDB();

    // =========================================================
    // POST /api/auth.php?action=login
    // =========================================================
    if ($action === 'login' && $method === 'POST') {
        $input    = getJsonInput();
        $email    = trim(strtolower($input['email']    ?? ''));
        $password = (string)($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            jsonResponse(['success' => false, 'message' => 'Por favor ingresa tu correo y contraseña.'], 400);
        }

        $stmt = $db->prepare("SELECT * FROM `users` WHERE `email` = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            // Delay de seguridad anti-timing attacks
            usleep(random_int(100000, 300000));
            jsonResponse(['success' => false, 'message' => 'Credenciales inválidas. Revisa tu correo y contraseña.'], 401);
        }

        // Actualizar hash si el algoritmo cambió (re-hashing automático)
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE `users` SET `password` = :p WHERE `id` = :id")
               ->execute([':p' => $newHash, ':id' => $user['id']]);
        }

        $tokens = JWT::issueTokenPair($user, $ua, $ip);
        JWT::setCookies($tokens['access_token'], $tokens['refresh_token']);

        unset($user['password']);
        $user['id'] = (int)$user['id'];
        header('X-Token-Expires-In: ' . JWT_ACCESS_EXPIRY);

        jsonResponse([
            'success'       => true,
            'message'       => "¡Bienvenido de nuevo, {$user['name']}!",
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type'    => 'Bearer',
            'expires_in'    => JWT_ACCESS_EXPIRY,
            'user'          => $user
        ]);
    }

    // =========================================================
    // POST /api/auth.php?action=register
    // =========================================================
    if ($action === 'register' && $method === 'POST') {
        $input    = getJsonInput();
        $name     = trim($input['name']     ?? '');
        $email    = trim(strtolower($input['email']    ?? ''));
        $password = (string)($input['password'] ?? '');
        $phone    = trim($input['phone']    ?? '');
        $address  = trim($input['address']  ?? 'Neiva, Huila');
        $comuna   = trim($input['comuna']   ?? 'Comuna 4 - Centro');

        // Validaciones
        if (empty($name) || empty($email) || empty($password)) {
            jsonResponse(['success' => false, 'message' => 'Nombre, correo y contraseña son obligatorios.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'El correo electrónico no es válido.'], 400);
        }
        if (strlen($password) < 8) {
            jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'], 400);
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos una mayúscula y un número.'], 400);
        }

        // Verificar email duplicado
        $stmtCheck = $db->prepare("SELECT id FROM `users` WHERE `email` = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Ya existe una cuenta con este correo.'], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("
            INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `comuna`, `role`)
            VALUES (:name, :email, :pass, :phone, :addr, :comuna, 'customer')
        ")->execute([
            ':name'   => $name,
            ':email'  => $email,
            ':pass'   => $hash,
            ':phone'  => $phone ?: null,
            ':addr'   => $address,
            ':comuna' => $comuna
        ]);

        $newId = (int)$db->lastInsertId();
        $newUser = [
            'id'      => $newId,
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'address' => $address,
            'comuna'  => $comuna,
            'role'    => 'customer'
        ];

        $tokens = JWT::issueTokenPair($newUser, $ua, $ip);
        JWT::setCookies($tokens['access_token'], $tokens['refresh_token']);

        jsonResponse([
            'success'       => true,
            'message'       => '¡Cuenta creada exitosamente en Antojo Virtual!',
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type'    => 'Bearer',
            'expires_in'    => JWT_ACCESS_EXPIRY,
            'user'          => $newUser
        ], 201);
    }

    // =========================================================
    // POST /api/auth.php?action=refresh
    // Recibe: { "refresh_token": "..." } o Cookie antojo_refresh_token
    // =========================================================
    if ($action === 'refresh' && $method === 'POST') {
        $input        = getJsonInput();
        $refreshToken = $input['refresh_token']
            ?? $_COOKIE['antojo_refresh_token']
            ?? null;

        if (empty($refreshToken)) {
            jsonResponse(['success' => false, 'message' => 'Refresh token no proporcionado.', 'code' => 'MISSING_REFRESH_TOKEN'], 400);
        }

        $userId = JWT::validateRefreshToken($refreshToken);
        if (!$userId) {
            JWT::clearCookies();
            jsonResponse(['success' => false, 'message' => 'Refresh token inválido o expirado. Inicia sesión nuevamente.', 'code' => 'INVALID_REFRESH_TOKEN'], 401);
        }

        // Cargar usuario actual de la BD
        $stmt = $db->prepare("SELECT `id`, `name`, `email`, `phone`, `address`, `comuna`, `role` FROM `users` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.'], 401);
        }

        // Rotación del refresh token (revocar el viejo, emitir uno nuevo)
        JWT::revokeRefreshToken($refreshToken);
        $tokens = JWT::issueTokenPair($user, $ua, $ip);
        JWT::setCookies($tokens['access_token'], $tokens['refresh_token']);

        header('X-Token-Expires-In: ' . JWT_ACCESS_EXPIRY);

        jsonResponse([
            'success'       => true,
            'message'       => 'Token renovado exitosamente.',
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type'    => 'Bearer',
            'expires_in'    => JWT_ACCESS_EXPIRY
        ]);
    }

    // =========================================================
    // POST/DELETE /api/auth.php?action=logout
    // =========================================================
    if ($action === 'logout') {
        $input        = getJsonInput();
        $refreshToken = $input['refresh_token']
            ?? $_COOKIE['antojo_refresh_token']
            ?? null;

        // Revocar access token actual en la blacklist
        $accessToken = JWT::getBearerToken();
        if ($accessToken) JWT::blacklist($accessToken);

        // Revocar refresh token
        if ($refreshToken) {
            JWT::revokeRefreshToken($refreshToken);
        }

        // Obtener user_id para revocar TODAS las sesiones si se pidió
        $payload = $accessToken ? JWT::decode($accessToken, JWT_SECRET_KEY, false) : null;
        if (!empty($input['all_devices']) && $payload) {
            JWT::revokeAllRefreshTokens((int)$payload['user_id']);
        }

        JWT::clearCookies();

        jsonResponse(['success' => true, 'message' => 'Sesión cerrada exitosamente. ¡Hasta pronto!']);
    }

    // =========================================================
    // GET /api/auth.php?action=me
    // =========================================================
    if ($action === 'me') {
        $user = JWT::requireAuth();

        // Historial de pedidos
        $stmtOrders = $db->prepare("
            SELECT o.id, o.order_code, o.total, o.status, o.created_at,
                   COUNT(i.id) as items_count
            FROM   `orders` o
            LEFT JOIN `order_items` i ON o.id = i.order_id
            WHERE  o.user_id = :uid
            GROUP  BY o.id
            ORDER  BY o.created_at DESC
            LIMIT  10
        ");
        $stmtOrders->execute([':uid' => $user['id']]);
        $myOrders = $stmtOrders->fetchAll();

        foreach ($myOrders as &$ord) {
            $ord['total_formatted'] = '$' . number_format($ord['total'], 0, ',', '.');
        }

        jsonResponse([
            'success' => true,
            'user'    => $user,
            'orders'  => $myOrders
        ]);
    }

    // =========================================================
    // GET /api/auth.php?action=verify
    // =========================================================
    if ($action === 'verify') {
        $token = JWT::getBearerToken();
        if (!$token) {
            jsonResponse(['success' => false, 'authenticated' => false, 'code' => 'NO_TOKEN'], 200);
        }

        $payload = JWT::decode($token);
        if (!$payload) {
            jsonResponse(['success' => false, 'authenticated' => false, 'code' => 'INVALID_TOKEN'], 200);
        }

        jsonResponse([
            'success'       => true,
            'authenticated' => true,
            'user_id'       => $payload['user_id'],
            'role'          => $payload['role'],
            'expires_at'    => $payload['exp'],
            'expires_in'    => max(0, $payload['exp'] - time())
        ]);
    }

    jsonResponse(['success' => false, 'message' => "Acción '{$action}' no reconocida."], 400);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error de autenticación: ' . $e->getMessage()
    ], 500);
}
