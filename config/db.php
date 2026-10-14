<?php
// =========================================================
// Configuración de Conexión a Base de Datos MySQL
// Antojo Virtual - Neiva, Huila
// =========================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'antojo_virtual');

/**
 * Obtiene la conexión PDO a MySQL.
 * Si la base de datos no existe, intenta crearla automáticamente.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsnWithDb = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Si la base de datos no existe (código 1049), intentar crearla y ejecutar schema
        if ($e->getCode() == 1049) {
            try {
                $dsnWithoutDb = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
                $tempPdo = new PDO($dsnWithoutDb, DB_USER, DB_PASS, $options);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                $schemaFile = __DIR__ . '/../database/antojo_virtual.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $tempPdo->exec($sql);
                }
                
                $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
            } catch (PDOException $ex) {
                die("Error de inicialización de Base de Datos: " . $ex->getMessage());
            }
        } else {
            die("Error conectando a MySQL en XAMPP: " . $e->getMessage() . ". Asegúrate de que el servicio MySQL esté iniciado en el panel de XAMPP.");
        }
    }

    return $pdo;
}

/**
 * Retorna una respuesta JSON estandarizada y finaliza la ejecución.
 */
function jsonResponse($data, int $statusCode = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Obtiene el cuerpo de una solicitud JSON (php://input).
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}
