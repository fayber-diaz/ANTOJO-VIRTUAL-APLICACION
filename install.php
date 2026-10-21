<?php
require_once __DIR__ . '/config/db.php';

$message = null;
$messageType = 'info';

// Procesar reinstalación si se solicita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_database') {
    try {
        $db = getDB();
        $schemaFile = __DIR__ . '/database/antojo_virtual.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $db->exec($sql);
            $message = '¡Base de datos "antojo_virtual" reiniciada y poblada exitosamente con datos frescos de Neiva!';
            $messageType = 'success';
        } else {
            $message = 'No se encontró el archivo database/antojo_virtual.sql';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error al ejecutar la instalación: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Comprobar estado de conexión y tablas
$dbStatus = false;
$tablesInfo = [];
$totalRestaurants = 0;
$totalDishes = 0;
$totalOrders = 0;

try {
    $db = getDB();
    $dbStatus = true;
    
    $tables = [
        'restaurants', 'menu_items', 'item_options', 'categories', 'coupons', 
        'riders', 'orders', 'order_items', 'order_tracking_events',
        'banks', 'payment_methods', 'payments', 'invoices',
        'users', 'user_addresses', 'user_saved_cards', 'jwt_refresh_tokens', 'jwt_blacklist',
        'reviews', 'comunas'
    ];
    foreach ($tables as $t) {
        try {
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            $tablesInfo[$t] = $count;
        } catch (Exception $ex) {
            $tablesInfo[$t] = 'No existe';
        }
    }

    $totalRestaurants = $tablesInfo['restaurants'] ?? 0;
    $totalDishes = $tablesInfo['menu_items'] ?? 0;
    $totalOrders = $tablesInfo['orders'] ?? 0;
    $totalPayments = $tablesInfo['payments'] ?? 0;
    $totalBanks = $tablesInfo['banks'] ?? 0;

} catch (Exception $e) {
    $dbStatus = false;
    $message = 'Error conectando a MySQL: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instalador & Diagnóstico (Neumorfismo) | ANTOJO VIRTUAL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: var(--neu-bg);
      color: var(--color-on-surface);
      padding: 2.5rem 1rem;
    }
    .container {
      max-width: 880px;
      margin: 0 auto;
    }
    .card {
      background: var(--neu-bg);
      border-radius: var(--radius-lg);
      border: 1px solid var(--color-border-subtle);
      box-shadow: var(--neu-extruded-lg);
      padding: 2.25rem;
      margin-bottom: 2rem;
    }
    .header {
      display: flex;
      align-items: center;
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .logo-badge {
      width: 60px;
      height: 60px;
      border-radius: var(--radius-md);
      background: var(--color-primary-neu);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2rem;
      box-shadow: var(--color-primary-neu-shadow);
    }
    h1 {
      font-family: var(--font-epilogue);
      font-size: 1.85rem;
      font-weight: 900;
      color: var(--color-on-surface);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.45rem 1rem;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 800;
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid var(--color-border-subtle);
    }
    .badge-success { background: var(--neu-bg); color: #00682e; }
    .badge-error { background: var(--neu-bg); color: #dc2626; }
    .status-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 1.25rem;
      margin: 2rem 0;
    }
    .stat-card {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid var(--color-border-subtle);
      padding: 1.5rem;
      border-radius: var(--radius-md);
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }
    .stat-val {
      font-family: var(--font-epilogue);
      font-size: 2rem;
      font-weight: 900;
      color: #dc2626;
    }
    .stat-lbl {
      font-size: 0.85rem;
      color: var(--color-on-surface-variant);
      font-weight: 700;
    }
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 0.65rem;
      margin-top: 1.25rem;
    }
    th, td {
      padding: 0.85rem 1.15rem;
      text-align: left;
      font-size: 0.9rem;
    }
    th {
      font-weight: 800;
      color: var(--color-on-surface-variant);
      background: transparent;
      text-transform: uppercase;
      font-size: 0.8rem;
    }
    td {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded-sm);
      border-top: 1px solid var(--color-border-subtle);
      border-bottom: 1px solid var(--color-border-subtle);
    }
    td:first-child {
      border-top-left-radius: var(--radius-sm);
      border-bottom-left-radius: var(--radius-sm);
      border-left: 1px solid var(--color-border-subtle);
    }
    td:last-child {
      border-top-right-radius: var(--radius-sm);
      border-bottom-right-radius: var(--radius-sm);
      border-right: 1px solid var(--color-border-subtle);
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--color-primary-neu);
      color: white;
      text-decoration: none;
      padding: 0.9rem 1.65rem;
      border-radius: var(--radius-full);
      font-weight: 800;
      border: none;
      cursor: pointer;
      font-size: 0.95rem;
      box-shadow: var(--color-primary-neu-shadow);
      transition: all 0.2s ease;
    }
    .btn:hover {
      transform: translateY(-2px);
    }
    .btn:active {
      box-shadow: var(--color-primary-neu-inset);
    }
    .btn-secondary {
      background: var(--neu-bg);
      color: var(--color-on-surface);
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid var(--color-border-subtle);
    }
    .btn-secondary:hover {
      box-shadow: var(--neu-extruded);
      color: #dc2626;
    }
    .btn-secondary:active {
      box-shadow: var(--neu-inset-sm);
    }
    .alert {
      padding: 1.15rem 1.5rem;
      border-radius: var(--radius-md);
      margin-bottom: 1.75rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.85rem;
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid var(--color-border-subtle);
    }
    .alert-success { background: var(--neu-bg); color: #00682e; }
    .alert-error { background: var(--neu-bg); color: #93000a; }
    .actions-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 2rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="header">
        <div class="logo-badge">
          <span class="material-symbols-outlined" style="font-size:32px;">lunch_dining</span>
        </div>
        <div>
          <h1>ANTOJO VIRTUAL • XAMPP & MySQL</h1>
          <p style="color: var(--color-on-surface-variant); font-size: 0.95rem; font-weight: 600;">Panel de Diagnóstico & Configuración en Neiva (Neumorfismo)</p>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
          <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
          <span><?php echo htmlspecialchars($message); ?></span>
        </div>
      <?php endif; ?>

      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; padding-bottom:1.25rem; border-bottom:1px solid var(--color-border-subtle);">
        <div>
          <strong>Estado de Conexión MySQL:</strong>
          <?php if ($dbStatus): ?>
            <span class="badge badge-success"><span class="material-symbols-outlined" style="font-size:18px;">check_circle</span> Conectado a MySQL (localhost:3306)</span>
          <?php else: ?>
            <span class="badge badge-error"><span class="material-symbols-outlined" style="font-size:18px;">cancel</span> Desconectado</span>
          <?php endif; ?>
        </div>
        <div>
          <span style="font-size:0.85rem; color:var(--color-on-surface-variant); font-weight:700;">Base de datos: <strong>antojo_virtual</strong> (MariaDB / PHP 8.2)</span>
        </div>
      </div>

      <div class="status-grid">
        <div class="stat-card">
          <span class="stat-val"><?php echo $totalRestaurants; ?></span>
          <span class="stat-lbl">Restaurantes Aliados en Neiva</span>
        </div>
        <div class="stat-card">
          <span class="stat-val"><?php echo $totalDishes; ?></span>
          <span class="stat-lbl">Platos y Especialidades</span>
        </div>
        <div class="stat-card">
          <span class="stat-val"><?php echo $totalOrders; ?></span>
          <span class="stat-lbl">Pedidos Registrados en MySQL</span>
        </div>
      </div>

      <h3 style="font-family:var(--font-epilogue); margin-top:1.5rem; margin-bottom:0.5rem; font-size:1.25rem; font-weight:800;">
        Tablas del Sistema en MySQL
      </h3>
      <table>
        <thead>
          <tr>
            <th>Nombre de la Tabla</th>
            <th>Registros Actuales</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tablesInfo as $tableName => $cnt): ?>
            <tr>
              <td><code><?php echo htmlspecialchars($tableName); ?></code></td>
              <td><strong><?php echo is_numeric($cnt) ? $cnt : 0; ?></strong> filas</td>
              <td>
                <?php if (is_numeric($cnt)): ?>
                  <span style="color: #00682e; font-weight:800;">✓ Correcta</span>
                <?php else: ?>
                  <span style="color: #dc2626; font-weight:800;">✗ Falta</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="actions-row">
        <a href="index.php" class="btn">
          <span class="material-symbols-outlined">storefront</span>
          Abrir Antojo Virtual
        </a>
        <a href="admin.php" class="btn btn-secondary">
          <span class="material-symbols-outlined">kitchen</span>
          Panel de Cocina / Admin
        </a>
        <a href="/phpmyadmin/index.php?route=/database/structure&db=antojo_virtual" target="_blank" class="btn btn-secondary">
          <span class="material-symbols-outlined">database</span>
          Abrir en phpMyAdmin
        </a>
        <form method="POST" onsubmit="return confirm('¿Seguro que deseas reiniciar la base de datos con los datos iniciales de Neiva? Se reiniciarán órdenes y menús.');" style="margin-left:auto;">
          <input type="hidden" name="action" value="reset_database">
          <button type="submit" class="btn" style="background: linear-gradient(145deg, #b70011, #85000c);">
            <span class="material-symbols-outlined">restart_alt</span>
            Reiniciar Base de Datos
          </button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
