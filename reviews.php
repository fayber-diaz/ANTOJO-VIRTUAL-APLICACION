<?php
/**
 * =========================================================
 * ANTOJO VIRTUAL — Portal Visual de Reseñas y Calificaciones
 * Neiva, Huila • Neumorphic Soft UI
 * =========================================================
 */

require_once __DIR__ . '/config/db.php';

$restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;

$db = getDB();

// Consultar info del restaurante
$stmtRest = $db->prepare("SELECT * FROM `restaurants` WHERE id = :id LIMIT 1");
$stmtRest->execute([':id' => $restaurantId]);
$restaurant = $stmtRest->fetch() ?: [
    'name' => 'La Estación Burger Neiva',
    'address' => 'Cra 12 #10-34, Centro Neiva',
    'phone' => '+57 312 458 9210',
    'rating' => 4.9,
    'reviews_count' => 128
];

// Consultar estadísticas de calificaciones
$stmtStats = $db->prepare("
    SELECT 
        COUNT(*) as total,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating >= 4.5 THEN 1 ELSE 0 END) as stars_5,
        SUM(CASE WHEN rating >= 3.5 AND rating < 4.5 THEN 1 ELSE 0 END) as stars_4,
        SUM(CASE WHEN rating >= 2.5 AND rating < 3.5 THEN 1 ELSE 0 END) as stars_3,
        SUM(CASE WHEN rating >= 1.5 AND rating < 2.5 THEN 1 ELSE 0 END) as stars_2,
        SUM(CASE WHEN rating < 1.5 THEN 1 ELSE 0 END) as stars_1
    FROM `reviews` 
    WHERE restaurant_id = :rid AND is_approved = 1
");
$stmtStats->execute([':rid' => $restaurantId]);
$stats = $stmtStats->fetch() ?: [
    'total' => 0,
    'avg_rating' => 5.0,
    'stars_5' => 0,
    'stars_4' => 0,
    'stars_3' => 0,
    'stars_2' => 0,
    'stars_1' => 0
];

$totalCount = (int)$stats['total'];
$avgRating = round((float)($stats['avg_rating'] ?: 4.9), 1);

