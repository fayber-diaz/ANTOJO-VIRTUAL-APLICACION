<?php
/**
 * =========================================================
 * ANTOJO VIRTUAL — Portal Móvil para el Repartidor / Domiciliario
 * Optimizado para Smartphones • Neiva, Huila
 * Diseño Neumórfico (Soft UI)
 * =========================================================
 */

require_once __DIR__ . '/config/db.php';

$db = getDB();

// Consultar repartidor activo (Carlos Mendoza por defecto para demo)
$riderId = 1;
$stmtRider = $db->prepare("SELECT * FROM `riders` WHERE id = :id LIMIT 1");
$stmtRider->execute([':id' => $riderId]);
$rider = $stmtRider->fetch() ?: [
    'id' => 1,
    'name' => 'Carlos Mendoza',
    'phone' => '+57 312 458 9210',
    'vehicle' => 'Bajaj Pulsar 200 NS - Negra',
    'plate' => 'HUI-84E',
    'rating' => 4.9,
    'photo' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80',
    'current_street' => 'Av. Circunvalar con Calle 14, Neiva'
];

// Consultar pedidos activos para entrega en Neiva
$activeOrders = [];
try {
    $stmtOrders = $db->query("
        SELECT o.*, p.payment_method, p.status as payment_status, p.amount, p.bank_name,
               COALESCE(r.name, 'La Estación Burger') as rest_name,
               COALESCE(r.address, 'Cra 12 #10-34, Centro Neiva') as rest_address,
               COALESCE(r.phone, '+57 312 458 9210') as rest_phone
        FROM `orders` o
        LEFT JOIN `payments` p ON o.id = p.order_id
        LEFT JOIN `restaurants` r ON o.restaurant_id = r.id
        WHERE o.status IN ('recibido', 'preparando', 'en_camino')
        ORDER BY o.id DESC
    ");
    $activeOrders = $stmtOrders->fetchAll();

    // Si no hay pedidos en curso, traer el último para demostración
    if (empty($activeOrders)) {
        $stmtLast = $db->query("
            SELECT o.*, p.payment_method, p.status as payment_status, p.amount, p.bank_name,
                   COALESCE(r.name, 'La Estación Burger') as rest_name,
                   COALESCE(r.address, 'Cra 12 #10-34, Centro Neiva') as rest_address,
                   COALESCE(r.phone, '+57 312 458 9210') as rest_phone
            FROM `orders` o
            LEFT JOIN `payments` p ON o.id = p.order_id
            LEFT JOIN `restaurants` r ON o.restaurant_id = r.id
            ORDER BY o.id DESC LIMIT 1
        ");
        $activeOrders = $stmtLast->fetchAll();
    }
} catch (Exception $e) {
    // Fallback a consulta simple sin join de restaurantes por compatibilidad
    try {
        $stmtFallback = $db->query("
            SELECT o.*, p.payment_method, p.status as payment_status, p.amount, p.bank_name,
                   'La Estación Burger' as rest_name,
                   'Cra 12 #10-34, Centro Neiva' as rest_address,
                   '+57 312 458 9210' as rest_phone
            FROM `orders` o
            LEFT JOIN `payments` p ON o.id = p.order_id
            ORDER BY o.id DESC LIMIT 1
        ");
        $activeOrders = $stmtFallback->fetchAll();
    } catch (Exception $e2) {
        $activeOrders = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Rider App • Antojo Virtual Neiva</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  
  <style>
    :root {
      --neu-bg: #e5ebf3;
      --neu-extruded: 6px 6px 14px rgba(163, 177, 198, 0.6), -6px -6px 14px rgba(255, 255, 255, 0.95);
      --neu-extruded-sm: 3px 3px 8px rgba(163, 177, 198, 0.5), -3px -3px 8px rgba(255, 255, 255, 0.9);
      --neu-inset: inset 4px 4px 10px rgba(163, 177, 198, 0.6), inset -4px -4px 10px rgba(255, 255, 255, 0.95);
      --neu-inset-sm: inset 2px 2px 5px rgba(163, 177, 198, 0.5), inset -2px -2px 5px rgba(255, 255, 255, 0.9);
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

    body {
      background-color: var(--neu-bg);
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--color-text);
      max-width: 520px;
      margin: 0 auto;
      min-height: 100vh;
      padding-bottom: 5rem;
    }

    /* Header Móvil del Domiciliario */
    .rider-header {
      background: var(--neu-bg);
      box-shadow: 0 4px 16px rgba(163, 177, 198, 0.35);
      padding: 1rem 1.25rem;
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.6);
    }

    .rider-profile {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .rider-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 3px solid var(--color-accent);
      object-fit: cover;
      box-shadow: var(--neu-extruded-sm);
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background: #dcfce7;
      color: #15803d;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
    }

    .status-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #16a34a;
      animation: pulseDot 1.4s infinite;
    }

    @keyframes pulseDot {
      0% { transform: scale(0.9); opacity: 0.7; }
      50% { transform: scale(1.4); opacity: 1; }
      100% { transform: scale(0.9); opacity: 0.7; }
    }

    /* Contenedor Principal */
    .rider-main {
      padding: 1.25rem;
    }

    .rider-stat-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.75rem;
      margin-bottom: 1.5rem;
    }

    .rider-stat-card {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded-sm);
      border-radius: var(--radius-md);
      padding: 0.85rem 0.65rem;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .rider-stat-val {
      font-family: 'Epilogue', sans-serif;
      font-size: 1.25rem;
      font-weight: 900;
      color: var(--color-text);
    }

    .rider-stat-lbl {
      font-size: 0.7rem;
      color: var(--color-subtext);
      font-weight: 700;
      text-transform: uppercase;
      margin-top: 2px;
    }

    /* Tarjeta de Orden Activa */
    .order-delivery-card {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded);
      border-radius: var(--radius-lg);
      padding: 1.35rem;
      margin-bottom: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.8);
      position: relative;
      overflow: hidden;
    }

    .order-status-strip {
      display: inline-block;
      padding: 0.3rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 900;
      text-transform: uppercase;
      margin-bottom: 0.85rem;
    }

    .order-status-strip.recibido {
      background: #fef3c7;
      color: #b45309;
    }

    .order-status-strip.preparando {
      background: #fed7aa;
      color: #c2410c;
    }

    .order-status-strip.en_camino {
      background: #fee2e2;
      color: #b91c1c;
    }

    .order-status-strip.entregado {
      background: #dcfce7;
      color: #15803d;
    }

    .point-box {
      background: var(--neu-bg);
      box-shadow: var(--neu-inset-sm);
      border-radius: var(--radius-md);
      padding: 0.85rem 1rem;
      margin-bottom: 0.85rem;
      border: 1px solid rgba(255, 255, 255, 0.4);
    }

    .point-box-title {
      font-size: 0.7rem;
      text-transform: uppercase;
      font-weight: 800;
      color: var(--color-subtext);
      display: flex;
      align-items: center;
      gap: 0.35rem;
      margin-bottom: 0.25rem;
    }

    .point-box-name {
      font-size: 0.95rem;
      font-weight: 900;
      color: var(--color-text);
    }

    .point-box-sub {
      font-size: 0.8rem;
      color: var(--color-subtext);
      margin-top: 2px;
    }

    /* Botones de Navegación */
    .nav-buttons-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.65rem;
      margin: 1rem 0;
    }

    .btn-nav-action {
      background: var(--neu-bg);
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: var(--radius-md);
      padding: 0.75rem 0.5rem;
      font-size: 0.8rem;
      font-weight: 800;
      color: var(--color-text);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-nav-action:active {
      box-shadow: var(--neu-inset-sm);
      transform: translateY(1px);
    }

    /* Botones de Transición de Estados */
    .btn-status-step {
      width: 100%;
      border: none;
      border-radius: var(--radius-md);
      padding: 1rem;
      font-family: 'Epilogue', sans-serif;
      font-size: 0.95rem;
      font-weight: 900;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
      margin-top: 0.85rem;
      box-shadow: 4px 4px 12px rgba(0,0,0,0.15);
      color: #fff;
    }

    .btn-status-step.to-preparando {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .btn-status-step.to-en_camino {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .btn-status-step.to-entregado {
      background: linear-gradient(135deg, #10b981, #059669);
    }

    /* Barra Inferior Fija */
    .rider-bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      max-width: 520px;
      margin: 0 auto;
      background: var(--neu-bg);
      box-shadow: 0 -4px 16px rgba(163, 177, 198, 0.4);
      display: flex;
      justify-content: space-around;
      padding: 0.65rem 0.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.7);
      z-index: 100;
    }

    .bottom-nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.2rem;
      text-decoration: none;
      color: var(--color-subtext);
      font-size: 0.7rem;
      font-weight: 800;
      padding: 0.35rem 0.75rem;
      border-radius: var(--radius-md);
    }

    .bottom-nav-item.active {
      color: var(--color-primary);
      box-shadow: var(--neu-inset-sm);
    }

    .toast-alert {
      position: fixed;
      top: 75px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(15, 23, 42, 0.92);
      color: #fff;
      padding: 0.75rem 1.25rem;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 800;
      display: none;
      z-index: 999;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body>

  <!-- Alerta Toast -->
  <div id="rider-toast" class="toast-alert"></div>

  <!-- Encabezado del Domiciliario -->
  <header class="rider-header">
    <div class="rider-profile">
      <img src="<?= htmlspecialchars($rider['photo']) ?>" alt="<?= htmlspecialchars($rider['name']) ?>" class="rider-avatar">
      <div>
        <h3 style="font-family: 'Epilogue', sans-serif; font-size: 1.05rem; font-weight: 900; margin: 0;">
          <?= htmlspecialchars($rider['name']) ?>
        </h3>
        <span style="font-size: 0.72rem; color: var(--color-subtext); font-weight: 700;">
          <?= htmlspecialchars($rider['vehicle']) ?> (<?= htmlspecialchars($rider['plate']) ?>)
        </span>
      </div>
    </div>
    <div>
      <span class="status-badge">
        <span class="status-dot"></span>
        GPS Activo
      </span>
    </div>
  </header>

  <main class="rider-main">

    <!-- Tarjetas de Estadísticas Rápidas -->
    <div class="rider-stat-grid">
      <div class="rider-stat-card">
        <div class="rider-stat-val">⭐ <?= htmlspecialchars($rider['rating']) ?></div>
        <div class="rider-stat-lbl">Calificación</div>
      </div>
      <div class="rider-stat-card">
        <div class="rider-stat-val"><?= count($activeOrders) ?></div>
        <div class="rider-stat-lbl">En Curso</div>
      </div>
      <div class="rider-stat-card">
        <div class="rider-stat-val">412</div>
        <div class="rider-stat-lbl">Entregas</div>
      </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
      <h4 style="font-family: 'Epilogue', sans-serif; font-size: 1.15rem; font-weight: 900;">
        Tus Entregas Asignadas (Neiva)
      </h4>
      <button onclick="location.reload()" style="background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); border: none; padding: 0.4rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
        <span class="material-symbols-outlined" style="font-size: 16px;">refresh</span>
        Actualizar
      </button>
    </div>

    <?php if (empty($activeOrders)): ?>
      <div style="text-align: center; padding: 3rem 1.5rem; background: var(--neu-bg); box-shadow: var(--neu-inset); border-radius: var(--radius-lg);">
        <span class="material-symbols-outlined" style="font-size: 48px; color: var(--color-success);">done_all</span>
        <h3 style="margin: 0.5rem 0;">¡Todo al día, Carlos!</h3>
        <p style="font-size: 0.85rem; color: var(--color-subtext);">No tienes entregas pendientes en este momento en Neiva.</p>
      </div>
    <?php else: ?>
      <?php foreach ($activeOrders as $ord): 
          $status = $ord['status'];
          $totalFormatted = '$' . number_format($ord['total'], 0, ',', '.');
          $comuna = $ord['comuna'] ?: 'Comuna 4 - Centro';
          $destAddress = $ord['delivery_address'] ?: 'Cra 5 #18-42, Neiva';
          $destQuery = urlencode($destAddress . ', Neiva, Huila');
      ?>
        <div class="order-delivery-card" id="card-order-<?= $ord['id'] ?>">
          <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
              <span class="order-status-strip <?= $status ?>">
                ● <?= ucfirst(str_replace('_', ' ', $status)) ?>
              </span>
              <h3 style="font-family: 'Epilogue', sans-serif; font-size: 1.35rem; font-weight: 900; margin-bottom: 0.25rem;">
                Orden <?= htmlspecialchars($ord['order_code']) ?>
              </h3>
            </div>
            <div style="text-align: right;">
              <span style="font-size: 0.75rem; color: var(--color-subtext); font-weight: 800;">VALOR TOTAL</span>
              <div style="font-family: 'Epilogue', sans-serif; font-size: 1.2rem; font-weight: 900; color: var(--color-primary);">
                <?= $totalFormatted ?>
              </div>
            </div>
          </div>

          <!-- Punto A: Restaurante -->
          <div class="point-box">
            <div class="point-box-title">
              <span class="material-symbols-outlined" style="font-size: 16px; color: var(--color-accent);">storefront</span>
              Recoger en Restaurante
            </div>
            <div class="point-box-name"><?= htmlspecialchars($ord['rest_name'] ?: 'La Estación Burger') ?></div>
            <div class="point-box-sub"><?= htmlspecialchars($ord['rest_address'] ?: 'Cra 12 #10-34, Centro Neiva') ?></div>
          </div>

          <!-- Punto B: Cliente -->
          <div class="point-box">
            <div class="point-box-title">
              <span class="material-symbols-outlined" style="font-size: 16px; color: var(--color-primary);">home_pin</span>
              Entregar a Cliente
            </div>
            <div class="point-box-name"><?= htmlspecialchars($ord['customer_name']) ?></div>
            <div class="point-box-sub"><?= htmlspecialchars($destAddress) ?> (<?= htmlspecialchars($comuna) ?>)</div>
            <?php if (!empty($ord['instructions'])): ?>
              <div style="font-size: 0.75rem; color: #dc2626; font-weight: 700; margin-top: 4px;">
                📌 Nota: "<?= htmlspecialchars($ord['instructions']) ?>"
              </div>
            <?php endif; ?>
          </div>

          <!-- Estado del Pago -->
          <div style="background: rgba(255,255,255,0.5); padding: 0.65rem 0.85rem; border-radius: var(--radius-md); font-size: 0.8rem; margin-bottom: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="color: var(--color-subtext); font-weight: 700;">Método de Pago:</span>
            <?php if ($ord['payment_method'] === 'cash_efectivo'): ?>
              <span style="color: #b45309; font-weight: 900;">⚠️ Cobrar en Efectivo (<?= $totalFormatted ?>)</span>
            <?php else: ?>
              <span style="color: #15803d; font-weight: 900;">✓ Pagado Online (<?= htmlspecialchars($ord['bank_name'] ?: 'PSE / Tarjeta') ?>)</span>
            <?php endif; ?>
          </div>

          <!-- Botones de Navegación GPS (Google Maps & Waze) -->
          <div class="nav-buttons-grid">
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $destQuery ?>" target="_blank" class="btn-nav-action" style="color: #0284c7;">
              <span class="material-symbols-outlined" style="color: #0284c7;">near_me</span>
              Google Maps
            </a>
            <a href="https://waze.com/ul?q=<?= $destQuery ?>" target="_blank" class="btn-nav-action" style="color: #059669;">
              <span class="material-symbols-outlined" style="color: #059669;">navigation</span>
              Abrir en Waze
            </a>
          </div>

          <!-- Botones de Contacto con el Cliente -->
          <div class="nav-buttons-grid" style="margin-top: 0;">
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $ord['customer_phone']) ?>?text=<?= urlencode("Hola {$ord['customer_name']}, soy Carlos Mendoza tu domiciliario de Antojo Virtual con tu orden {$ord['order_code']}.") ?>" target="_blank" class="btn-nav-action" style="color: #15803d;">
              <span class="material-symbols-outlined" style="color: #15803d;">chat</span>
              WhatsApp
            </a>
            <a href="tel:<?= preg_replace('/\D/', '', $ord['customer_phone']) ?>" class="btn-nav-action">
              <span class="material-symbols-outlined">call</span>
              Llamar
            </a>
          </div>

          <!-- Botón de Transición según estado actual -->
          <?php if ($status === 'recibido'): ?>
            <button onclick="updateOrderStatus('<?= htmlspecialchars($ord['order_code']) ?>', 'preparando')" class="btn-status-step to-preparando">
              <span class="material-symbols-outlined">restaurant</span>
              1. Llegué al Restaurante (Confirmar Cocina)
            </button>
          <?php elseif ($status === 'preparando'): ?>
            <button onclick="updateOrderStatus('<?= htmlspecialchars($ord['order_code']) ?>', 'en_camino')" class="btn-status-step to-en_camino">
              <span class="material-symbols-outlined">moped</span>
              2. Recibí Paquete (Iniciar Ruta de Entrega)
            </button>
          <?php elseif ($status === 'en_camino'): ?>
            <button onclick="updateOrderStatus('<?= htmlspecialchars($ord['order_code']) ?>', 'entregado')" class="btn-status-step to-entregado">
              <span class="material-symbols-outlined">task_alt</span>
              3. ¡Entregado al Cliente en Mano!
            </button>
          <?php else: ?>
            <div style="text-align: center; color: var(--color-success); font-weight: 800; font-size: 0.9rem; margin-top: 0.5rem;">
              ✓ Orden finalizada exitosamente
            </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>

  <!-- Barra de Navegación Inferior -->
  <nav class="rider-bottom-nav">
    <a href="rider.php" class="bottom-nav-item active">
      <span class="material-symbols-outlined">two_wheeler</span>
      <span>Entregas</span>
    </a>
    <a href="index.php#rastreo" target="_blank" class="bottom-nav-item">
      <span class="material-symbols-outlined">map</span>
      <span>Ver Mapa Cliente</span>
    </a>
    <a href="admin.php" target="_blank" class="bottom-nav-item">
      <span class="material-symbols-outlined">dashboard</span>
      <span>Panel Admin</span>
    </a>
  </nav>

  <script>
    // Sintetizador de audio nativo para confirmaciones
    const playChime = () => {
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // A5
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
        osc.start();
        osc.stop(ctx.currentTime + 0.35);
      } catch(e) {}
    };

    function showToast(msg) {
      const toast = document.getElementById('rider-toast');
      toast.textContent = msg;
      toast.style.display = 'block';
      setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    async function updateOrderStatus(orderCode, newStatus) {
      try {
        const res = await fetch('api/admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'change_status',
            order_code: orderCode,
            status: newStatus
          })
        });
        const data = await res.json();
        if (data.success) {
          playChime();
          showToast(`¡Estado actualizado: ${newStatus.toUpperCase()}!`);
          setTimeout(() => { location.reload(); }, 800);
        } else {
          showToast(data.message || 'Error actualizando estado.');
        }
      } catch (err) {
        showToast('Error de conexión con el servidor.');
      }
    }
  </script>
</body>
</html>
