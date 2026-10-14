<?php
require_once __DIR__ . '/config/db.php';
// Aseguramos que la base de datos esté lista
$db = getDB();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ANTOJO VIRTUAL | Comida Rápida & Parrilla en Neiva, Huila</title>
  <meta name="description" content="El epicentro digital de comida rápida, sabor callejero y parrilladas auténticas para Neiva, Huila. Entregas calientes y al instante en tu puerta.">
  
  <!-- Tipografías Oficiales de Antojo Virtual -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  
  <!-- Hojas de Estilos -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- =========================================================
       HEADER PRINCIPAL SUPERIOR
       ========================================================= -->
  <header class="header-top">
    <div class="container">
      <div class="header-container">
        <!-- Logo y Marca -->
        <a class="brand-logo" onclick="App.navigate('explorar')">
          <div class="brand-icon">
            <span class="material-symbols-outlined" style="font-size: 26px;">lunch_dining</span>
          </div>
          <div class="brand-text">
            <h1>ANTOJO VIRTUAL</h1>
            <span>Neiva, Huila • Sabor Opita</span>
          </div>
        </a>

        <!-- Selector de Comuna de Neiva -->
        <div class="comuna-badge">
          <span class="material-symbols-outlined" style="color: #dc2626; font-size: 20px;">location_on</span>
          <select id="header-comuna-select" class="comuna-select">
            <option value="Comuna 4 - Centro">Comuna 4 - Centro</option>
            <option value="Comuna 1 - Cándido / Camilo Torres">Comuna 1 - Cándido</option>
            <option value="Comuna 2 - Las Granjas / Los Mártires">Comuna 2 - Las Granjas</option>
            <option value="Comuna 3 - Quirinal / Santa Inés">Comuna 3 - Quirinal</option>
            <option value="Comuna 5 - Altico / La Floresta">Comuna 5 - Altico</option>
            <option value="Comuna 6 - Timanco / Puertas del Sol">Comuna 6 - Timanco</option>
            <option value="Comuna 7 - Ipanema / Las Brisas">Comuna 7 - Ipanema</option>
            <option value="Comuna 8 - Las Américas">Comuna 8 - Las Américas</option>
            <option value="Comuna 9 - Luis Carlos Galán">Comuna 9 - Galán</option>
            <option value="Comuna 10 - Las Palmas">Comuna 10 - Las Palmas</option>
          </select>
        </div>

        <!-- Barra de Búsqueda Instantánea -->
        <div class="search-wrapper">
          <span class="material-symbols-outlined search-icon">search</span>
          <input type="text" id="search-input" class="search-input" placeholder="Buscar hamburguesas, salchipapas, restaurantes...">
        </div>

        <!-- Acciones del Header -->
        <div class="header-actions">
          <a href="admin.php" class="btn-admin-link" title="Ir al panel de cocina y pedidos">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #dc2626;">kitchen</span>
            <span>Panel Cocina</span>
          </a>

          <button class="btn-cart" onclick="App.navigate('checkout')">
            <span class="material-symbols-outlined" style="font-size: 20px;">shopping_bag</span>
            <span>Carrito</span>
            <div id="cart-badge-count" class="cart-count-badge">0</div>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- =========================================================
       RIBBON DE NAVEGACIÓN DE PANTALLAS (SWITCHER RÁPIDO)
       ========================================================= -->
  <nav class="nav-ribbon">
    <div class="container">
      <div class="nav-ribbon-inner">
        <div class="screen-tabs">
          <button class="tab-btn active" data-screen="explorar" onclick="App.navigate('explorar')">
            <span class="material-symbols-outlined" style="font-size: 18px;">explore</span>
            <span>1. Explorar Menú</span>
          </button>
          <button class="tab-btn" data-screen="checkout" onclick="App.navigate('checkout')">
            <span class="material-symbols-outlined" style="font-size: 18px;">receipt_long</span>
            <span>2. Mi Pedido & Pago</span>
          </button>
          <button class="tab-btn" data-screen="rastreo" onclick="App.navigate('rastreo')">
            <span class="material-symbols-outlined" style="font-size: 18px;">near_me</span>
            <span>3. Rastreo en Vivo</span>
          </button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <button class="tab-btn" style="background: #fff8eb; color: #855300; border: 1px solid #fea619;" onclick="App.openCustomizer(1)">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #dc2626;">tune</span>
            <span>Probar Personalizador</span>
          </button>
          <a href="install.php" style="font-size: 0.8rem; color: #5c403c; text-decoration: none; display: flex; align-items: center; gap: 0.2rem;" title="Verificar estado de MySQL y base de datos">
            <span class="material-symbols-outlined" style="font-size: 16px; color: #1a8340;">check_circle</span>
            <span>MySQL XAMPP Conectado</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- =========================================================
       PANTALLA 1: EXPLORAR MENÚ & RESTAURANTES
       ========================================================= -->
  <main id="screen-explorar" class="container">
    <!-- Hero Banner -->
    <section class="hero-banner">
      <div class="hero-content">
        <div class="hero-pill">
          <span class="material-symbols-outlined" style="font-size: 16px;">local_shipping</span>
          Entregas Calientes en Neiva • 25 a 35 min
        </div>
        <h2 class="hero-title">
          ¡El auténtico sabor huilense <span>directo a tu puerta!</span>
        </h2>
        <p class="hero-desc">
          Parrillas al carbón, carnes seleccionadas, quesos asados de Algeciras y las salchipapas más monumentales de la capital del Huila.
        </p>

        <!-- Caja de cupón promocional copiable -->
        <div class="coupon-hero-box">
          <span class="material-symbols-outlined" style="color: #fea619;">loyalty</span>
          <span style="font-size: 0.85rem; color: #ffffff;">Cupón Huila:</span>
          <span class="coupon-code-text">ANTOJONEIVA5K</span>
          <button id="btn-hero-copy-coupon" class="btn-copy-coupon">
            Copiar (-$5.000 COP)
          </button>
        </div>
      </div>
    </section>

    <!-- Selector / Slider de Categorías -->
    <section class="categories-slider">
      <button class="cat-chip active" data-cat="all" onclick="App.filterByCategory('all')">
        <span class="material-symbols-outlined" style="font-size: 20px;">restaurant</span>
        Todos los Antojos
      </button>
      <button class="cat-chip" data-cat="burgers" onclick="App.filterByCategory('burgers')">
        <span class="material-symbols-outlined" style="font-size: 20px;">lunch_dining</span>
        Hamburguesas al Carbón
      </button>
      <button class="cat-chip" data-cat="salchipapas" onclick="App.filterByCategory('salchipapas')">
        <span class="material-symbols-outlined" style="font-size: 20px;">fastfood</span>
        Salchipapas Monumentales
      </button>
      <button class="cat-chip" data-cat="arepas" onclick="App.filterByCategory('arepas')">
        <span class="material-symbols-outlined" style="font-size: 20px;">bakery_dining</span>
        Arepas & Patacones
      </button>
      <button class="cat-chip" data-cat="perros" onclick="App.filterByCategory('perros')">
        <span class="material-symbols-outlined" style="font-size: 20px;">kebab_dining</span>
        Perros Calientes
      </button>
      <button class="cat-chip" data-cat="desgranados" onclick="App.filterByCategory('desgranados')">
        <span class="material-symbols-outlined" style="font-size: 20px;">grain</span>
        Desgranados & Mazorca
      </button>
    </section>

    <!-- Sección: Restaurantes Aliados en Neiva -->
    <section style="margin-top: 1.5rem;">
      <div class="section-header">
        <h2 class="section-title">
          <span class="material-symbols-outlined" style="color: #dc2626;">storefront</span>
          Restaurantes Aliados en Neiva
        </h2>
        <span style="font-size: 0.85rem; color: #5c403c; font-weight: 600;">Cocinas 100% Verificadas</span>
      </div>
      <div id="restaurants-container" class="dishes-grid">
        <!-- Render dinámico desde MySQL via app.js -->
        <div style="text-align: center; padding: 2rem; color: #5c403c;">Cargando restaurantes desde MySQL...</div>
      </div>
    </section>

    <!-- Sección: Platos y Especialidades Populares -->
    <section style="margin-top: 3rem; margin-bottom: 3rem;">
      <div class="section-header">
        <h2 class="section-title">
          <span class="material-symbols-outlined" style="color: #fea619;">local_fire_department</span>
          Platos Estrella & Antojos de la Casa
        </h2>
        <span style="font-size: 0.85rem; color: #5c403c; font-weight: 600;">Personaliza carne, pan y salsas</span>
      </div>
      <div id="dishes-container" class="dishes-grid">
        <!-- Render dinámico desde MySQL via app.js -->
        <div style="text-align: center; padding: 2rem; color: #5c403c;">Cargando platos desde MySQL...</div>
      </div>
    </section>
  </main>

  <!-- =========================================================
       PANTALLA 2: CHECKOUT & PAGO (PASO 2)
       ========================================================= -->
  <section id="screen-checkout" class="container checkout-view" style="display: none;">
    <!-- Stepper de Progreso -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; padding: 1.25rem; background: #ffffff; border-radius: 1rem; border: 1px solid var(--color-border);">
      <div style="display: flex; align-items: center; gap: 0.75rem; color: #1a8340;">
        <span class="material-symbols-outlined" style="font-size: 28px;">check_circle</span>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800;">Paso 1</div>
          <div style="font-weight: 800; font-size: 0.95rem;">Carrito & Toppings</div>
        </div>
      </div>
      <div style="flex: 1; height: 3px; background: #1a8340; margin: 0 1.5rem; border-radius: 9999px;"></div>
      <div style="display: flex; align-items: center; gap: 0.75rem; color: #dc2626;">
        <div style="width: 32px; height: 32px; border-radius: 9999px; background: #dc2626; color: white; display: flex; align-items: center; justify-content: center; font-weight: 900;">2</div>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800;">Paso 2 (Activo)</div>
          <div style="font-weight: 800; font-size: 0.95rem;">Entrega & Pago</div>
        </div>
      </div>
      <div style="flex: 1; height: 3px; background: #d8e3fb; margin: 0 1.5rem; border-radius: 9999px;"></div>
      <div style="display: flex; align-items: center; gap: 0.75rem; opacity: 0.5;">
        <div style="width: 32px; height: 32px; border-radius: 9999px; background: #d8e3fb; color: #5c403c; display: flex; align-items: center; justify-content: center; font-weight: 800;">3</div>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Paso 3</div>
          <div style="font-weight: 700; font-size: 0.95rem;">Rastreo en Vivo</div>
        </div>
      </div>
    </div>

    <div class="checkout-grid">
      <!-- Columna Izquierda: Items de la Orden + Formulario -->
      <div>
        <!-- Caja de Items en Carrito -->
        <div class="checkout-card">
          <h3 class="checkout-card-title">
            <span class="material-symbols-outlined" style="color: #dc2626;">lunch_dining</span>
            Detalle de tus Antojos
          </h3>
          <div id="checkout-items-list">
            <!-- Render dinámico desde app.js -->
          </div>
          <div style="margin-top: 1rem; text-align: right;">
            <button onclick="App.navigate('explorar')" style="background: none; border: none; color: #dc2626; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
              + Agregar más productos del menú
            </button>
          </div>
        </div>

        <!-- Caja de Datos de Entrega -->
        <div class="checkout-card">
          <h3 class="checkout-card-title">
            <span class="material-symbols-outlined" style="color: #1a8340;">pin_drop</span>
            Dirección de Entrega en Neiva
          </h3>
          <div class="form-group">
            <label class="form-label">Nombre de quien recibe</label>
            <input type="text" id="customer-name" class="form-input" value="Mateo Rodríguez">
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label">Teléfono Móvil (WhatsApp)</label>
              <input type="tel" id="customer-phone" class="form-input" value="+57 314 289 9012">
            </div>
            <div class="form-group">
              <label class="form-label">Comuna de Neiva</label>
              <select id="delivery-comuna" class="form-select">
                <option value="Comuna 4 - Centro" selected>Comuna 4 - Centro</option>
                <option value="Comuna 1 - Cándido / Camilo Torres">Comuna 1 - Cándido</option>
                <option value="Comuna 2 - Las Granjas / Los Mártires">Comuna 2 - Las Granjas</option>
                <option value="Comuna 3 - Quirinal / Santa Inés">Comuna 3 - Quirinal</option>
                <option value="Comuna 5 - Altico / La Floresta">Comuna 5 - Altico</option>
                <option value="Comuna 6 - Timanco / Puertas del Sol">Comuna 6 - Timanco</option>
                <option value="Comuna 7 - Ipanema / Las Brisas">Comuna 7 - Ipanema</option>
                <option value="Comuna 8 - Las Américas">Comuna 8 - Las Américas</option>
                <option value="Comuna 9 - Luis Carlos Galán">Comuna 9 - Galán</option>
                <option value="Comuna 10 - Las Palmas">Comuna 10 - Las Palmas</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Dirección exacta (Calle, Carrera, Barrio)</label>
            <input type="text" id="delivery-address" class="form-input" value="Cra. 5 #18-42, Centro">
          </div>
          <div class="form-group">
            <label class="form-label">Instrucciones para el repartidor (Opcional)</label>
            <input type="text" id="delivery-instructions" class="form-input" placeholder="Ej: Dejar en portería con don Hernando, torre 2 apto 401..." value="Dejar en recepción con portero don Hernando">
          </div>
        </div>

        <!-- Caja de Método de Pago -->
        <div class="checkout-card">
          <h3 class="checkout-card-title">
            <span class="material-symbols-outlined" style="color: #fea619;">payments</span>
            Método de Pago
          </h3>
          <div class="payment-methods-grid">
            <div class="pay-card active" data-method="nequi">
              <span class="material-symbols-outlined" style="font-size: 26px;">account_balance_wallet</span>
              <span style="font-size: 0.85rem; font-weight: 700;">Nequi</span>
            </div>
            <div class="pay-card" data-method="daviplata">
              <span class="material-symbols-outlined" style="font-size: 26px;">phone_android</span>
              <span style="font-size: 0.85rem; font-weight: 700;">Daviplata</span>
            </div>
            <div class="pay-card" data-method="pse">
              <span class="material-symbols-outlined" style="font-size: 26px;">account_balance</span>
              <span style="font-size: 0.85rem; font-weight: 700;">PSE</span>
            </div>
            <div class="pay-card" data-method="card">
              <span class="material-symbols-outlined" style="font-size: 26px;">credit_card</span>
              <span style="font-size: 0.85rem; font-weight: 700;">Tarjeta</span>
            </div>
            <div class="pay-card" data-method="cash_efectivo">
              <span class="material-symbols-outlined" style="font-size: 26px;">attach_money</span>
              <span style="font-size: 0.85rem; font-weight: 700;">Efectivo</span>
            </div>
            <div class="pay-card" data-method="cash_datafono">
              <span class="material-symbols-outlined" style="font-size: 26px;">point_of_sale</span>
              <span style="font-size: 0.85rem; font-weight: 700;">Datáfono</span>
            </div>
          </div>

          <!-- Selector de cambio en efectivo -->
          <div id="cash-change-box" style="display: none; background: #fff8eb; padding: 1rem; border-radius: 0.75rem; border: 1px solid #fea619;">
            <label class="form-label" style="color: #855300;">¿Con cuánto vas a pagar en efectivo?</label>
            <select id="cash-change-select" class="form-select">
              <option value="exacto">Pago exacto (No requiero cambio)</option>
              <option value="50000">Billete de $50.000 COP</option>
              <option value="100000">Billete de $100.000 COP</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Resumen de Totales y Cupón -->
      <div>
        <div class="order-summary-card">
          <h3 class="checkout-card-title">
            <span class="material-symbols-outlined" style="color: #111c2d;">receipt</span>
            Resumen de la Orden
          </h3>

          <!-- Formulario de cupón -->
          <div style="margin-bottom: 1.5rem; background: #f0f3ff; padding: 1rem; border-radius: 0.85rem;">
            <label class="form-label" style="font-size: 0.8rem;">¿Tienes un cupón de descuento?</label>
            <div style="display: flex; gap: 0.5rem;">
              <input type="text" id="coupon-input" class="form-input" placeholder="Ej: ANTOJONEIVA5K" style="text-transform: uppercase;">
              <button onclick="App.applyCouponCode()" class="btn-customize" style="border-radius: 0.85rem;">
                Aplicar
              </button>
            </div>
            <div id="coupon-applied-tag" style="display: none; align-items: center; justify-content: space-between; margin-top: 0.5rem; background: #e7ffe5; color: #00682e; padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 700;">
              <span>✓ Cupón <strong id="coupon-applied-code"></strong> activo</span>
              <button onclick="App.removeCoupon()" style="background: none; border: none; color: #dc2626; cursor: pointer; font-weight: 800;">✕</button>
            </div>
          </div>

          <!-- Filas de totales -->
          <div class="summary-row">
            <span>Subtotal de productos y adiciones</span>
            <strong id="summary-subtotal" style="color: #111c2d;">$0</strong>
          </div>
          <div id="summary-discount-row" class="summary-row" style="color: #00682e; display: none;">
            <span>Descuento promocional</span>
            <strong id="summary-discount">-$0</strong>
          </div>
          <div class="summary-row">
            <span>Domicilio local en Neiva</span>
            <strong id="summary-delivery">$3.500</strong>
          </div>
          <div class="summary-row">
            <span>Tasa de apoyo al repartidor</span>
            <strong id="summary-tip">$1.000</strong>
          </div>

          <!-- Total a pagar -->
          <div class="summary-row summary-total">
            <span>Total a Pagar</span>
            <span id="summary-total" style="color: #dc2626;">$0</span>
          </div>

          <!-- Botón de Confirmación -->
          <button id="btn-confirm-order-action" class="btn-confirm-order" onclick="App.submitOrder()">
            <span class="material-symbols-outlined">check_circle</span>
            <span>Confirmar Pedido • </span>
            <span id="btn-confirm-total">$0</span>
          </button>

          <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; font-size: 0.75rem; color: #5c403c; margin-top: 1rem;">
            <span class="material-symbols-outlined" style="font-size: 16px; color: #1a8340;">lock</span>
            <span>Transacción segura y directa con el restaurante en Neiva</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =========================================================
       PANTALLA 3: RASTREO EN VIVO (LIVE TRACKING)
       ========================================================= -->
  <section id="screen-rastreo" class="container" style="display: none; margin: 2rem auto;">
    <div style="background: #ffffff; border-radius: 1.5rem; border: 1px solid var(--color-border); padding: 2rem; box-shadow: var(--shadow-md);">
      
      <!-- Encabezado del Pedido -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1.5rem;">
        <div>
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
            <span class="material-symbols-outlined" style="color: #dc2626;">moped</span>
            <span style="font-size: 0.85rem; font-weight: 700; color: #5c403c; text-transform: uppercase;">Rastreo en Tiempo Real</span>
          </div>
          <h2 style="font-family: var(--font-epilogue); font-size: 1.85rem; font-weight: 900; color: #111c2d; margin: 0;">
            Pedido <span id="track-order-code" style="color: #dc2626;">#NV-8492</span>
          </h2>
          <p id="track-status-sub" style="color: #5c403c; font-size: 0.95rem; margin-top: 0.25rem;">
            El repartidor va en ruta por la Av. Circunvalar de Neiva hacia tu ubicación.
          </p>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem;">
          <div style="background: #fff8eb; border: 1px solid #fea619; padding: 0.65rem 1.25rem; border-radius: 1rem; text-align: right;">
            <div style="font-size: 0.75rem; font-weight: 700; color: #855300; text-transform: uppercase;">Tiempo Estimado</div>
            <div id="track-eta" style="font-family: var(--font-epilogue); font-size: 1.4rem; font-weight: 900; color: #b70011;">15-20 min</div>
          </div>
          <button onclick="App.autoAdvanceDemoOrder()" class="btn-customize" style="background: #111c2d; font-size: 0.8rem; padding: 0.65rem 1rem;" title="Simular que el pedido avanza de etapa">
            <span class="material-symbols-outlined" style="font-size: 16px; color: #fea619;">fast_forward</span>
            Simular Avance
          </button>
        </div>
      </div>

      <!-- Barra de Estado Visual (Stepper de 4 Pasos) -->
      <div class="tracking-stepper">
        <div class="tracking-line">
          <div id="track-progress-bar" class="tracking-line-progress" style="width: 55%;"></div>
        </div>

        <!-- Paso 0 -->
        <div id="track-step-0" class="tracking-step done">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">assignment_turned_in</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 700; color: #111c2d;">Confirmado</span>
          <span style="font-size: 0.7rem; color: #5c403c;">Recibido</span>
        </div>

        <!-- Paso 1 -->
        <div id="track-step-1" class="tracking-step active">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">local_fire_department</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 700; color: #dc2626;">Al Carbón</span>
          <span style="font-size: 0.7rem; color: #5c403c;">En Cocina</span>
        </div>

        <!-- Paso 2 -->
        <div id="track-step-2" class="tracking-step">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">two_wheeler</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 700; color: #111c2d;">En Camino</span>
          <span style="font-size: 0.7rem; color: #5c403c;">Rider en ruta</span>
        </div>

        <!-- Paso 3 -->
        <div id="track-step-3" class="tracking-step">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">done_all</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 700; color: #111c2d;">¡Entregado!</span>
          <span style="font-size: 0.7rem; color: #5c403c;">En tu puerta</span>
        </div>
      </div>

      <!-- Estado Destacado -->
      <div style="background: #f0f3ff; border-radius: 1rem; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          <span class="material-symbols-outlined" style="color: #dc2626; font-size: 28px;">restaurant_menu</span>
          <div>
            <div id="track-status-title" style="font-weight: 800; font-size: 1.05rem; color: #111c2d;">Carbón al Rojo Vivo & Cocción</div>
            <div style="font-size: 0.8rem; color: #5c403c;">El restaurante en Neiva prepara tu orden con los mejores ingredientes.</div>
          </div>
        </div>
        <span style="font-size: 0.8rem; background: #ffffff; padding: 0.35rem 0.85rem; border-radius: 9999px; font-weight: 700; color: #1a8340; border: 1px solid #79db8d;">
          ● Conexión Activa MySQL
        </span>
      </div>

      <!-- Grilla: Mapa de Neiva y Tarjeta del Repartidor -->
      <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem;">
        <!-- Mapa interactivo de Neiva -->
        <div>
          <h4 style="font-family: var(--font-epilogue); font-size: 1.1rem; font-weight: 800; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
            <span class="material-symbols-outlined" style="color: #dc2626;">map</span>
            Mapa de Ruta en Vivo (Neiva, Huila)
          </h4>
          <div class="map-canvas-container">
            <canvas id="neiva-map-canvas"></canvas>
          </div>
        </div>

        <!-- Tarjeta del Repartidor (Rider) -->
        <div>
          <h4 style="font-family: var(--font-epilogue); font-size: 1.1rem; font-weight: 800; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
            <span class="material-symbols-outlined" style="color: #fea619;">person_pin</span>
            Tu Repartidor Asignado
          </h4>
          <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: 1.25rem; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
              <img id="track-rider-img" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80" alt="Rider" style="width: 64px; height: 64px; border-radius: 9999px; object-fit: cover; border: 3px solid #fea619;">
              <div>
                <h5 id="track-rider-name" style="font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 800; color: #111c2d; margin: 0;">Carlos Mendoza</h5>
                <div style="display: flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; font-weight: 700; color: #855300; margin-top: 0.2rem;">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: #fea619;">star</span>
                  <span id="track-rider-rating">4.9</span> (350+ entregas en Neiva)
                </div>
                <div id="track-rider-vehicle" style="font-size: 0.8rem; color: #5c403c;">Bajaj Pulsar 200 NS - Negra (HUI-84E)</div>
              </div>
            </div>

            <div style="background: #f0f3ff; padding: 0.75rem 1rem; border-radius: 0.75rem; font-size: 0.85rem; margin-bottom: 1.25rem;">
              <strong>Ubicación actual:</strong>
              <div id="track-rider-street" style="color: #dc2626; font-weight: 700; margin-top: 0.2rem;">
                Av. Circunvalar con Calle 10
              </div>
            </div>

            <!-- Acciones de contacto directo -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
              <a id="track-rider-whatsapp" href="#" target="_blank" class="btn-customize" style="background: #1a8340; justify-content: center; text-decoration: none;">
                <span class="material-symbols-outlined">chat</span>
                WhatsApp
              </a>
              <a id="track-rider-call" href="#" class="btn-customize" style="background: #111c2d; justify-content: center; text-decoration: none;">
                <span class="material-symbols-outlined">call</span>
                Llamar
              </a>
            </div>
          </div>

          <!-- Resumen breve de entrega -->
          <div style="background: #f9f9ff; border: 1px solid var(--color-border); border-radius: 1.25rem; padding: 1.25rem; margin-top: 1rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem;">
              <span style="color: #5c403c;">Dirección de destino:</span>
              <strong id="track-order-address" style="text-align: right; max-width: 60%;">Cra. 5 #18-42, Centro</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: 800; border-top: 1px dashed var(--color-border); padding-top: 0.5rem;">
              <span>Total Cancelado / A Cobrar:</span>
              <span id="track-order-total" style="color: #dc2626;">$40.400</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Ítems del pedido en rastreo -->
      <div style="margin-top: 2rem; border-top: 1px solid var(--color-border); padding-top: 1.5rem;">
        <h4 style="font-family: var(--font-epilogue); font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">
          Ítems incluidos en esta entrega
        </h4>
        <div id="track-order-items">
          <!-- Render dinámico desde app.js -->
        </div>
      </div>

    </div>
  </section>

  <!-- =========================================================
       MODAL DE PERSONALIZACIÓN INTERACTIVO (SCREEN 3)
       ========================================================= -->
  <div id="customizer-modal" class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <div>
          <span style="font-size: 0.75rem; color: #fea619; text-transform: uppercase; font-weight: 800;" id="modal-dish-rest">
            La Estación Burger
          </span>
          <h2 id="modal-dish-title" style="font-family: var(--font-epilogue); font-size: 1.35rem; font-weight: 900; margin: 0.2rem 0;">
            Hamburguesa Monumental Huilense 200g
          </h2>
          <span style="font-size: 0.8rem; color: #d8e3fb;">Personaliza a tu gusto con ingredientes frescos</span>
        </div>
        <button class="modal-close-btn" onclick="App.closeCustomizer()">✕</button>
      </div>

      <div class="modal-body">
        <!-- Sección 1: Término de la Carne -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>1. Término de la Carne al Carbón</span>
            <span style="font-size: 0.75rem; color: #dc2626; font-weight: 700;">Obligatorio</span>
          </div>
          <div class="modal-section-sub">Selecciona la jugosidad deseada en la parrilla</div>
          <div id="modal-terminos-list">
            <!-- Dinámico desde MySQL -->
          </div>
        </div>

        <!-- Sección 2: Tipo de Pan Artesanal -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>2. Selección de Pan Artesanal</span>
            <span style="font-size: 0.75rem; color: #dc2626; font-weight: 700;">Obligatorio</span>
          </div>
          <div class="modal-section-sub">Horneado fresco a diario en panadería local</div>
          <div id="modal-panes-list">
            <!-- Dinámico desde MySQL -->
          </div>
        </div>

        <!-- Sección 3: Toppings Extras con Contador -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>3. Toppings & Adiciones Especiales</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 700;">Opcionales</span>
          </div>
          <div class="modal-section-sub">Agrega tocineta, quesos asados o doble carne</div>
          <div id="modal-toppings-list">
            <!-- Dinámico desde MySQL -->
          </div>
        </div>

        <!-- Sección 4: Salsas Colombianas de la Casa -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>4. Salsas Artesanales de la Casa</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 700;">Gratis</span>
          </div>
          <div class="modal-section-sub">Elige las salsas que acompañarán tu orden</div>
          <div id="modal-salsas-list">
            <!-- Dinámico desde MySQL -->
          </div>
        </div>

        <!-- Sección 5: Notas Especiales -->
        <div class="modal-section" style="border-bottom: none;">
          <div class="modal-section-title">
            <span>5. Notas para el Maestro Parrillero</span>
          </div>
          <div class="modal-section-sub">¿Sin cebolla? ¿Salsa aparte? Escríbelo aquí:</div>
          <textarea id="modal-notes-input" class="form-textarea" rows="2" placeholder="Ej: Sin cebolla, salsas en recipientes separados por favor..."></textarea>
        </div>
      </div>

      <!-- Footer del Modal -->
      <div class="modal-footer">
        <div class="modal-total-display">
          <span class="modal-total-lbl">Total con adiciones:</span>
          <span id="modal-total-display" class="modal-total-price">$24.900</span>
        </div>
        <button class="btn-add-modal" onclick="App.confirmAddToCartFromModal()">
          <span class="material-symbols-outlined">add_shopping_cart</span>
          <span>Agregar al Pedido</span>
        </button>
      </div>
    </div>
  </div>

  <!-- =========================================================
       FOOTER INFERIOR DEL SITIO
       ========================================================= -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
            <div class="brand-icon" style="width: 36px; height: 36px;">
              <span class="material-symbols-outlined" style="font-size: 20px;">lunch_dining</span>
            </div>
            <h3 style="font-family: var(--font-epilogue); font-size: 1.25rem; font-weight: 900; color: white;">ANTOJO VIRTUAL</h3>
          </div>
          <p style="color: #d8e3fb; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
            El epicentro gastronómico digital para la ciudad de Neiva, Huila. Conectamos los mejores parrilleros y maestros de comida rápida con los hogares opitas.
          </p>
          <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #fea619;">
            <span class="material-symbols-outlined">verified</span>
            <span>Desarrollado para XAMPP (PHP 8.2 + MariaDB/MySQL)</span>
          </div>
        </div>

        <div class="footer-col">
          <h4>Cobertura Neiva</h4>
          <ul>
            <li><a href="#comuna4">Comuna 4 - Centro & Altico</a></li>
            <li><a href="#comuna1">Comuna 1 - Cándido</a></li>
            <li><a href="#comuna2">Comuna 2 - Las Granjas</a></li>
            <li><a href="#comuna3">Comuna 3 - Quirinal</a></li>
            <li><a href="#comuna7">Comuna 7 - Ipanema</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Navegación</h4>
          <ul>
            <li><a onclick="App.navigate('explorar')" style="cursor:pointer;">Explorar Menú</a></li>
            <li><a onclick="App.navigate('checkout')" style="cursor:pointer;">Mi Carrito</a></li>
            <li><a onclick="App.navigate('rastreo')" style="cursor:pointer;">Rastreo en Vivo</a></li>
            <li><a href="admin.php">Panel de Restaurante</a></li>
            <li><a href="install.php">Estado de Base de Datos</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Atención & Soporte</h4>
          <p style="font-size: 0.85rem; color: #d8e3fb; margin-bottom: 0.5rem;">
            Horario: Lunes a Domingo<br>
            <strong>5:00 PM – 12:00 AM</strong>
          </p>
          <p style="font-size: 0.85rem; color: #fea619; font-weight: 700;">
            WhatsApp Soporte: +57 314 289 9012
          </p>
        </div>
      </div>

      <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1.5rem; text-align: center; font-size: 0.8rem; color: #94a3b8;">
        © <?php echo date('Y'); ?> ANTOJO VIRTUAL • Neiva, Huila, Colombia. Todos los derechos reservados.
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="assets/js/app.js"></script>
</body>
</html>
