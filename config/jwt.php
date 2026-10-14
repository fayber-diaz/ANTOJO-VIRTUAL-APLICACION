<?php
// =========================================================
// Módulo JWT (JSON Web Tokens) - Versión Completa
// Antojo Virtual - Neiva, Huila
// Soporta: Access Token, Refresh Token, Blacklist
// =========================================================

require_once __DIR__ . '/db.php';

// ── Constantes de configuración ──────────────────────────
define('JWT_SECRET_KEY',         getenv('JWT_SECRET') ?: 'AntojoVirtual_NeivaHuila_Jwt_Secret_Key_2026_$ecur3!');
define('JWT_REFRESH_SECRET',     getenv('JWT_REFRESH_SECRET') ?: 'AntojoVirtual_Refresh_Secret_2026_$up3r$ecure!');
define('JWT_ALGORITHM',          'HS256');
define('JWT_ACCESS_EXPIRY',      60 * 60);          // 1 hora  (access token)
define('JWT_REFRESH_EXPIRY',     60 * 60 * 24 * 30); // 30 días (refresh token)
define('JWT_DEFAULT_EXPIRY',     JWT_ACCESS_EXPIRY);  // alias de compatibilidad

class JWT {

    // ─────────────────────────────────────────────────────
    // CODIFICACIÓN / DECODIFICACIÓN BASE
    // ─────────────────────────────────────────────────────

    public static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // ─────────────────────────────────────────────────────
    // GENERACIÓN DE TOKENS
    // ─────────────────────────────────────────────────────

    /**
     * Genera un token JWT firmado con HMAC-SHA256.
     * @param array  $payload    Datos del usuario
     * @param string $secret     Clave de firma
     * @param int    $expiry     Segundos de validez
     * @return string            Token JWT
     */
    public static function encode(array $payload, string $secret = JWT_SECRET_KEY, int $expiry = JWT_ACCESS_EXPIRY): string {
        $header = ['typ' => 'JWT', 'alg' => JWT_ALGORITHM];

        $now = time();
        $payload['iat'] = $payload['iat'] ?? $now;
        $payload['nbf'] = $payload['nbf'] ?? $now;
        $payload['exp'] = $payload['exp'] ?? ($now + $expiry);
        $payload['jti'] = $payload['jti'] ?? bin2hex(random_bytes(16)); // JWT ID único

        $h = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $s = self::base64UrlEncode(hash_hmac('sha256', "{$h}.{$p}", $secret, true));

        return "{$h}.{$p}.{$s}";
    }

