<?php
require_once __DIR__ . '/../config/db.php';

$category = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;
$format = $_GET['format'] ?? '';

// Detección de si la solicitud viene de un navegador web (HTML visual) o de una llamada API (JSON)
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$wantsJson = ($format === 'json') ||
             (strpos($acceptHeader, 'application/json') !== false && strpos($acceptHeader, 'text/html') === false) ||
             (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

try {
    $db = getDB();

    // 1. Opciones de Personalización
    if (isset($_GET['options'])) {
        $stmt = $db->query("SELECT * FROM `item_options` ORDER BY `category`, `price` ASC");
        $allOptions = $stmt->fetchAll();

        $grouped = [
            'terminos' => [],
            'panes' => [],
            'toppings' => [],
            'salsas' => [],
            'sabores' => [],
            'bases_dulces' => [],
            'siropes' => [],
            'toppings_dulces' => [],
            'dulzura' => []
        ];

        foreach ($allOptions as $opt) {
            $cat = $opt['category'];
            $key = match($cat) {
                'termino' => 'terminos',
                'pan' => 'panes',
                'topping' => 'toppings',
                'salsa' => 'salsas',
                'sabor_helado' => 'sabores',
                'base_dulce' => 'bases_dulces',
                'sirope_dulce' => 'siropes',
                'topping_dulce' => 'toppings_dulces',
                'dulzura' => 'dulzura',
                default => 'toppings'
            };

            $opt['id'] = (int)$opt['id'];
            $opt['price'] = (int)$opt['price'];
            $opt['is_default'] = (bool)$opt['is_default'];
            $opt['price_formatted'] = $opt['price'] > 0 ? '+$' . number_format($opt['price'], 0, ',', '.') : 'Gratis';

            $grouped[$key][] = $opt;
        }

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            jsonResponse([
                'success' => true,
                'options' => $grouped
            ]);
            exit;
        }
    }

    // 2. Detalle de un plato específico
    if (isset($_GET['id'])) {
        $dishId = (int)$_GET['id'];
        $stmt = $db->prepare("
            SELECT m.*, r.name as restaurant_name, r.delivery_time, r.delivery_fee, r.site_type
            FROM `menu_items` m
            JOIN `restaurants` r ON m.restaurant_id = r.id
            WHERE m.id = :id AND m.is_available = 1
        ");
        $stmt->execute([':id' => $dishId]);
        $dish = $stmt->fetch();

        if (!$dish) {
            if ($wantsJson) {
                jsonResponse(['success' => false, 'error' => 'Plato no encontrado'], 404);
                exit;
            }
            die("Plato no encontrado.");
        }

        $dish['id'] = (int)$dish['id'];
        $dish['price'] = (int)$dish['price'];
        $dish['price_formatted'] = '$' . number_format($dish['price'], 0, ',', '.');
        $dish['tags_array'] = !empty($dish['tags']) ? array_map('trim', explode(',', $dish['tags'])) : [];
        $dish['dish_type'] = $dish['dish_type'] ?: (in_array($dish['category'], ['helados', 'postres', 'waffles', 'malteadas']) ? 'dulce' : 'salado');

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            jsonResponse(['success' => true, 'data' => $dish]);
            exit;
        }
    }

    // 3. Listado de platos con filtros
    $query = "
        SELECT m.*, r.name as restaurant_name, r.badge as restaurant_badge, r.delivery_time, r.site_type, r.comuna as restaurant_comuna
        FROM `menu_items` m
        JOIN `restaurants` r ON m.restaurant_id = r.id
        WHERE m.is_available = 1
    ";
    $params = [];

    if ($category === 'comidas_rapidas' || $category === 'salados') {
        $query .= " AND m.dish_type = 'salado'";
    } elseif ($category === 'dulces' || $category === 'postres_all') {
        $query .= " AND m.dish_type = 'dulce'";
    } elseif ($category !== 'all' && !empty($category)) {
        $query .= " AND m.category = :category";
        $params[':category'] = $category;
    }

    if ($restaurantId) {
        $query .= " AND m.restaurant_id = :restaurant_id";
        $params[':restaurant_id'] = $restaurantId;
    }

    if (!empty($search)) {
        $query .= " AND (m.name LIKE :search OR m.description LIKE :search OR m.tags LIKE :search OR r.name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY m.is_featured DESC, m.is_popular DESC, m.id ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $dishes = $stmt->fetchAll();

    foreach ($dishes as &$d) {
        $d['id'] = (int)$d['id'];
        $d['restaurant_id'] = (int)$d['restaurant_id'];
        $d['price'] = (int)$d['price'];
        $d['price_formatted'] = '$' . number_format($d['price'], 0, ',', '.');
        $d['tags_array'] = !empty($d['tags']) ? array_map('trim', explode(',', $d['tags'])) : [];
        $d['dish_type'] = $d['dish_type'] ?: (in_array($d['category'], ['helados', 'postres', 'waffles', 'malteadas']) ? 'dulce' : 'salado');
        $d['is_featured'] = (bool)$d['is_featured'];
        $d['is_popular'] = (bool)$d['is_popular'];
    }

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        jsonResponse([
            'success' => true,
            'count' => count($dishes),
            'data' => $dishes
        ]);
        exit;
    }

} catch (Exception $e) {
    if ($wantsJson) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        exit;
    }
    $errorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo Visual de Menú y Platos en Neiva | Antojo Virtual</title>
  <meta name="description" content="Explora los 79 platos de Antojo Virtual en Neiva: pizzas, hamburguesas, asados al carbón, salchipapas, helados y postres.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="stylesheet" href="../assets/css/variables.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html {
      font-size: 13.5px;
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f1f4f9;
      color: #1e293b;
      min-height: 100vh;
      padding-bottom: 2.5rem;
    }
    .header-nav {
      background: #ffffff;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      padding: 0.65rem 1.25rem;
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.85rem;
    }
    .logo-container {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      text-decoration: none;
      color: inherit;
    }
    .logo-badge {
      background: linear-gradient(135deg, #dc2626, #ef4444);
      color: white;
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(220,38,38,0.3);
    }
    .nav-actions {
      display: flex;
      gap: 0.65rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .btn {
      padding: 0.5rem 1rem;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.82rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
    }
    .btn-primary {
      background: #dc2626;
      color: #ffffff;
      box-shadow: 0 4px 14px rgba(220,38,38,0.25);
    }
    .btn-primary:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: #e2e8f0;
      color: #334155;
    }
    .btn-secondary:hover {
      background: #cbd5e1;
    }
    .hero-banner {
      max-width: 1220px;
      margin: 1.25rem auto;
      padding: 1.35rem 1.75rem;
      background: linear-gradient(135deg, #ffffff, #f8fafc);
      border-radius: 16px;
      box-shadow: 8px 8px 24px #d9e2ec, -8px -8px 24px #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .search-filter-box {
      max-width: 1220px;
      margin: 0 auto 1.25rem;
      padding: 0 1rem;
    }
    .search-input-wrapper {
      position: relative;
      margin-bottom: 0.85rem;
    }
    .search-input-wrapper input {
      width: 100%;
      padding: 0.65rem 1rem 0.65rem 2.75rem;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
      font-size: 0.88rem;
      font-family: inherit;
      box-shadow: inset 2px 2px 5px rgba(0,0,0,0.04);
      outline: none;
    }
    .search-input-wrapper span {
      position: absolute;
      left: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }
    .filter-pills {
      display: flex;
      gap: 0.5rem;
      overflow-x: auto;
      padding-bottom: 0.4rem;
    }
    .pill {
      padding: 0.45rem 0.95rem;
      border-radius: 20px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      color: #475569;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      box-shadow: 3px 3px 8px #d9e2ec, -3px -3px 8px #ffffff;
      transition: all 0.2s ease;
    }
    .pill.active, .pill:hover {
      background: #dc2626;
      color: #ffffff;
      border-color: #dc2626;
    }
    .container {
      max-width: 1220px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .dishes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.25rem;
    }
    .dish-card {
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 6px 6px 18px #dce4ee, -6px -6px 18px #ffffff;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      display: flex;
      flex-direction: column;
    }
    .dish-card:hover {
      transform: translateY(-4px);
      box-shadow: 8px 12px 24px rgba(0,0,0,0.08);
    }
    .dish-img-wrap {
      position: relative;
      height: 155px;
      overflow: hidden;
      background: #e2e8f0;
    }
    .dish-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    .dish-card:hover .dish-img-wrap img {
      transform: scale(1.06);
    }
    .dish-type-badge {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(8px);
      color: #ffffff;
      padding: 0.3rem 0.65rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
    }
    .dish-price-badge {
      position: absolute;
      bottom: 0.75rem;
      right: 0.75rem;
      background: #dc2626;
      color: #ffffff;
      padding: 0.35rem 0.75rem;
      border-radius: 12px;
      font-weight: 900;
      font-size: 0.95rem;
      box-shadow: 0 4px 10px rgba(220,38,38,0.3);
    }
    .dish-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }
    .dish-restaurant {
      font-size: 0.78rem;
      font-weight: 700;
      color: #dc2626;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 0.25rem;
    }
    .dish-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.3;
      margin-bottom: 0.5rem;
    }
    .dish-desc {
      font-size: 0.82rem;
      color: #64748b;
      line-height: 1.45;
      margin-bottom: 0.85rem;
    }
    .dish-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
      margin-bottom: 1rem;
    }
    .dish-tag {
      background: #f1f5f9;
      color: #475569;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.2rem 0.5rem;
      border-radius: 6px;
    }
    .dish-actions {
      margin-top: auto;
    }
  </style>
