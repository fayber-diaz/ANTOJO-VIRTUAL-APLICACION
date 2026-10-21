<?php
require_once __DIR__ . '/../config/db.php';

$category = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$comuna = trim($_GET['comuna'] ?? '');
$format = $_GET['format'] ?? '';

// Detección de si la solicitud viene de un navegador web (HTML visual) o de una llamada API (JSON)
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$wantsJson = ($format === 'json') ||
             (strpos($acceptHeader, 'application/json') !== false && strpos($acceptHeader, 'text/html') === false) ||
             (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

try {
    $db = getDB();

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

    // Si el cliente pide JSON explícito o es llamada AJAX
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        jsonResponse([
            'success' => true,
            'count' => count($restaurants),
            'data' => $restaurants
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
  <title>Directorio Visual de Restaurantes en Neiva | Antojo Virtual</title>
  <meta name="description" content="Explora los 40 restaurantes y sitios gastronómicos aliados de Antojo Virtual en Neiva: hamburguesas, pizzas, asados y postres.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="stylesheet" href="../assets/css/variables.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f1f4f9;
      color: #1e293b;
      min-height: 100vh;
      padding-bottom: 3rem;
    }
    .header-nav {
      background: #ffffff;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      padding: 1rem 1.5rem;
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .logo-container {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      color: inherit;
    }
    .logo-badge {
      background: linear-gradient(135deg, #dc2626, #ef4444);
      color: white;
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(220,38,38,0.3);
    }
    .nav-actions {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .btn {
      padding: 0.6rem 1.1rem;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.85rem;
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
      max-width: 1200px;
      margin: 1.5rem auto;
      padding: 1.75rem;
      background: linear-gradient(135deg, #ffffff, #f8fafc);
      border-radius: 20px;
      box-shadow: 8px 8px 24px #d9e2ec, -8px -8px 24px #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.25rem;
    }
    .search-filter-box {
      max-width: 1200px;
      margin: 0 auto 1.5rem;
      padding: 0 1rem;
    }
    .search-input-wrapper {
      position: relative;
      margin-bottom: 1rem;
    }
    .search-input-wrapper input {
      width: 100%;
      padding: 0.9rem 1rem 0.9rem 3rem;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
      font-size: 0.95rem;
      font-family: inherit;
      box-shadow: inset 2px 2px 5px rgba(0,0,0,0.04);
      outline: none;
    }
    .search-input-wrapper span {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }
    .filter-pills {
      display: flex;
      gap: 0.6rem;
      overflow-x: auto;
      padding-bottom: 0.5rem;
    }
    .pill {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      color: #475569;
      font-size: 0.85rem;
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
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .restaurants-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }
    .restaurant-card {
      background: #ffffff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 6px 6px 18px #dce4ee, -6px -6px 18px #ffffff;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      display: flex;
      flex-direction: column;
    }
    .restaurant-card:hover {
      transform: translateY(-4px);
      box-shadow: 8px 12px 24px rgba(0,0,0,0.08);
    }
    .card-img-wrap {
      position: relative;
      height: 180px;
      overflow: hidden;
      background: #e2e8f0;
    }
    .card-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    .restaurant-card:hover .card-img-wrap img {
      transform: scale(1.05);
    }
    .badge-float {
      position: absolute;
      top: 0.75rem;
      right: 0.75rem;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(8px);
      color: #ffffff;
      padding: 0.35rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }
    .card-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }
    .card-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 0.35rem;
    }
    .card-address {
      font-size: 0.8rem;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 0.3rem;
      margin-bottom: 0.75rem;
    }
    .card-rating-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.85rem;
      font-size: 0.85rem;
    }
    .rating-badge {
      background: #fef08a;
      color: #854d0e;
      padding: 0.2rem 0.5rem;
      border-radius: 8px;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      gap: 0.2rem;
    }
    .star-dish-box {
      background: #f8fafc;
      border-radius: 10px;
      padding: 0.75rem;
      border-left: 3px solid #dc2626;
      margin-bottom: 1rem;
      font-size: 0.85rem;
    }
    .star-dish-title {
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.2rem;
    }
    .card-actions {
      margin-top: auto;
      display: flex;
      gap: 0.6rem;
    }
  </style>
</head>
<body>

  <!-- Navegación Superior -->
  <header class="header-nav">
    <a href="../index.php" class="logo-container">
      <div class="logo-badge">
        <span class="material-symbols-outlined" style="font-size: 26px;">restaurant</span>
      </div>
      <div>
        <h1 style="font-size: 1.15rem; font-weight: 900; color: #0f172a; line-height: 1.1;">Antojo Virtual</h1>
        <span style="font-size: 0.75rem; color: #dc2626; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Directorio de Restaurantes • Neiva</span>
      </div>
    </a>
    <div class="nav-actions">
      <a href="../index.php" class="btn btn-primary">
        <span class="material-symbols-outlined" style="font-size: 18px;">storefront</span>
        Ir a la App & Carrito
      </a>
      <a href="menu.php" class="btn btn-secondary">
        <span class="material-symbols-outlined" style="font-size: 18px;">menu_book</span>
        Ver Menú de Platos
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
      <span style="font-size: 0.75rem; font-weight: 800; color: #dc2626; text-transform: uppercase; letter-spacing: 1px;">Explorador Gastronómico Oficial</span>
      <h2 style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-top: 0.25rem;">
        40 Sitios Aliados en Toda Neiva, Huila
      </h2>
      <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.4rem; max-width: 650px;">
        Parrilladas al carbón, hamburguesas smash, pizzas al horno de leña, pollo crispy, tacos y repostería artesanal en las 10 comunas de Neiva.
      </p>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center;">
      <div style="background: #ffffff; padding: 0.75rem 1.25rem; border-radius: 14px; text-align: center; box-shadow: 2px 4px 12px rgba(0,0,0,0.05);">
        <div style="font-size: 1.5rem; font-weight: 900; color: #dc2626;">26</div>
        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Comidas Rápidas</div>
      </div>
      <div style="background: #ffffff; padding: 0.75rem 1.25rem; border-radius: 14px; text-align: center; box-shadow: 2px 4px 12px rgba(0,0,0,0.05);">
        <div style="font-size: 1.5rem; font-weight: 900; color: #e11d48;">14</div>
        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Dulces & Postres</div>
      </div>
    </div>
  </div>

  <!-- Barra de Filtros -->
  <div class="search-filter-box">
    <div class="search-input-wrapper">
      <span class="material-symbols-outlined">search</span>
      <input type="text" id="filter-input" placeholder="Buscar por nombre, plato, dirección o barrio de Neiva..." onkeyup="filterCards()">
    </div>
    <div class="filter-pills">
      <a href="?category=all" class="pill <?= ($category === 'all' || empty($category)) ? 'active' : '' ?>">🌟 Todos (40)</a>
      <a href="?category=comidas_rapidas" class="pill <?= $category === 'comidas_rapidas' ? 'active' : '' ?>">🍔 Rápidas & Parrillas (26)</a>
      <a href="?category=dulces" class="pill <?= $category === 'dulces' ? 'active' : '' ?>">🍨 Dulces & Postres (14)</a>
      <a href="?category=pizzas" class="pill <?= $category === 'pizzas' ? 'active' : '' ?>">🍕 Pizzas</a>
      <a href="?category=asados" class="pill <?= $category === 'asados' ? 'active' : '' ?>">🥩 Asados al Carbón</a>
      <a href="?category=burgers" class="pill <?= $category === 'burgers' ? 'active' : '' ?>">🍔 Hamburguesas</a>
      <a href="?category=salchipapas" class="pill <?= $category === 'salchipapas' ? 'active' : '' ?>">🍟 Salchipapas</a>
      <a href="?category=pollo" class="pill <?= $category === 'pollo' ? 'active' : '' ?>">🍗 Pollo Crispy</a>
      <a href="?category=mexicana" class="pill <?= $category === 'mexicana' ? 'active' : '' ?>">🌮 Tacos & Tex-Mex</a>
      <a href="?category=helados" class="pill <?= $category === 'helados' ? 'active' : '' ?>">🍨 Helados</a>
      <a href="?category=postres" class="pill <?= $category === 'postres' ? 'active' : '' ?>">🍰 Postres</a>
    </div>
  </div>

  <!-- Grilla de Restaurantes -->
  <main class="container">
    <div class="restaurants-grid" id="restaurants-grid">
      <?php if (!empty($restaurants)): ?>
        <?php foreach ($restaurants as $r): ?>
          <article class="restaurant-card" data-name="<?= strtolower(htmlspecialchars($r['name'] . ' ' . $r['description'] . ' ' . $r['star_dish'] . ' ' . $r['address'])) ?>">
            <div class="card-img-wrap">
              <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy">
              <div class="badge-float">
                <span class="material-symbols-outlined" style="font-size: 14px; color: #f59e0b;">verified</span>
                <?= htmlspecialchars($r['badge']) ?>
              </div>
            </div>
            <div class="card-body">
              <h3 class="card-title"><?= htmlspecialchars($r['name']) ?></h3>
              <div class="card-address">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #dc2626;">location_on</span>
                <?= htmlspecialchars($r['address']) ?> (<?= htmlspecialchars($r['comuna']) ?>)
              </div>
              <div class="card-rating-row">
                <span class="rating-badge">★ <?= number_format($r['rating'], 1) ?></span>
                <span style="color: #64748b;">(<?= $r['reviews_count'] ?> reseñas)</span>
                <span style="margin-left: auto; color: #64748b; display: flex; align-items: center; gap: 0.2rem;">
                  <span class="material-symbols-outlined" style="font-size: 15px;">schedule</span>
                  <?= htmlspecialchars($r['delivery_time']) ?>
                </span>
              </div>
              <div class="star-dish-box">
                <div style="font-size: 0.72rem; color: #dc2626; font-weight: 800; text-transform: uppercase;">⭐ Plato Especialidad</div>
                <div class="star-dish-title"><?= htmlspecialchars($r['star_dish']) ?></div>
                <div style="color: #0f172a; font-weight: 800;"><?= $r['star_price_formatted'] ?> COP</div>
              </div>
              <div class="card-actions">
                <a href="../index.php?search=<?= urlencode($r['name']) ?>" class="btn btn-primary" style="flex: 1; justify-content: center;">
                  <span class="material-symbols-outlined" style="font-size: 16px;">shopping_bag</span>
                  Ver Platos
                </a>
                <a href="https://wa.me/<?= htmlspecialchars($r['whatsapp']) ?>" target="_blank" class="btn btn-secondary" style="background: #25d366; color: white; justify-content: center;" title="Contactar por WhatsApp">
                  <span class="material-symbols-outlined" style="font-size: 16px;">chat</span>
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: #ffffff; border-radius: 16px;">
          <span class="material-symbols-outlined" style="font-size: 48px; color: #94a3b8;">storefront</span>
          <h3 style="margin-top: 0.5rem; font-weight: 800;">No se encontraron restaurantes</h3>
          <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">Intenta con otro término de búsqueda o selecciona otra categoría.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    function filterCards() {
      const q = document.getElementById('filter-input').value.toLowerCase().trim();
      const cards = document.querySelectorAll('.restaurant-card');
      cards.forEach(card => {
        const text = card.getAttribute('data-name');
        card.style.display = text.includes(q) ? 'flex' : 'none';
      });
    }
  </script>
</body>
</html>