    /**
     * Verifica y decodifica un token JWT.
     * Retorna el payload si es válido, null si es inválido/expirado/blacklisted.
     */
    public static function decode(string $token, string $secret = JWT_SECRET_KEY, bool $checkBlacklist = true): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verificar firma criptográfica (timing-safe)
        $expected = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $actual   = self::base64UrlDecode($signatureEncoded);
        if (!hash_equals($expected, $actual)) return null;

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!is_array($payload)) return null;

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        // Verificar nbf (not before)
        if (isset($payload['nbf']) && $payload['nbf'] > time()) return null;

        // Verificar blacklist (token revocado)
        if ($checkBlacklist && isset($payload['jti']) && self::isBlacklisted($payload['jti'])) {
            return null;
        }

        return $payload;
    }

    // ─────────────────────────────────────────────────────
    // REFRESH TOKENS
    // ─────────────────────────────────────────────────────

    /**
     * Genera y persiste un Refresh Token en la base de datos.
     */
    public static function generateRefreshToken(int $userId, string $userAgent = '', string $ip = ''): string {
        $jti = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);

        $payload = [
            'user_id' => $userId,
            'jti'     => $jti,
            'type'    => 'refresh'
        ];
        $token = self::encode($payload, JWT_REFRESH_SECRET, JWT_REFRESH_EXPIRY);

        try {
            $db = getDB();
            // Limpiar tokens expirados del usuario antes de insertar
            $db->prepare("DELETE FROM `jwt_refresh_tokens` WHERE `user_id` = :uid AND `expires_at` < NOW()")
               ->execute([':uid' => $userId]);

            $db->prepare("
                INSERT INTO `jwt_refresh_tokens` (`user_id`, `token_hash`, `jti`, `expires_at`, `user_agent`, `ip_address`)
                VALUES (:uid, :hash, :jti, :exp, :ua, :ip)
            ")->execute([
                ':uid'  => $userId,
                ':hash' => hash('sha256', $token),
                ':jti'  => $jti,
                ':exp'  => $expiresAt,
                ':ua'   => substr($userAgent, 0, 255),
                ':ip'   => $ip
            ]);
        } catch (Exception $e) {
            // Si la tabla no existe aún, continuar sin persistir
        }

        return $token;
    }

    /**
     * Valida un Refresh Token y retorna el user_id si es válido.
     */
    public static function validateRefreshToken(string $token): ?int {
        $payload = self::decode($token, JWT_REFRESH_SECRET, false);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh' || empty($payload['user_id'])) {
            return null;
        }

        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT `user_id` FROM `jwt_refresh_tokens`
                WHERE `token_hash` = :hash AND `expires_at` > NOW() AND `revoked` = 0
                LIMIT 1
            ");
            $stmt->execute([':hash' => hash('sha256', $token)]);
            $row = $stmt->fetch();
            return $row ? (int)$row['user_id'] : null;
        } catch (Exception $e) {
            // Fallback si la tabla no existe
            return (int)$payload['user_id'];
        }
    }

    /**
     * Revoca todos los refresh tokens de un usuario (logout de todas las sesiones).
     */
    public static function revokeAllRefreshTokens(int $userId): void {
        try {
            $db = getDB();
            $db->prepare("UPDATE `jwt_refresh_tokens` SET `revoked` = 1 WHERE `user_id` = :uid")
               ->execute([':uid' => $userId]);
        } catch (Exception $e) {}
    }

    /**
     * Revoca un refresh token específico.
     */
    public static function revokeRefreshToken(string $token): void {
        try {
            $db = getDB();
            $db->prepare("UPDATE `jwt_refresh_tokens` SET `revoked` = 1 WHERE `token_hash` = :hash")
               ->execute([':hash' => hash('sha256', $token)]);
        } catch (Exception $e) {}
    }

    // ─────────────────────────────────────────────────────
    // BLACKLIST (REVOCACIÓN DE ACCESS TOKENS)
    // ─────────────────────────────────────────────────────

    /**
     * Añade el JTI de un access token a la blacklist (p.ej. en logout).
     */
    public static function blacklist(string $token): void {
        $payload = self::decode($token, JWT_SECRET_KEY, false);
        if (!$payload || empty($payload['jti'])) return;

        try {
            $db = getDB();
            $expiresAt = date('Y-m-d H:i:s', $payload['exp'] ?? (time() + 3600));
            $db->prepare("
                INSERT IGNORE INTO `jwt_blacklist` (`jti`, `expires_at`)
                VALUES (:jti, :exp)
            ")->execute([':jti' => $payload['jti'], ':exp' => $expiresAt]);
        } catch (Exception $e) {}
    }

    /**
     * Comprueba si un JTI está en la blacklist.
     */
    public static function isBlacklisted(string $jti): bool {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT 1 FROM `jwt_blacklist` WHERE `jti` = :jti AND `expires_at` > NOW() LIMIT 1");
            $stmt->execute([':jti' => $jti]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────
    // EXTRACCIÓN DEL TOKEN DE LA PETICIÓN
    // ─────────────────────────────────────────────────────

    /**
     * Extrae el token Bearer del encabezado Authorization, Cookie o parámetro GET.
     */
    public static function getBearerToken(): ?string {
        // 1. Header Authorization: Bearer <token>
        $authHeader = null;
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $val) {
                if (strtolower($key) === 'authorization') {
                    $authHeader = $val;
                    break;
                }
            }
        }
        $authHeader = $authHeader
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
            return $m[1];
        }

        // 2. Cookie HttpOnly
        if (!empty($_COOKIE['antojo_access_token'])) {
            return $_COOKIE['antojo_access_token'];
        }

        // 3. Cookie legacy
        if (!empty($_COOKIE['antojo_jwt_token'])) {
            return $_COOKIE['antojo_jwt_token'];
        }

        // 4. GET (solo para pruebas en desarrollo)
        if (!empty($_GET['auth_token'])) {
            return $_GET['auth_token'];
        }

        return null;
    }

    // ─────────────────────────────────────────────────────
    // AUTENTICACIÓN DE PETICIONES
    // ─────────────────────────────────────────────────────

    /**
     * Retorna el usuario autenticado desde el token JWT + DB.
     */
    public static function getAuthUser(): ?array {
        $token = self::getBearerToken();
        if (!$token) return null;

        $payload = self::decode($token);
        if (!$payload || empty($payload['user_id'])) return null;

        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT `id`, `name`, `email`, `phone`, `address`, `comuna`, `role`, `created_at` FROM `users` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $payload['user_id']]);
            $user = $stmt->fetch();
            if (!$user) return null;
            $user['id'] = (int)$user['id'];
            return $user;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Middleware: exige autenticación y opcionalmente un rol.
     * Termina la ejecución con HTTP 401/403 si no cumple.
     */
    public static function requireAuth(array $allowedRoles = []): array {
        $user = self::getAuthUser();

        if (!$user) {
            jsonResponse([
                'success' => false,
                'error'   => 'No autorizado. Se requiere un token JWT válido.',
                'code'    => 'UNAUTHORIZED'
            ], 401);
        }

        if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
            jsonResponse([
                'success'        => false,
                'error'          => 'Acceso denegado. Permisos insuficientes.',
                'code'           => 'FORBIDDEN',
                'user_role'      => $user['role'],
                'required_roles' => $allowedRoles
            ], 403);
        }

        return $user;
    }

    // ─────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────

    /**
     * Construye el payload estándar para un usuario.
     */
    public static function buildPayload(array $user): array {
        return [
            'user_id' => (int)$user['id'],
            'email'   => $user['email'],
            'name'    => $user['name'],
            'role'    => $user['role']
        ];
    }

    /**
     * Genera par Access + Refresh token para un usuario.
     */
    public static function issueTokenPair(array $user, string $ua = '', string $ip = ''): array {
        $payload      = self::buildPayload($user);
        $accessToken  = self::encode($payload);
        $refreshToken = self::generateRefreshToken((int)$user['id'], $ua, $ip);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => JWT_ACCESS_EXPIRY,
        ];
    }

    /**
     * Establece las cookies HttpOnly de los tokens.
     */
    public static function setCookies(string $accessToken, string $refreshToken): void {
        $secure   = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $sameSite = $secure ? 'Strict' : 'Lax';

        setcookie('antojo_access_token', $accessToken, [
            'expires'  => time() + JWT_ACCESS_EXPIRY,
            'path'     => '/',
            'httponly' => true,
            'samesite' => $sameSite,
            'secure'   => $secure
        ]);

        setcookie('antojo_refresh_token', $refreshToken, [
            'expires'  => time() + JWT_REFRESH_EXPIRY,
            'path'     => '/api/auth.php',  // Scope restringido al endpoint de refresh
            'httponly' => true,
            'samesite' => $sameSite,
            'secure'   => $secure
        ]);

        // Cookie legacy para compatibilidad con código anterior
        setcookie('antojo_jwt_token', $accessToken, [
            'expires'  => time() + JWT_ACCESS_EXPIRY,
            'path'     => '/',
            'httponly' => false,
            'samesite' => $sameSite
        ]);
    }

    /**
     * Borra todas las cookies de autenticación.
     */
    public static function clearCookies(): void {
        foreach (['antojo_access_token', 'antojo_refresh_token', 'antojo_jwt_token'] as $name) {
            setcookie($name, '', ['expires' => time() - 3600, 'path' => '/']);
        }
        setcookie('antojo_refresh_token', '', ['expires' => time() - 3600, 'path' => '/api/auth.php']);
    }
}