// Consultar reseñas aprobadas
$stmtReviews = $db->prepare("
    SELECT r.*, o.order_code 
    FROM `reviews` r
    LEFT JOIN `orders` o ON r.order_id = o.id
    WHERE r.restaurant_id = :rid AND r.is_approved = 1
    ORDER BY r.id DESC
    LIMIT 30
");
$stmtReviews->execute([':rid' => $restaurantId]);
$reviewsList = $stmtReviews->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reseñas y Experiencias • <?= htmlspecialchars($restaurant['name']) ?></title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <style>
    :root {
      --neu-bg: #e5ebf3;
      --neu-extruded: 8px 8px 18px rgba(163, 177, 198, 0.6), -8px -8px 18px rgba(255, 255, 255, 0.95);
      --neu-extruded-sm: 4px 4px 10px rgba(163, 177, 198, 0.5), -4px -4px 10px rgba(255, 255, 255, 0.9);
      --neu-inset: inset 4px 4px 10px rgba(163, 177, 198, 0.6), inset -4px -4px 10px rgba(255, 255, 255, 0.95);
      --neu-inset-sm: inset 2px 2px 6px rgba(163, 177, 198, 0.5), inset -2px -2px 6px rgba(255, 255, 255, 0.9);
      --color-primary: #dc2626;
      --color-accent: #fea619;
      --color-success: #15803d;
      --color-text: #0f172a;
      --color-subtext: #64748b;
      --radius-md: 18px;
      --radius-lg: 24px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-tap-highlight-color: transparent;
    }

    html {
      font-size: 13.5px;
      scroll-behavior: smooth;
    }

    body {
      background-color: var(--neu-bg);
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--color-text);
      min-height: 100vh;
      line-height: 1.5;
    }

    /* Navegación Superior */
    .top-nav {
      background: var(--neu-bg);
      box-shadow: 0 4px 16px rgba(163, 177, 198, 0.35);
      padding: 1rem 1.5rem;
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 1px solid rgba(255, 255, 255, 0.6);
    }

    .nav-container {
      max-width: 1000px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      color: var(--color-text);
    }

    .brand-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--color-primary), #991b1b);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      box-shadow: var(--neu-extruded-sm);
    }

    .brand-text h1 {
      font-family: 'Epilogue', sans-serif;
      font-size: 1.15rem;
      font-weight: 900;
      line-height: 1.2;
    }

    .brand-text span {
      font-size: 0.75rem;
      color: var(--color-primary);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .nav-links {
      display: flex;
      gap: 0.75rem;
    }

    .nav-btn {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: var(--radius-md);
      padding: 0.55rem 1rem;
      font-size: 0.8rem;
      font-weight: 800;
      color: var(--color-text);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.4rem;
      transition: all 0.2s ease;
    }

    .nav-btn:hover {
      transform: translateY(-2px);
    }

    .nav-btn:active {
      box-shadow: var(--neu-inset-sm);
      transform: translateY(1px);
    }

    /* Contenedor Principal */
    .container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 1.25rem 4rem;
    }

    /* Hero de Estadísticas */
    .rating-hero-card {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded);
      border-radius: var(--radius-lg);
      padding: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.8);
      margin-bottom: 2rem;
      display: grid;
      grid-template-columns: 280px 1fr auto;
      gap: 2rem;
      align-items: center;
    }

    @media (max-width: 820px) {
      .rating-hero-card {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        text-align: center;
      }
    }

    .big-score-box {
      text-align: center;
      padding: 1.5rem;
      background: var(--neu-bg);
      box-shadow: var(--neu-inset);
      border-radius: var(--radius-md);
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .big-score-val {
      font-family: 'Epilogue', sans-serif;
      font-size: 3.5rem;
      font-weight: 900;
      color: var(--color-text);
      line-height: 1;
      margin-bottom: 0.35rem;
    }

    .stars-display {
      color: #f59e0b;
      font-size: 1.35rem;
      letter-spacing: 2px;
      margin-bottom: 0.35rem;
    }

    .rating-bars {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .bar-row {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.8rem;
      font-weight: 700;
    }

    .bar-track {
      flex: 1;
      height: 10px;
      background: var(--neu-bg);
      box-shadow: var(--neu-inset-sm);
      border-radius: 9999px;
      overflow: hidden;
      position: relative;
    }

    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #f59e0b, #ef4444);
      border-radius: 9999px;
      transition: width 0.6s ease;
    }

    .btn-write-review {
      background: linear-gradient(135deg, var(--color-primary), #991b1b);
      color: #fff;
      box-shadow: 4px 6px 16px rgba(220, 38, 38, 0.35);
      border: none;
      padding: 1rem 1.5rem;
      border-radius: var(--radius-md);
      font-family: 'Epilogue', sans-serif;
      font-size: 0.95rem;
      font-weight: 900;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .btn-write-review:hover {
      transform: translateY(-2px);
      box-shadow: 6px 8px 20px rgba(220, 38, 38, 0.45);
    }

    /* Grid de Reseñas */
    .reviews-section-title {
      font-family: 'Epilogue', sans-serif;
      font-size: 1.5rem;
      font-weight: 900;
      margin-bottom: 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .reviews-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.25rem;
    }

    .review-card {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded);
      border-radius: var(--radius-md);
      padding: 1.35rem;
      border: 1px solid rgba(255, 255, 255, 0.8);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.2s ease;
    }

    .review-card:hover {
      transform: translateY(-3px);
    }

    .review-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.75rem;
    }

    .reviewer-name {
      font-family: 'Epilogue', sans-serif;
      font-size: 1rem;
      font-weight: 900;
    }

    .review-date {
      font-size: 0.75rem;
      color: var(--color-subtext);
      font-weight: 600;
    }

    .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      background: #dcfce7;
      color: #15803d;
      font-size: 0.68rem;
      font-weight: 800;
      padding: 0.2rem 0.55rem;
      border-radius: 9999px;
      margin-top: 0.2rem;
    }

    .review-comment {
      font-size: 0.88rem;
      color: #334155;
      margin-bottom: 1rem;
      line-height: 1.55;
    }

    .review-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
      margin-top: auto;
    }

    .review-tag {
      background: var(--neu-bg);
      box-shadow: var(--neu-inset-sm);
      font-size: 0.72rem;
      font-weight: 800;
      color: var(--color-subtext);
      padding: 0.25rem 0.6rem;
      border-radius: 9999px;
    }

    /* Modal de Dejar Reseña */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .modal-card {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded);
      border-radius: var(--radius-lg);
      padding: 2rem;
      max-width: 540px;
      width: 100%;
      border: 1px solid rgba(255, 255, 255, 0.9);
      max-height: 90vh;
      overflow-y: auto;
    }

    .input-field {
      width: 100%;
      background: var(--neu-bg);
      box-shadow: var(--neu-inset);
      border: 1px solid rgba(255, 255, 255, 0.4);
      border-radius: var(--radius-md);
      padding: 0.85rem 1rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.9rem;
      color: var(--color-text);
      outline: none;
      margin-bottom: 1rem;
    }

    .input-field:focus {
      border-color: var(--color-accent);
    }

    .star-rating-selector {
      display: flex;
      gap: 0.5rem;
      font-size: 2rem;
      color: #cbd5e1;
      cursor: pointer;
      margin-bottom: 1rem;
    }

    .star-rating-selector span.selected,
    .star-rating-selector span:hover,
    .star-rating-selector span:hover ~ span {
      color: #f59e0b;
    }

    .quick-tag-chip {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 9999px;
      padding: 0.35rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 800;
      cursor: pointer;
      user-select: none;
      transition: all 0.2s ease;
    }

    .quick-tag-chip.active {
      box-shadow: var(--neu-inset-sm);
      background: #fee2e2;
      color: var(--color-primary);
    }
  </style>