</head>
<body>

  <!-- Navegación Superior -->
  <header class="header-nav">
    <a href="../index.php" class="logo-container">
      <div class="logo-badge">
        <span class="material-symbols-outlined" style="font-size: 26px;">restaurant_menu</span>
      </div>
      <div>
        <h1 style="font-size: 1.15rem; font-weight: 900; color: #0f172a; line-height: 1.1;">Antojo Virtual</h1>
        <span style="font-size: 0.75rem; color: #dc2626; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Catálogo de Menú • 79 Platos</span>
      </div>
    </a>
    <div class="nav-actions">
      <a href="../index.php" class="btn btn-primary">
        <span class="material-symbols-outlined" style="font-size: 18px;">storefront</span>
        Ir a la App & Carrito
      </a>
      <a href="restaurants.php" class="btn btn-secondary">
        <span class="material-symbols-outlined" style="font-size: 18px;">store</span>
        Ver 40 Restaurantes
      </a>
      <a href="?format=json<?= !empty($category) && $category !== 'all' ? '&category=' . urlencode($category) : '' ?>" class="btn btn-secondary" title="Ver respuesta cruda en JSON">
        <span class="material-symbols-outlined" style="font-size: 18px;">code</span>
        Ver JSON Crudo
      </a>
    </div>
  </header>

  <!-- Hero Informativo -->
  <div class="hero-banner">
    <div>
      <span style="font-size: 0.75rem; font-weight: 800; color: #dc2626; text-transform: uppercase; letter-spacing: 1px;">Catálogo Gastronómico de Neiva</span>
      <h2 style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-top: 0.25rem;">
        79 Platos y Antojos para Degustar en Neiva
      </h2>
      <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.4rem; max-width: 650px;">
        Pizzas napolitanas al horno de leña, asados a la brasa, hamburguesas smash, pollo crispy, tacos mexicanos, heladería artesanal y repostería fina.
      </p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <div style="background: #ffffff; padding: 0.75rem 1.25rem; border-radius: 14px; text-align: center; box-shadow: 2px 4px 12px rgba(0,0,0,0.05);">
        <div style="font-size: 1.5rem; font-weight: 900; color: #dc2626;">49</div>
        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Platos Salados</div>
      </div>
      <div style="background: #ffffff; padding: 0.75rem 1.25rem; border-radius: 14px; text-align: center; box-shadow: 2px 4px 12px rgba(0,0,0,0.05);">
        <div style="font-size: 1.5rem; font-weight: 900; color: #e11d48;">30</div>
        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Platos Dulces</div>
      </div>
    </div>
  </div>

  <!-- Barra de Filtros -->
  <div class="search-filter-box">
    <div class="search-input-wrapper">
      <span class="material-symbols-outlined">search</span>
      <input type="text" id="filter-input" placeholder="Buscar por plato, ingrediente, categoría o restaurante..." onkeyup="filterDishes()">
    </div>
    <div class="filter-pills">
      <a href="?category=all" class="pill <?= ($category === 'all' || empty($category)) ? 'active' : '' ?>">🌟 Todos (79)</a>
      <a href="?category=pizzas" class="pill <?= $category === 'pizzas' ? 'active' : '' ?>">🍕 Pizzas (6)</a>
      <a href="?category=asados" class="pill <?= $category === 'asados' ? 'active' : '' ?>">🥩 Asados al Carbón (6)</a>
      <a href="?category=burgers" class="pill <?= $category === 'burgers' ? 'active' : '' ?>">🍔 Hamburguesas (15)</a>
      <a href="?category=salchipapas" class="pill <?= $category === 'salchipapas' ? 'active' : '' ?>">🍟 Salchipapas (6)</a>
      <a href="?category=pollo" class="pill <?= $category === 'pollo' ? 'active' : '' ?>">🍗 Pollo Crispy (2)</a>
      <a href="?category=mexicana" class="pill <?= $category === 'mexicana' ? 'active' : '' ?>">🌮 Tex-Mex & Tacos (4)</a>
      <a href="?category=arepas" class="pill <?= $category === 'arepas' ? 'active' : '' ?>">🫓 Arepas (4)</a>
      <a href="?category=perros" class="pill <?= $category === 'perros' ? 'active' : '' ?>">🌭 Perros Calientes (3)</a>
      <a href="?category=desgranados" class="pill <?= $category === 'desgranados' ? 'active' : '' ?>">🌽 Desgranados (3)</a>
      <a href="?category=helados" class="pill <?= $category === 'helados' ? 'active' : '' ?>">🍨 Helados (9)</a>
      <a href="?category=postres" class="pill <?= $category === 'postres' ? 'active' : '' ?>">🍰 Postres (13)</a>
      <a href="?category=waffles" class="pill <?= $category === 'waffles' ? 'active' : '' ?>">🧇 Waffles (4)</a>
      <a href="?category=malteadas" class="pill <?= $category === 'malteadas' ? 'active' : '' ?>">🥤 Shakes (4)</a>
    </div>
  </div>

  <!-- Grilla de Platos -->
  <main class="container">
    <div class="dishes-grid" id="dishes-grid">
      <?php if (!empty($dishes)): ?>
        <?php foreach ($dishes as $d): ?>
          <article class="dish-card" data-search="<?= strtolower(htmlspecialchars($d['name'] . ' ' . $d['description'] . ' ' . $d['restaurant_name'] . ' ' . $d['tags'])) ?>">
            <div class="dish-img-wrap">
              <img src="<?= htmlspecialchars($d['image']) ?>" alt="<?= htmlspecialchars($d['name']) ?>" loading="lazy">
              <div class="dish-type-badge">
                <?= $d['dish_type'] === 'dulce' ? '🍨 Dulce' : '🍔 Comida Rápida' ?>
              </div>
              <div class="dish-price-badge">
                <?= $d['price_formatted'] ?>
              </div>
            </div>
            <div class="dish-body">
              <div class="dish-restaurant">
                <?= htmlspecialchars($d['restaurant_name']) ?>
              </div>
              <h3 class="dish-title"><?= htmlspecialchars($d['name']) ?></h3>
              <p class="dish-desc"><?= htmlspecialchars($d['description']) ?></p>
              
              <?php if (!empty($d['tags_array'])): ?>
                <div class="dish-tags">
                  <?php foreach ($d['tags_array'] as $tag): ?>
                    <span class="dish-tag"><?= htmlspecialchars($tag) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="dish-actions">
                <a href="../index.php?dish=<?= $d['id'] ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                  <span class="material-symbols-outlined" style="font-size: 18px;">tune</span>
                  Personalizar & Pedir
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: #ffffff; border-radius: 16px;">
          <span class="material-symbols-outlined" style="font-size: 48px; color: #94a3b8;">restaurant_menu</span>
          <h3 style="margin-top: 0.5rem; font-weight: 800;">No se encontraron platos</h3>
          <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">Intenta con otra categoría o prueba buscando otro ingrediente.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    function filterDishes() {
      const q = document.getElementById('filter-input').value.toLowerCase().trim();
      const cards = document.querySelectorAll('.dish-card');
      cards.forEach(card => {
        const text = card.getAttribute('data-search');
        card.style.display = text.includes(q) ? 'flex' : 'none';
      });
    }
  </script>
</body>
</html>
