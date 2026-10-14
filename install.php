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
    
    $tables = ['restaurants', 'menu_items', 'item_options', 'categories', 'coupons', 'riders', 'orders', 'order_items', 'order_tracking_events'];
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
  <title>Instalador & Diagnóstico | ANTOJO VIRTUAL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    :root {
      --primary: #dc2626;
      --primary-dark: #b70011;
      --secondary: #fea619;
      --success: #1a8340;
      --surface: #f9f9ff;
      --text: #111c2d;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--surface);
      color: var(--text);
      line-height: 1.5;
      padding: 2rem 1rem;
    }
    .container {
      max-width: 860px;
      margin: 0 auto;
    }
    .card {
      background: #ffffff;
      border-radius: 1.25rem;
      border: 1px solid #e7eeff;
      box-shadow: 0 10px 30px rgba(17, 28, 45, 0.05);
      padding: 2rem;
      margin-bottom: 1.5rem;
    }
    .header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .logo-badge {
      width: 56px;
      height: 56px;
      border-radius: 1rem;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2rem;
      box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
    }
    h1 {
      font-family: 'Epilogue', sans-serif;
      font-size: 1.85rem;
      font-weight: 800;
      color: #111c2d;
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.35rem 0.85rem;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 700;
    }
    .badge-success { background: #e7ffe5; color: var(--success); }
    .badge-error { background: #ffdad6; color: var(--primary-dark); }
    .status-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
      margin: 1.5rem 0;
    }
    .stat-card {
      background: #f0f3ff;
      padding: 1.25rem;
      border-radius: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    .stat-val {
      font-family: 'Epilogue', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--primary-dark);
    }
    .stat-lbl {
      font-size: 0.85rem;
      color: #5c403c;
      font-weight: 600;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th, td {
      padding: 0.75rem 1rem;
      text-align: left;
      border-bottom: 1px solid #e7eeff;
      font-size: 0.9rem;
    }
    th {
      font-weight: 700;
      color: #5c403c;
      background: #f9f9ff;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--primary);
      color: white;
      text-decoration: none;
      padding: 0.85rem 1.5rem;
      border-radius: 0.85rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
    }
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
    .btn-secondary {
      background: #e7eeff;
      color: #111c2d;
      box-shadow: none;
    }
    .btn-secondary:hover {
      background: #d8e3fb;
    }
    .btn-warning {
      background: #b70011;
      color: white;
    }
    .alert {
      padding: 1rem 1.25rem;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .alert-success { background: #e7ffe5; color: #00682e; border: 1px solid #79db8d; }
    .alert-error { background: #ffdad6; color: #93000a; border: 1px solid #ffb4ab; }
    .actions-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 1.5rem;
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
          <p style="color: #5c403c; font-size: 0.95rem;">Panel de Diagnóstico & Configuración en Neiva, Huila</p>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
          <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
          <span><?php echo htmlspecialchars($message); ?></span>
        </div>
      <?php endif; ?>

      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; padding-bottom:1rem; border-bottom:1px solid #e7eeff;">
        <div>
          <strong>Estado de Conexión MySQL:</strong>
          <?php if ($dbStatus): ?>
            <span class="badge badge-success"><span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> Conectado a MySQL (localhost:3306)</span>
          <?php else: ?>
            <span class="badge badge-error"><span class="material-symbols-outlined" style="font-size:16px;">cancel</span> Desconectado</span>
          <?php endif; ?>
        </div>
        <div>
          <span style="font-size:0.85rem; color:#5c403c;">Base de datos: <strong>antojo_virtual</strong> (MariaDB / PHP 8.2)</span>
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

      <h3 style="font-family:'Epilogue', sans-serif; margin-top:1.5rem; margin-bottom:0.5rem; font-size:1.15rem;">
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
                  <span style="color: var(--success); font-weight:700;">✓ Correcta</span>
                <?php else: ?>
                  <span style="color: var(--primary); font-weight:700;">✗ Falta</span>
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
          <button type="submit" class="btn btn-warning">
            <span class="material-symbols-outlined">restart_alt</span>
            Reiniciar Base de Datos
          </button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