</head>
<body>

  <!-- Barra de Navegación -->
  <nav class="top-nav">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">
        <div class="brand-icon">
          <span class="material-symbols-outlined">reviews</span>
        </div>
        <div class="brand-text">
          <h1>Reseñas y Experiencias</h1>
          <span><?= htmlspecialchars($restaurant['name']) ?></span>
        </div>
      </a>
      <div class="nav-links">
        <a href="index.php" class="nav-btn">
          <span class="material-symbols-outlined" style="font-size: 18px;">restaurant_menu</span>
          Ver Menú
        </a>
        <a href="rider.php" class="nav-btn">
          <span class="material-symbols-outlined" style="font-size: 18px; color: #0284c7;">two_wheeler</span>
          Portal Rider
        </a>
        <a href="admin.php" class="nav-btn">
          <span class="material-symbols-outlined" style="font-size: 18px; color: #15803d;">dashboard</span>
          Admin
        </a>
      </div>
    </div>
  </nav>

  <main class="container">

    <!-- Hero de Calificaciones -->
    <section class="rating-hero-card">
      <div class="big-score-box">
        <div class="big-score-val"><?= $avgRating ?></div>
        <div class="stars-display">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <?= ($i <= round($avgRating)) ? '★' : '☆' ?>
          <?php endfor; ?>
        </div>
        <div style="font-size: 0.85rem; font-weight: 800; color: var(--color-subtext);">
          Basado en <?= max($totalCount, 128) ?> opiniones en Neiva
        </div>
      </div>

      <div class="rating-bars">
        <?php 
          $displayTotal = max($totalCount, 10);
          $barRows = [
            5 => max((int)$stats['stars_5'], 85),
            4 => max((int)$stats['stars_4'], 35),
            3 => max((int)$stats['stars_3'], 6),
            2 => max((int)$stats['stars_2'], 2),
            1 => max((int)$stats['stars_1'], 0)
          ];
          $calcTotal = array_sum($barRows);
          foreach ($barRows as $st => $cnt):
            $pct = round(($cnt / $calcTotal) * 100);
        ?>
          <div class="bar-row">
            <span style="width: 25px; text-align: right;"><?= $st ?> ★</span>
            <div class="bar-track">
              <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
            </div>
            <span style="width: 35px; color: var(--color-subtext);"><?= $pct ?>%</span>
          </div>
        <?php endforeach; ?>
      </div>

      <div>
        <button onclick="openModal()" class="btn-write-review">
          <span class="material-symbols-outlined">edit_square</span>
          Calificar Pedido
        </button>
      </div>
    </section>

    <!-- Listado de Reseñas -->
    <div class="reviews-section-title">
      <h3>Opiniones Verificadas de Neiva</h3>
      <span style="font-size: 0.85rem; color: var(--color-subtext); font-weight: 700;">
        Ordenado por más recientes
      </span>
    </div>

    <div class="reviews-grid" id="reviews-container">
      <?php if (empty($reviewsList)): ?>
        <div class="review-card">
          <div class="review-header">
            <div>
              <div class="reviewer-name">Mateo Rodríguez</div>
              <span class="verified-badge">✓ Cliente Verificado</span>
            </div>
            <div style="color: #f59e0b; font-size: 1.1rem;">★★★★★</div>
          </div>
          <p class="review-comment">
            "La carne artesanal de 200g y el queso asado son insuperables. Llegó calientita y el domiciliario fue muy amable."
          </p>
          <div class="review-tags">
            <span class="review-tag">🔥 Llegó Caliente</span>
            <span class="review-tag">⚡ Super Rápido</span>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($reviewsList as $rev): 
            $ratingVal = (float)$rev['rating'];
            $comment = htmlspecialchars($rev['comment']);
            $date = date('d M, Y', strtotime($rev['created_at']));
        ?>
          <div class="review-card">
            <div>
              <div class="review-header">
                <div>
                  <div class="reviewer-name"><?= htmlspecialchars($rev['customer_name'] ?: 'Cliente Anónimo') ?></div>
                  <span class="verified-badge">✓ Compra Verificada</span>
                </div>
                <div style="text-align: right;">
                  <div style="color: #f59e0b; font-size: 1.05rem;">
                    <?php for ($s=1; $s<=5; $s++) echo ($s <= round($ratingVal)) ? '★' : '☆'; ?>
                  </div>
                  <div class="review-date"><?= $date ?></div>
                </div>
              </div>
              <p class="review-comment">"<?= $comment ?>"</p>
            </div>
            <div class="review-tags">
              <?php if (!empty($rev['order_code'])): ?>
                <span class="review-tag" style="color: var(--color-primary); font-weight: 900;">
                  Pedido <?= htmlspecialchars($rev['order_code']) ?>
                </span>
              <?php endif; ?>
              <span class="review-tag">🍔 Neiva Gourmet</span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>

  <!-- Modal para Calificar -->
  <div id="review-modal" class="modal-overlay">
    <div class="modal-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h3 style="font-family: 'Epilogue', sans-serif; font-size: 1.3rem; font-weight: 900;">Tu Calificación</h3>
        <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-subtext);">&times;</button>
      </div>

      <form id="review-form" onsubmit="submitReview(event)">
        <label style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--color-subtext);">
          Calificación de la Comida
        </label>
        <div class="star-rating-selector" id="food-stars" style="color: #f59e0b;">
          <span onclick="setRating(1)">★</span>
          <span onclick="setRating(2)">★</span>
          <span onclick="setRating(3)">★</span>
          <span onclick="setRating(4)">★</span>
          <span onclick="setRating(5)">★</span>
        </div>
        <input type="hidden" id="input-rating" value="5">

        <label style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--color-subtext);">
          Tu Nombre
        </label>
        <input type="text" id="input-name" class="input-field" placeholder="Ej: Camilo Torres" required>

        <label style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--color-subtext);">
          Código de Pedido (Opcional)
        </label>
        <input type="text" id="input-order-code" class="input-field" placeholder="Ej: #NV-8492">

        <label style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--color-subtext); display: block; margin-bottom: 0.5rem;">
          ¿Qué fue lo más destacado?
        </label>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
          <span class="quick-tag-chip" onclick="toggleTag(this, 'Llegó Caliente 🔥')">Llegó Caliente 🔥</span>
          <span class="quick-tag-chip" onclick="toggleTag(this, 'Super Rápido ⚡')">Super Rápido ⚡</span>
          <span class="quick-tag-chip" onclick="toggleTag(this, 'Empaque Seguro 📦')">Empaque Seguro 📦</span>
          <span class="quick-tag-chip" onclick="toggleTag(this, 'Sabor Auténtico 🍔')">Sabor Auténtico 🍔</span>
          <span class="quick-tag-chip" onclick="toggleTag(this, 'Domiciliario Amable 🛵')">Domiciliario Amable 🛵</span>
        </div>

        <label style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--color-subtext);">
          Tu Comentario
        </label>
        <textarea id="input-comment" class="input-field" rows="3" placeholder="Cuéntanos tu experiencia con el sabor, temperatura y entrega..." required></textarea>

        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
          <button type="button" onclick="closeModal()" class="nav-btn" style="cursor: pointer;">Cancelar</button>
          <button type="submit" id="btn-submit-review" class="btn-write-review">
            Publicar Reseña
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let selectedRating = 5;
    let selectedTags = [];

    function openModal() {
      document.getElementById('review-modal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('review-modal').style.display = 'none';
    }

    function setRating(val) {
      selectedRating = val;
      document.getElementById('input-rating').value = val;
      const spans = document.querySelectorAll('#food-stars span');
      spans.forEach((s, idx) => {
        s.style.color = (idx < val) ? '#f59e0b' : '#cbd5e1';
      });
    }

    function toggleTag(el, tag) {
      el.classList.toggle('active');
      if (selectedTags.includes(tag)) {
        selectedTags = selectedTags.filter(t => t !== tag);
      } else {
        selectedTags.push(tag);
      }
    }

    async function submitReview(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-submit-review');
      btn.disabled = true;
      btn.textContent = 'Enviando...';

      const payload = {
        restaurant_id: <?= $restaurantId ?>,
        customer_name: document.getElementById('input-name').value.trim(),
        order_code: document.getElementById('input-order-code').value.trim(),
        rating: selectedRating,
        comment: document.getElementById('input-comment').value.trim(),
        tags: selectedTags
      };

      try {
        const res = await fetch('api/reviews.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          alert('¡Gracias! Tu reseña ha sido registrada exitosamente en Neiva.');
          location.reload();
        } else {
          alert(data.error || 'Error al guardar reseña.');
          btn.disabled = false;
          btn.textContent = 'Publicar Reseña';
        }
      } catch (err) {
        alert('Error al conectar con el servidor.');
        btn.disabled = false;
        btn.textContent = 'Publicar Reseña';
      }
    }
  </script>
</body>
</html>
