<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

// Procesar actualización de estado directa vía POST si se envía desde formulario
$actionMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    
    $validStatuses = ['recibido', 'preparando', 'en_camino', 'entregado', 'cancelado'];
    if ($orderId > 0 && in_array($newStatus, $validStatuses)) {
        try {
            $stmt = $db->prepare("UPDATE `orders` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id");
            $stmt->execute([':st' => $newStatus, ':id' => $orderId]);

            // Registrar evento en timeline
            $descMap = [
                'recibido' => 'Orden en cola de cocina.',
                'preparando' => 'Maestro parrillero asando la carne con carbón y leña en Neiva.',
                'en_camino' => 'Repartidor en ruta con termo térmico por las calles de Neiva.',
                'entregado' => 'Pedido entregado en puerta al cliente.',
                'cancelado' => 'Pedido cancelado.'
            ];
            $stmtEvt = $db->prepare("INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`) VALUES (:oid, :st, :ti, :de)");
            $stmtEvt->execute([
                ':oid' => $orderId,
                ':st' => $newStatus,
                ':ti' => 'Cambio de estado en cocina: ' . ucfirst($newStatus),
                ':de' => $descMap[$newStatus] ?? 'Actualización de estado.'
            ]);

            $actionMessage = "¡Orden #{$orderId} actualizada a: " . strtoupper($newStatus) . "!";
        } catch (Exception $e) {
            $actionMessage = "Error actualizando: " . $e->getMessage();
        }
    }
}

// Consultar estadísticas generales
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
$activeOrders = (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` IN ('recibido', 'preparando', 'en_camino')")->fetchColumn();
$deliveredOrders = (int)$db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'entregado'")->fetchColumn();
$totalRevenue = (int)$db->query("SELECT COALESCE(SUM(total), 0) FROM `orders` WHERE `status` != 'cancelado'")->fetchColumn();

// Consultar lista completa de órdenes con sus ítems, pagos y facturas
$stmt = $db->query("
    SELECT o.*, 
           r.name as rider_name, r.vehicle as rider_vehicle, r.plate as rider_plate,
           p.transaction_reference, p.authorization_code, p.bank_name, p.card_brand, p.card_last_four, p.status as p_status,
           inv.invoice_number, inv.cufe_code
    FROM `orders` o
    LEFT JOIN `riders` r ON o.rider_id = r.id
    LEFT JOIN `payments` p ON o.id = p.order_id
    LEFT JOIN `invoices` inv ON o.id = inv.order_id
    ORDER BY o.created_at DESC
    LIMIT 50
");
$orders = $stmt->fetchAll();

// Cargar ítems para cada orden
foreach ($orders as &$ord) {
    $stmtIt = $db->prepare("SELECT * FROM `order_items` WHERE `order_id` = :oid");
    $stmtIt->execute([':oid' => $ord['id']]);
    $items = $stmtIt->fetchAll();
    foreach ($items as &$it) {
        $it['toppings'] = json_decode($it['toppings_json'] ?? '[]', true);
        $it['sauces'] = json_decode($it['sauces_json'] ?? '[]', true);
    }
    $ord['items'] = $items;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Cocina & Pedidos (Neumorfismo) | ANTOJO VIRTUAL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .badge-status {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.4rem 0.85rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      box-shadow: var(--neu-inset-sm);
      border: 1px solid var(--color-border-subtle);
    }
    .status-recibido { background: var(--neu-bg); color: #192537; }
    .status-preparando { background: var(--neu-bg); color: #855300; border-color: rgba(254, 166, 25, 0.4); }
    .status-en_camino { background: var(--neu-bg); color: #dc2626; border-color: rgba(220, 38, 38, 0.4); }
    .status-entregado { background: var(--neu-bg); color: #00682e; border-color: rgba(26, 131, 64, 0.4); }
    .status-cancelado { background: var(--neu-bg); color: #93000a; }

    .action-btn-sm {
      border: none;
      padding: 0.55rem 1rem;
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 0.78rem;
      font-weight: 800;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      box-shadow: var(--neu-extruded-sm);
      border: 1px solid var(--color-border-subtle);
      transition: all 0.2s ease;
    }
    .action-btn-sm:active {
      box-shadow: var(--neu-inset-sm);
      transform: translateY(1px);
    }
    .btn-prep { background: var(--color-secondary-neu); color: #ffffff; }
    .btn-ship { background: var(--color-primary-neu); color: white; }
    .btn-done { background: linear-gradient(145deg, #1fa350, #146b34); color: white; }
  </style>
</head>
<body style="background: var(--neu-bg);">

  <!-- Header Superior Neumórfico -->
  <header class="header-top">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem 0;">
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <div class="brand-icon">
          <span class="material-symbols-outlined" style="font-size: 26px;">kitchen</span>
        </div>
        <div>
          <h1 style="font-family: var(--font-epilogue); font-size: 1.35rem; font-weight: 900; margin: 0; color: var(--color-on-surface);">
            Panel de Cocina & Pedidos
          </h1>
          <span style="font-size: 0.75rem; color: #dc2626; font-weight: 800; text-transform: uppercase;">
            ANTOJO VIRTUAL • Restaurantes Neiva (Soft UI)
          </span>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <a href="index.php" class="btn-customize" style="background: var(--neu-bg); color: var(--color-on-surface); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); text-decoration: none;">
          <span class="material-symbols-outlined" style="color: #dc2626;">storefront</span>
          Ver Aplicación Cliente
        </a>
        <a href="install.php" class="btn-customize" style="background: var(--neu-bg); color: var(--color-on-surface-variant); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); text-decoration: none;">
          <span class="material-symbols-outlined">settings</span>
          Base de Datos
        </a>
      </div>
    </div>
  </header>

  <main class="container" style="margin-top: 2rem; margin-bottom: 3.5rem;">

    <?php if ($actionMessage): ?>
      <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded); border: 1px solid rgba(26, 131, 64, 0.3); color: #00682e; padding: 1.15rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 800; display: flex; align-items: center; gap: 0.65rem;">
        <span class="material-symbols-outlined" style="color: #1a8340;">check_circle</span>
        <span><?php echo htmlspecialchars($actionMessage); ?></span>
      </div>
    <?php endif; ?>

    <!-- Métricas del Restaurante en Neiva Neumórficas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
      <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--color-border-subtle);">
        <div style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface-variant); text-transform: uppercase;">Total de Ventas (COP)</div>
        <div style="font-family: var(--font-epilogue); font-size: 1.95rem; font-weight: 900; color: #1a8340; margin-top: 0.35rem;">
          $<?php echo number_format($totalRevenue, 0, ',', '.'); ?>
        </div>
        <div style="font-size: 0.75rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">Facturado en Neiva</div>
      </div>

      <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--color-border-subtle);">
        <div style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface-variant); text-transform: uppercase;">Pedidos Activos</div>
        <div style="font-family: var(--font-epilogue); font-size: 1.95rem; font-weight: 900; color: #dc2626; margin-top: 0.35rem;">
          <?php echo $activeOrders; ?>
        </div>
        <div style="font-size: 0.75rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">En cola, cocción o ruta</div>
      </div>

      <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--color-border-subtle);">
        <div style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface-variant); text-transform: uppercase;">Pedidos Entregados</div>
        <div style="font-family: var(--font-epilogue); font-size: 1.95rem; font-weight: 900; color: var(--color-on-surface); margin-top: 0.35rem;">
          <?php echo $deliveredOrders; ?>
        </div>
        <div style="font-size: 0.75rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">Entregas completadas</div>
      </div>

      <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--color-border-subtle);">
        <div style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface-variant); text-transform: uppercase;">Total Registrados</div>
        <div style="font-family: var(--font-epilogue); font-size: 1.95rem; font-weight: 900; color: #855300; margin-top: 0.35rem;">
          <?php echo $totalOrders; ?>
        </div>
        <div style="font-size: 0.75rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">Historial total en MySQL</div>
      </div>
    </div>

    <!-- Lista de Órdenes Neumórfica -->
    <div style="background: var(--neu-bg); border-radius: var(--radius-lg); box-shadow: var(--neu-extruded-lg); border: 1px solid var(--color-border-subtle); padding: 2.25rem;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2 style="font-family: var(--font-epilogue); font-size: 1.45rem; font-weight: 800; color: var(--color-on-surface); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
          <span class="material-symbols-outlined" style="color: #dc2626;">order_approve</span>
          Gestión de Pedidos en Vivo (MySQL)
        </h2>
        <span style="font-size: 0.85rem; color: var(--color-on-surface-variant); font-weight: 700;">Mostrando los últimos 50 pedidos</span>
      </div>

      <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--color-on-surface-variant);">
          <span class="material-symbols-outlined" style="font-size: 48px; color: #fea619;">receipt_long</span>
          <p style="margin-top: 0.5rem;">No hay órdenes registradas todavía en la base de datos.</p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 2rem;">
          <?php foreach ($orders as $o): ?>
            <div style="border: 1px solid var(--color-border-subtle); border-radius: var(--radius-md); padding: 1.75rem; background: var(--neu-bg); box-shadow: var(--neu-extruded);">
              
              <!-- Cabecera de la orden -->
              <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                  <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <span style="font-family: var(--font-epilogue); font-size: 1.35rem; font-weight: 900; color: #dc2626;">
                      <?php echo htmlspecialchars($o['order_code']); ?>
                    </span>
                    <span class="badge-status status-<?php echo $o['status']; ?>">
                      ● <?php echo strtoupper($o['status']); ?>
                    </span>
                  </div>
                  <div style="font-size: 0.85rem; color: var(--color-on-surface-variant); margin-top: 0.35rem;">
                    <strong>Cliente:</strong> <?php echo htmlspecialchars($o['customer_name']); ?> • 
                    <strong>Tel:</strong> <a href="tel:<?php echo htmlspecialchars($o['customer_phone']); ?>" style="color: var(--color-on-surface); font-weight: 800;"><?php echo htmlspecialchars($o['customer_phone']); ?></a> • 
                    <strong>Fecha:</strong> <?php echo date('d/m/Y h:i A', strtotime($o['created_at'])); ?>
                  </div>
                </div>

                <div style="text-align: right;">
                  <div style="font-family: var(--font-epilogue); font-size: 1.45rem; font-weight: 900; color: var(--color-on-surface);">
                    $<?php echo number_format($o['total'], 0, ',', '.'); ?>
                  </div>
                  <div style="margin-top: 4px;">
                    <?php if (($o['payment_status'] ?? '') === 'paid' || ($o['p_status'] ?? '') === 'approved'): ?>
                      <span style="display: inline-flex; align-items: center; gap: 3px; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 800;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span>
                        PAGADO ONLINE
                      </span>
                    <?php else: ?>
                      <span style="display: inline-flex; align-items: center; gap: 3px; background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 800;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">hourglass_top</span>
                        CONTRA ENTREGA
                      </span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size: 0.72rem; color: var(--color-on-surface-variant); font-weight: 700; margin-top: 3px;">
                    <?php 
                      $pLabel = $o['bank_name'] ?: ($o['card_brand'] ? strtoupper($o['card_brand']) . ' •••• ' . $o['card_last_four'] : strtoupper($o['payment_method']));
                      echo htmlspecialchars($pLabel);
                    ?>
                  </div>
                  <?php if (!empty($o['transaction_reference'])): ?>
                    <div style="font-family: monospace; font-size: 0.68rem; color: #64748b;">
                      Ref: <?php echo htmlspecialchars($o['transaction_reference']); ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($o['invoice_number'])): ?>
                    <div style="font-size: 0.68rem; color: #0284c7; font-weight: 800;">
                      Fac: <?php echo htmlspecialchars($o['invoice_number']); ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Dirección y Notas -->
              <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border: 1px solid var(--color-border-subtle); padding: 0.95rem 1.25rem; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                  <span class="material-symbols-outlined" style="font-size: 16px; color: #dc2626;">pin_drop</span>
                  <strong>Destino en Neiva:</strong> <?php echo htmlspecialchars($o['delivery_address']); ?> (<?php echo htmlspecialchars($o['comuna']); ?>)
                </div>
                <?php if (!empty($o['instructions'])): ?>
                  <div style="color: #855300; font-weight: 800;">
                    📝 Nota: "<?php echo htmlspecialchars($o['instructions']); ?>"
                  </div>
                <?php endif; ?>
              </div>

              <!-- Ítems del pedido -->
              <div style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.8rem; font-weight: 900; color: var(--color-on-surface-variant); text-transform: uppercase; margin-bottom: 0.65rem;">
                  Platos Solicitados (<?php echo count($o['items']); ?> ítems)
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                  <?php foreach ($o['items'] as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.65rem 0.85rem; background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); border-radius: var(--radius-sm); border: 1px solid var(--color-border-subtle);">
                      <div>
                        <strong style="color: var(--color-on-surface);"><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <?php if (!empty($item['doneness'])): ?>
                          <span style="color: #dc2626; font-size: 0.75rem;">(<?php echo htmlspecialchars($item['doneness']); ?>)</span>
                        <?php endif; ?>
                        <?php if (!empty($item['bread'])): ?>
                          <span style="color: var(--color-on-surface-variant); font-size: 0.75rem;">• Pan: <?php echo htmlspecialchars($item['bread']); ?></span>
                        <?php endif; ?>

                        <?php if (!empty($item['toppings']) && is_array($item['toppings'])): ?>
                          <div style="font-size: 0.75rem; color: var(--color-on-surface); margin-top: 0.25rem;">
                            + Extras: <?php echo implode(', ', array_map(fn($t) => $t['name'] . ' ($' . number_format($t['price'], 0, ',', '.') . ')', $item['toppings'])); ?>
                          </div>
                        <?php endif; ?>

                        <?php if (!empty($item['sauces']) && is_array($item['sauces'])): ?>
                          <div style="font-size: 0.75rem; color: #1a8340;">
                            Salsas: <?php echo implode(', ', $item['sauces']); ?>
                          </div>
                        <?php endif; ?>
                      </div>
                      <span style="font-weight: 800; color: var(--color-on-surface);">$<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Repartidor y Botones de Avance de Estado -->
              <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--color-border-subtle); padding-top: 1.25rem;">
                <div style="font-size: 0.8rem; color: var(--color-on-surface-variant);">
                  <?php if (!empty($o['rider_name'])): ?>
                    <span>🛵 Repartidor: <strong style="color: var(--color-on-surface);"><?php echo htmlspecialchars($o['rider_name']); ?></strong> (<?php echo htmlspecialchars($o['rider_vehicle']); ?> - <?php echo htmlspecialchars($o['rider_plate']); ?>)</span>
                  <?php else: ?>
                    <span style="color: #fea619; font-weight: 800;">Repartidor por asignar</span>
                  <?php endif; ?>
                </div>

                <!-- Botones para cambiar estado en cocina -->
                <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                  <!-- Botón Ver Rastreo del Cliente -->
                  <a href="index.php#rastreo" onclick="localStorage.setItem('antojo_active_order_code', '<?php echo $o['order_code']; ?>');" class="action-btn-sm" style="background: var(--neu-bg); color: var(--color-on-surface); text-decoration: none;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">near_me</span>
                    Ver Rastreo Cliente
                  </a>

                  <!-- Formulario cambiar a preparando -->
                  <?php if ($o['status'] === 'recibido'): ?>
                    <form method="POST" style="margin: 0;">
                      <input type="hidden" name="action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                      <input type="hidden" name="status" value="preparando">
                      <button type="submit" class="action-btn-sm btn-prep">
                        <span class="material-symbols-outlined" style="font-size: 16px;">local_fire_department</span>
                        Encender Carbón (Preparar)
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Formulario cambiar a en camino -->
                  <?php if ($o['status'] === 'preparando' || $o['status'] === 'recibido'): ?>
                    <form method="POST" style="margin: 0;">
                      <input type="hidden" name="action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                      <input type="hidden" name="status" value="en_camino">
                      <button type="submit" class="action-btn-sm btn-ship">
                        <span class="material-symbols-outlined" style="font-size: 16px;">moped</span>
                        Despachar en Ruta
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Formulario cambiar a entregado -->
                  <?php if ($o['status'] === 'en_camino' || $o['status'] === 'preparando'): ?>
                    <form method="POST" style="margin: 0;">
                      <input type="hidden" name="action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                      <input type="hidden" name="status" value="entregado">
                      <button type="submit" class="action-btn-sm btn-done">
                        <span class="material-symbols-outlined" style="font-size: 16px;">done_all</span>
                        Marcar Entregado
                      </button>
                    </form>
                  <?php endif; ?>
                </div>

              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </main>

</body>
</html>
