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
  <title>ANTOJO VIRTUAL | Comida Rápida Neumórfica en Neiva, Huila</title>
  <meta name="description" content="El epicentro digital de comida rápida, sabor callejero y parrilladas auténticas para Neiva, Huila con un diseño moderno Neumorfismo Soft UI.">
  
  <!-- Tipografías Oficiales de Antojo Virtual -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  
  <!-- Metadatos y Manifiesto PWA (Instalable en Celulares) -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#dc2626">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Antojo Virtual">
  <link rel="apple-touch-icon" href="assets/icons/icon.svg">

  <!-- Hojas de Estilos Neumórficas & Mapas -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/auth.css">
  <link rel="stylesheet" href="assets/css/payments.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
</head>
<body>

  <!-- =========================================================
       HEADER PRINCIPAL SUPERIOR (NEUMORFISMO)
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
            <span>Neiva, Huila • Neomorfismo</span>
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
          <!-- Botón de Audio Efectos -->
          <button id="btn-audio-toggle" onclick="App.toggleSoundFX()" class="btn-admin-link" title="Efectos de sonido en vivo">
            <span id="audio-toggle-icon" class="material-symbols-outlined" style="font-size: 18px; color: #16a34a;">volume_up</span>
            <span id="audio-toggle-text">Sonido</span>
          </button>

          <!-- Portal Rider para Domiciliario -->
          <a href="rider.php" target="_blank" class="btn-admin-link" title="Portal para el repartidor en moto">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #fea619;">two_wheeler</span>
            <span>Rider App</span>
          </a>

          <!-- Reseñas de Clientes -->
          <a href="reviews.php" target="_blank" class="btn-admin-link" title="Ver calificaciones y reseñas de clientes">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #f59e0b;">star</span>
            <span>Reseñas</span>
          </a>

          <!-- Auth JWT: Botón de usuario / login -->
          <div id="header-auth-btn"></div>

          <a href="admin.php" class="btn-admin-link" title="Ir al panel de cocina y pedidos">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #dc2626;">kitchen</span>
            <span>Panel Cocina</span>
          </a>

          <!-- Botón de Instalación PWA (Móvil) -->
          <button id="btn-install-pwa" onclick="App.promptInstallPWA()" class="btn-admin-link" style="display: none; background: #dc2626; color: #ffffff;" title="Instalar Antojo Virtual en tu celular">
            <span class="material-symbols-outlined" style="font-size: 18px;">install_mobile</span>
            <span>Instalar App</span>
          </button>

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
       RIBBON DE NAVEGACIÓN DE PANTALLAS (SEGMENTED NEUMORPHIC)
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

        <div style="display: flex; align-items: center; gap: 0.65rem;">
          <button class="tab-btn" style="box-shadow: var(--neu-extruded-sm); color: #855300; background: var(--neu-bg); border: 1px solid var(--color-border-subtle);" onclick="App.openCustomizer(1)">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #dc2626;">tune</span>
            <span>Probar Personalizador</span>
          </button>
          <a href="install.php" style="font-size: 0.8rem; color: var(--color-on-surface-variant); text-decoration: none; display: flex; align-items: center; gap: 0.3rem;" title="Verificar estado de MySQL y base de datos">
            <span class="material-symbols-outlined" style="font-size: 16px; color: #1a8340;">check_circle</span>
            <span>MySQL Conectado</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- =========================================================
       PANTALLA 1: EXPLORAR MENÚ & RESTAURANTES
       ========================================================= -->
  <main id="screen-explorar" class="container">
    <!-- Hero Banner Neumórfico -->
    <section class="hero-banner">
      <div class="hero-content">
        <div class="hero-pill">
          <span class="material-symbols-outlined" style="font-size: 16px; color: #fea619;">local_shipping</span>
          Entregas Calientes en Neiva • 25 a 35 min
        </div>
        <h2 class="hero-title">
          ¡El auténtico sabor huilense <span>directo a tu puerta!</span>
        </h2>
        <p class="hero-desc">
          Parrillas al carbón, carnes seleccionadas, quesos asados de Algeciras y las salchipapas más monumentales de la capital del Huila con experiencia de diseño táctil Neumórfico.
        </p>

        <!-- Caja de cupón promocional copiable -->
        <div class="coupon-hero-box">
          <span class="material-symbols-outlined" style="color: #fea619;">loyalty</span>
          <span style="font-size: 0.85rem; color: var(--color-on-surface); font-weight: 700;">Cupón Neiva:</span>
          <span class="coupon-code-text">ANTOJONEIVA5K</span>
          <button id="btn-hero-copy-coupon" class="btn-copy-coupon">
            Copiar (-$5.000 COP)
          </button>
        </div>
      </div>
    </section>

    <!-- Selector de Tipo de Antojo (Comidas Rápidas vs Dulces y Postres) -->
    <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.35rem;">
      <button class="cat-chip active" id="btn-mood-all" onclick="App.filterBySiteType('all')" style="font-weight: 800; cursor: pointer;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #dc2626;">auto_awesome</span>
        🌟 Todo el Menú (28 Sitios)
      </button>
      <button class="cat-chip" id="btn-mood-salado" onclick="App.filterBySiteType('comidas_rapidas')" style="font-weight: 800; cursor: pointer;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #dc2626;">lunch_dining</span>
        🍔 Comidas Rápidas & Parrillas (14)
      </button>
      <button class="cat-chip" id="btn-mood-dulce" onclick="App.filterBySiteType('dulces')" style="font-weight: 800; cursor: pointer; background: #fff5f5; border-color: #fca5a5;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #e11d48;">icecream</span>
        🍨 Dulces, Helados & Postres (14)
      </button>
    </div>

    <!-- Selector / Slider de Categorías Neumórficas -->
    <section class="categories-slider" id="subcategories-slider">
      <button class="cat-chip active" data-cat="all" onclick="App.filterByCategory('all')">
        <span class="material-symbols-outlined" style="font-size: 20px;">restaurant</span>
        Todos
      </button>
      <!-- Categorías Dulces -->
      <button class="cat-chip sweet-cat" data-cat="helados" onclick="App.filterByCategory('helados')" style="color: #be123c;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #e11d48;">icecream</span>
        🍨 Helados & Gelato
      </button>
      <button class="cat-chip sweet-cat" data-cat="postres" onclick="App.filterByCategory('postres')" style="color: #be123c;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #e11d48;">cake</span>
        🍰 Postres & Tortas
      </button>
      <button class="cat-chip sweet-cat" data-cat="waffles" onclick="App.filterByCategory('waffles')" style="color: #be123c;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #e11d48;">cookie</span>
        🧇 Waffles & Crepes
      </button>
      <button class="cat-chip sweet-cat" data-cat="malteadas" onclick="App.filterByCategory('malteadas')" style="color: #be123c;">
        <span class="material-symbols-outlined" style="font-size: 20px; color: #e11d48;">local_cafe</span>
        🥤 Monster Shakes
      </button>
      <!-- Categorías Saladas -->
      <button class="cat-chip" data-cat="burgers" onclick="App.filterByCategory('burgers')">
        <span class="material-symbols-outlined" style="font-size: 20px;">lunch_dining</span>
        Hamburguesas
      </button>
      <button class="cat-chip" data-cat="salchipapas" onclick="App.filterByCategory('salchipapas')">
        <span class="material-symbols-outlined" style="font-size: 20px;">fastfood</span>
        Salchipapas
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
        Desgranados
      </button>
    </section>

    <!-- Sección: Restaurantes Aliados en Neiva -->
    <section style="margin-top: 1.5rem;">
      <div class="section-header">
        <h2 class="section-title">
          <span class="material-symbols-outlined" style="color: #dc2626;">storefront</span>
          Restaurantes Aliados en Neiva
        </h2>
        <span style="font-size: 0.85rem; color: var(--color-on-surface-variant); font-weight: 700;">Cocinas 100% Verificadas</span>
      </div>
      <div id="restaurants-container" class="dishes-grid">
        <!-- Render dinámico desde MySQL via app.js -->
        <div style="text-align: center; padding: 2rem; color: var(--color-on-surface-variant);">Cargando restaurantes desde MySQL...</div>
      </div>
    </section>

    <!-- Sección: Platos y Especialidades Populares -->
    <section style="margin-top: 3rem; margin-bottom: 3rem;">
      <div class="section-header">
        <h2 class="section-title">
          <span class="material-symbols-outlined" style="color: #fea619;">local_fire_department</span>
          Platos Estrella & Antojos de la Casa
        </h2>
        <span style="font-size: 0.85rem; color: var(--color-on-surface-variant); font-weight: 700;">Personaliza carne, pan y salsas</span>
      </div>
      <div id="dishes-container" class="dishes-grid">
        <!-- Render dinámico desde MySQL via app.js -->
        <div style="text-align: center; padding: 2rem; color: var(--color-on-surface-variant);">Cargando platos desde MySQL...</div>
      </div>
    </section>
  </main>

  <!-- =========================================================
       PANTALLA 2: CHECKOUT & PAGO (PASO 2)
       ========================================================= -->
  <section id="screen-checkout" class="container checkout-view" style="display: none;">
    <!-- Stepper Neumórfico de Progreso -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; padding: 1.25rem 1.75rem; background: var(--neu-bg); border-radius: var(--radius-lg); box-shadow: var(--neu-extruded); border: 1px solid var(--color-border-subtle);">
      <div style="display: flex; align-items: center; gap: 0.75rem; color: #1a8340;">
        <span class="material-symbols-outlined" style="font-size: 28px;">check_circle</span>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 900;">Paso 1</div>
          <div style="font-weight: 800; font-size: 0.95rem;">Carrito & Toppings</div>
        </div>
      </div>
      <div style="flex: 1; height: 6px; background: var(--neu-bg); box-shadow: var(--neu-inset-sm); margin: 0 1.5rem; border-radius: 9999px;">
        <div style="width: 100%; height: 100%; background: #1a8340; border-radius: 9999px;"></div>
      </div>
      <div style="display: flex; align-items: center; gap: 0.75rem; color: #dc2626;">
        <div style="width: 36px; height: 36px; border-radius: 9999px; background: var(--color-primary-neu); color: white; display: flex; align-items: center; justify-content: center; font-weight: 900; box-shadow: var(--color-primary-neu-shadow);">2</div>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 900;">Paso 2 (Activo)</div>
          <div style="font-weight: 800; font-size: 0.95rem;">Entrega & Pago</div>
        </div>
      </div>
      <div style="flex: 1; height: 6px; background: var(--neu-bg); box-shadow: var(--neu-inset-sm); margin: 0 1.5rem; border-radius: 9999px;"></div>
      <div style="display: flex; align-items: center; gap: 0.75rem; opacity: 0.6;">
        <div style="width: 36px; height: 36px; border-radius: 9999px; background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); color: var(--color-on-surface-variant); display: flex; align-items: center; justify-content: center; font-weight: 800;">3</div>
        <div>
          <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800;">Paso 3</div>
          <div style="font-weight: 800; font-size: 0.95rem;">Rastreo en Vivo</div>
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
          <div style="margin-top: 1.25rem; text-align: right;">
            <button onclick="App.navigate('explorar')" style="background: none; border: none; color: #dc2626; font-weight: 800; font-size: 0.9rem; cursor: pointer;">
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
            Pasarela de Pagos Multibanco
          </h3>
          <p style="font-size: 0.85rem; color: var(--color-on-surface-variant); margin-bottom: 1.25rem;">
            Transacciones seguras encriptadas para Neiva con soporte para todos los bancos de Colombia, tarjetas y billeteras.
          </p>

          <div class="payment-methods-grid">
            <div class="pay-card active" data-method="card">
              <span class="material-symbols-outlined" style="font-size: 26px;">credit_card</span>
              <span style="font-size: 0.85rem; font-weight: 800;">Tarjeta</span>
            </div>
            <div class="pay-card" data-method="pse">
              <span class="material-symbols-outlined" style="font-size: 26px;">account_balance</span>
              <span style="font-size: 0.85rem; font-weight: 800;">PSE Bancos</span>
            </div>
            <div class="pay-card" data-method="nequi">
              <span class="material-symbols-outlined" style="font-size: 26px;">account_balance_wallet</span>
              <span style="font-size: 0.85rem; font-weight: 800;">Nequi</span>
            </div>
            <div class="pay-card" data-method="daviplata">
              <span class="material-symbols-outlined" style="font-size: 26px;">phone_android</span>
              <span style="font-size: 0.85rem; font-weight: 800;">Daviplata</span>
            </div>
            <div class="pay-card" data-method="cash_efectivo">
              <span class="material-symbols-outlined" style="font-size: 26px;">attach_money</span>
              <span style="font-size: 0.85rem; font-weight: 800;">Efectivo</span>
            </div>
            <div class="pay-card" data-method="cash_datafono">
              <span class="material-symbols-outlined" style="font-size: 26px;">point_of_sale</span>
              <span style="font-size: 0.85rem; font-weight: 800;">Datáfono</span>
            </div>
          </div>

          <!-- Contenedor Dinámico de la Pasarela de Pagos -->
          <div class="gateway-container">
            
            <!-- PANEL 1: TARJETAS DE CRÉDITO Y DÉBITO -->
            <div id="gateway-panel-card" class="gateway-panel">
              <!-- Tarjeta Visual Neumórfica 3D -->
              <div class="card-visual-wrapper">
                <div class="neu-card-preview">
                  <div class="neu-card-top">
                    <div class="neu-card-chip"></div>
                    <div class="neu-card-contactless">
                      <span class="material-symbols-outlined">contactless</span>
                    </div>
                    <div id="card-brand-display" class="neu-card-brand-badge visa">
                      <span>VISA</span>
                    </div>
                  </div>
                  <div id="card-number-display" class="neu-card-number">•••• •••• •••• 4242</div>
                  <div class="neu-card-bottom">
                    <div>
                      <div class="neu-card-exp-label">Titular de la Tarjeta</div>
                      <div id="card-holder-display" class="neu-card-holder-name">MATEO RODRÍGUEZ</div>
                    </div>
                    <div style="text-align: right;">
                      <div class="neu-card-exp-label">Vence</div>
                      <div id="card-exp-display" class="neu-card-exp-val">12/28</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Formulario de Tarjeta -->
              <div class="card-form-grid">
                <div class="card-form-full form-group">
                  <label class="form-label">Número de Tarjeta</label>
                  <div style="position: relative;">
                    <input type="text" id="card-input-number" class="form-input" placeholder="4000 1234 5678 9010" maxlength="19" autocomplete="cc-number">
                    <span id="card-input-brand-icon" class="material-symbols-outlined" style="position: absolute; right: 14px; top: 12px; color: #566479;">credit_card</span>
                  </div>
                </div>

                <div class="card-form-full form-group">
                  <label class="form-label">Nombre del Titular (como aparece en el plástico)</label>
                  <input type="text" id="card-input-name" class="form-input" placeholder="Ej: MATEO RODRIGUEZ" value="MATEO RODRÍGUEZ" autocomplete="cc-name">
                </div>

                <div class="form-group">
                  <label class="form-label">Expiración (MM/AA)</label>
                  <input type="text" id="card-input-exp" class="form-input" placeholder="MM/AA" maxlength="5" value="12/28" autocomplete="cc-exp">
                </div>

                <div class="form-group">
                  <label class="form-label">Código CVV / CVC</label>
                  <div style="position: relative;">
                    <input type="password" id="card-input-cvv" class="form-input" placeholder="•••" maxlength="4" value="123" autocomplete="cc-csc">
                    <span class="material-symbols-outlined" style="position: absolute; right: 12px; top: 12px; font-size: 18px; color: #855300;" title="3 o 4 dígitos de seguridad en el reverso">lock</span>
                  </div>
                </div>

                <div class="card-form-full form-group">
                  <label class="form-label">Diferir a Cuotas</label>
                  <select id="card-input-installments" class="form-select">
                    <option value="1" selected>1 cuota (Sin intereses bancarios)</option>
                    <option value="2">2 cuotas</option>
                    <option value="3">3 cuotas</option>
                    <option value="6">6 cuotas</option>
                    <option value="12">12 cuotas</option>
                    <option value="24">24 cuotas</option>
                    <option value="36">36 cuotas</option>
                  </select>
                  <div id="card-installment-calc" class="installments-badge">
                    <span class="material-symbols-outlined" style="font-size: 16px;">payments</span>
                    <span>1 cuota de <strong id="card-installment-amount">$0</strong></span>
                  </div>
                </div>

                <div class="card-form-full" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                  <input type="checkbox" id="card-save-checkbox" checked style="accent-color: #dc2626; width: 18px; height: 18px; cursor: pointer;">
                  <label for="card-save-checkbox" style="font-size: 0.8rem; color: var(--color-on-surface-variant); cursor: pointer;">
                    Guardar tarjeta tokenizada de forma segura para compras rápidas
                  </label>
                </div>
              </div>
            </div>

            <!-- PANEL 2: PSE (18 BANCOS DE COLOMBIA) -->
            <div id="gateway-panel-pse" class="gateway-panel" style="display: none;">
              <div class="pse-banner">
                <div class="pse-badge-logo">
                  <div class="pse-logo-circle">PSE</div>
                  <div>
                    <h4 style="margin: 0; font-family: var(--font-epilogue); font-size: 0.95rem; font-weight: 800; color: #003366;">
                      Pagos Seguros en Línea
                    </h4>
                    <span style="font-size: 0.75rem; color: var(--color-on-surface-variant);">
                      Débito en tiempo real desde tu cuenta bancaria
                    </span>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #1a8340; font-weight: 700;">
                  <span class="material-symbols-outlined" style="font-size: 18px;">verified_user</span>
                  <span>SSL 256 bits</span>
                </div>
              </div>

              <div class="card-form-grid">
                <div class="card-form-full form-group">
                  <label class="form-label" style="font-weight: 800; color: #003366;">
                    Selecciona tu Banco o Entidad Financiera
                  </label>
                  <div class="pse-banks-select-wrapper">
                    <select id="pse-bank-select" class="form-select" style="font-weight: 700; border-color: #003366;">
                      <optgroup label="Bancos Tradicionales">
                        <option value="1" selected>Bancolombia S.A.</option>
                        <option value="4">Banco Davivienda S.A.</option>
                        <option value="5">Banco de Bogotá</option>
                        <option value="6">BBVA Colombia</option>
                        <option value="7">Banco de Occidente</option>
                        <option value="8">Scotiabank Colpatria</option>
                        <option value="9">Banco Popular</option>
                        <option value="10">Banco AV Villas</option>
                        <option value="11">Banco Caja Social</option>
                        <option value="12">Banco Itaú</option>
                        <option value="17">Banco Falabella</option>
                        <option value="18">Banco Pichincha</option>
                      </optgroup>
                      <optgroup label="Neobancos y Billeteras Digitales">
                        <option value="2">Nequi (PSE Bancolombia)</option>
                        <option value="3">Daviplata (PSE Davivienda)</option>
                        <option value="13">Nu Colombia (Nubank)</option>
                        <option value="14">Lulo Bank</option>
                        <option value="15">Dale! (Grupo Aval)</option>
                        <option value="16">RappiPay</option>
                      </optgroup>
                    </select>
                  </div>
                </div>

                <div class="card-form-full form-group">
                  <label class="form-label">Tipo de Persona</label>
                  <div style="display: flex; gap: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; font-weight: 700; cursor: pointer;">
                      <input type="radio" name="pse_person_type" value="natural" checked style="accent-color: #003366;">
                      Persona Natural
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; font-weight: 700; cursor: pointer;">
                      <input type="radio" name="pse_person_type" value="juridica" style="accent-color: #003366;">
                      Persona Jurídica (Empresa)
                    </label>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Tipo de Identificación</label>
                  <select id="pse-doc-type" class="form-select">
                    <option value="CC" selected>Cédula de Ciudadanía (CC)</option>
                    <option value="CE">Cédula de Extranjería (CE)</option>
                    <option value="NIT">NIT (Empresas)</option>
                    <option value="PP">Pasaporte</option>
                    <option value="PPT">Permiso por Protección Temporal (PPT)</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Número de Identificación</label>
                  <input type="text" id="pse-doc-number" class="form-input" placeholder="Ej: 1075304128" value="1075304128">
                </div>

                <div class="card-form-full form-group">
                  <label class="form-label">Correo Registrado en PSE</label>
                  <input type="email" id="pse-email" class="form-input" placeholder="correo@ejemplo.com" value="mateo@gmail.com">
                </div>
              </div>
            </div>

            <!-- PANEL 3: NEQUI DIRECTO -->
            <div id="gateway-panel-nequi" class="gateway-panel" style="display: none;">
              <div class="wallet-box-nequi">
                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.75rem;">
                  <span class="material-symbols-outlined" style="color: #c81e6e; font-size: 32px;">account_balance_wallet</span>
                  <div>
                    <h4 style="margin: 0; font-family: var(--font-epilogue); font-size: 1.05rem; font-weight: 900; color: #4a154b;">
                      Pagar con Nequi
                    </h4>
                    <span style="font-size: 0.8rem; color: var(--color-on-surface-variant);">
                      Notificación directa a tu celular o escaneo de QR
                    </span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Número de Celular Nequi</label>
                  <input type="tel" id="nequi-phone-input" class="form-input" placeholder="314 289 9012" value="3142899012">
                </div>

                <div class="qr-simulator-box">
                  <div class="qr-code-img">
                    <svg viewBox="0 0 100 100" width="78" height="78">
                      <rect width="100" height="100" fill="#ffffff" />
                      <path d="M10,10 h30 v30 h-30 z M15,15 v20 h20 v-20 z M20,20 h10 v10 h-10 z" fill="#4a154b"/>
                      <path d="M60,10 h30 v30 h-30 z M65,15 v20 h20 v-20 z M70,20 h10 v10 h-10 z" fill="#4a154b"/>
                      <path d="M10,60 h30 v30 h-30 z M15,65 v20 h20 v-20 z M20,70 h10 v10 h-10 z" fill="#4a154b"/>
                      <rect x="50" y="50" width="10" height="10" fill="#c81e6e"/>
                      <rect x="70" y="50" width="15" height="10" fill="#4a154b"/>
                      <rect x="50" y="70" width="20" height="15" fill="#4a154b"/>
                      <rect x="75" y="70" width="15" height="20" fill="#c81e6e"/>
                    </svg>
                  </div>
                  <div>
                    <div style="font-size: 0.85rem; font-weight: 800; color: #4a154b;">
                      Aprobación Push Instantánea
                    </div>
                    <p style="font-size: 0.78rem; color: var(--color-on-surface-variant); margin: 0.25rem 0 0 0;">
                      Al confirmar el pedido recibirás la notificación de cobro en tu app Nequi. Tienes 5 minutos para autorizarlo.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- PANEL 4: DAVIPLATA DIRECTO -->
            <div id="gateway-panel-daviplata" class="gateway-panel" style="display: none;">
              <div class="wallet-box-daviplata">
                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.75rem;">
                  <span class="material-symbols-outlined" style="color: #dc2626; font-size: 32px;">phone_android</span>
                  <div>
                    <h4 style="margin: 0; font-family: var(--font-epilogue); font-size: 1.05rem; font-weight: 900; color: #991b1b;">
                      Pagar con Daviplata
                    </h4>
                    <span style="font-size: 0.8rem; color: var(--color-on-surface-variant);">
                      Notificación o código de confirmación OTP
                    </span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Número de Celular Daviplata</label>
                  <input type="tel" id="daviplata-phone-input" class="form-input" placeholder="314 289 9012" value="3142899012">
                </div>

                <div class="form-group">
                  <label class="form-label">Número de Cédula del Titular</label>
                  <input type="text" id="daviplata-doc-input" class="form-input" placeholder="1075304128" value="1075304128">
                </div>
              </div>
            </div>

            <!-- PANEL 5: EFECTIVO CONTRA ENTREGA -->
            <div id="gateway-panel-cash_efectivo" class="gateway-panel" style="display: none;">
              <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--color-border-subtle);">
                <label class="form-label" style="color: #855300; font-weight: 800;">
                  ¿Con cuánto vas a pagar en efectivo?
                </label>
                <select id="cash-change-select" class="form-select" style="box-shadow: var(--neu-extruded-sm); margin-bottom: 0.75rem;">
                  <option value="exacto">Pago exacto (No requiero cambio)</option>
                  <option value="50000">Billete de $50.000 COP</option>
                  <option value="100000">Billete de $100.000 COP</option>
                </select>
                <div id="cash-change-calc" style="font-size: 0.85rem; color: #00682e; font-weight: 800;">
                  El domiciliario llevará cambio para tu billete.
                </div>
              </div>
            </div>

            <!-- PANEL 6: DATÁFONO CONTRA ENTREGA -->
            <div id="gateway-panel-cash_datafono" class="gateway-panel" style="display: none;">
              <div style="display: flex; align-items: center; gap: 1rem; background: var(--neu-bg); box-shadow: var(--neu-inset-sm); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--color-border-subtle);">
                <span class="material-symbols-outlined" style="font-size: 36px; color: #855300;">point_of_sale</span>
                <div>
                  <div style="font-weight: 800; font-size: 0.95rem; color: var(--color-on-surface);">
                    Datáfono inalámbrico en tu puerta
                  </div>
                  <p style="font-size: 0.8rem; color: var(--color-on-surface-variant); margin: 0.25rem 0 0 0;">
                    El repartidor llevará un datáfono Redeban / Credibanco compatible con tarjetas débito, crédito y pago sin contacto (Contactless / Apple Pay / Google Pay).
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Columna Derecha: Resumen de Totales y Cupón -->
      <div>
        <div class="order-summary-card">
          <h3 class="checkout-card-title">
            <span class="material-symbols-outlined" style="color: var(--color-on-surface);">receipt</span>
            Resumen de la Orden
          </h3>

          <!-- Formulario de cupón neumórfico -->
          <div style="margin-bottom: 1.75rem; background: var(--neu-bg); box-shadow: var(--neu-inset); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--color-border-subtle);">
            <label class="form-label" style="font-size: 0.8rem;">¿Tienes un cupón de descuento?</label>
            <div style="display: flex; gap: 0.65rem;">
              <input type="text" id="coupon-input" class="form-input" placeholder="Ej: ANTOJONEIVA5K" style="text-transform: uppercase; box-shadow: var(--neu-extruded-sm);">
              <button onclick="App.applyCouponCode()" class="btn-customize" style="border-radius: var(--radius-md);">
                Aplicar
              </button>
            </div>
            <div id="coupon-applied-tag" style="display: none; align-items: center; justify-content: space-between; margin-top: 0.65rem; background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); color: #00682e; padding: 0.5rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 800;">
              <span>✓ Cupón <strong id="coupon-applied-code"></strong> activo</span>
              <button onclick="App.removeCoupon()" style="background: none; border: none; color: #dc2626; cursor: pointer; font-weight: 900;">✕</button>
            </div>
          </div>

          <!-- Filas de totales -->
          <div class="summary-row">
            <span>Subtotal de productos y adiciones</span>
            <strong id="summary-subtotal" style="color: var(--color-on-surface); font-weight: 800;">$0</strong>
          </div>
          <div id="summary-discount-row" class="summary-row" style="color: #00682e; display: none;">
            <span>Descuento promocional</span>
            <strong id="summary-discount">-$0</strong>
          </div>
          <div class="summary-row">
            <span>Domicilio local en Neiva</span>
            <strong id="summary-delivery" style="color: var(--color-on-surface); font-weight: 800;">$3.500</strong>
          </div>
          <div class="summary-row">
            <span>Tasa de apoyo al repartidor</span>
            <strong id="summary-tip" style="color: var(--color-on-surface); font-weight: 800;">$1.000</strong>
          </div>

          <!-- Total a pagar -->
          <div class="summary-row summary-total">
            <span>Total a Pagar</span>
            <span id="summary-total" style="color: var(--color-primary-container); font-weight: 900;">$0</span>
          </div>

          <!-- Botón de Confirmación -->
          <button id="btn-confirm-order-action" class="btn-confirm-order" onclick="App.submitOrder()">
            <span class="material-symbols-outlined">check_circle</span>
            <span>Confirmar Pedido • </span>
            <span id="btn-confirm-total">$0</span>
          </button>

          <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; font-size: 0.75rem; color: var(--color-on-surface-variant); margin-top: 1.25rem;">
            <span class="material-symbols-outlined" style="font-size: 16px; color: #1a8340;">lock</span>
            <span>Transacción segura y directa con el restaurante en Neiva</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =========================================================
       PANTALLA 3: RASTREO EN VIVO (LIVE TRACKING NEUMÓRFICO)
       ========================================================= -->
  <!-- =========================================================
       PANTALLA 3: RASTREO EN VIVO Y MAPA SATELITAL DE NEIVA
       ========================================================= -->
  <section id="screen-rastreo" class="container" style="display: none; margin: 2rem auto;">
    
    <!-- Buscador Rápido de Pedidos para el Cliente -->
    <div class="track-search-card">
      <div style="display: flex; align-items: center; gap: 0.65rem;">
        <span class="material-symbols-outlined" style="color: #dc2626; font-size: 26px;">manage_search</span>
        <div>
          <h4 style="margin: 0; font-family: var(--font-epilogue); font-size: 1.05rem; font-weight: 800; color: var(--color-on-surface);">
            Consultar Rastreo de Pedido
          </h4>
          <span style="font-size: 0.78rem; color: var(--color-on-surface-variant);">
            Ingresa tu código de orden de Neiva o tu número de celular
          </span>
        </div>
      </div>

      <div class="track-search-input-wrapper">
        <input type="text" id="track-search-code-input" class="form-input" placeholder="Ej: #NV-8492" style="box-shadow: var(--neu-inset-sm); text-transform: uppercase;">
        <button onclick="App.searchOrderTracking()" class="btn-customize" style="background: var(--color-primary-neu); color: #fff; box-shadow: var(--color-primary-neu-shadow);">
          <span class="material-symbols-outlined" style="font-size: 18px;">search</span>
          Buscar
        </button>
      </div>

      <div id="track-current-pill" style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface-variant); background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); padding: 0.5rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border-subtle);">
        Orden Activa: <strong id="track-current-badge" style="color: #dc2626;">#NV-8492</strong>
      </div>
    </div>

    <div style="background: var(--neu-bg); border-radius: var(--radius-lg); box-shadow: var(--neu-extruded-lg); border: 1px solid var(--color-border-subtle); padding: 2.25rem;">
      
      <!-- Encabezado del Pedido -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 1.75rem;">
        <div>
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
            <span class="material-symbols-outlined" style="color: #dc2626;">moped</span>
            <span style="font-size: 0.85rem; font-weight: 800; color: var(--color-on-surface-variant); text-transform: uppercase;">
              Telemetría & GPS en Tiempo Real
            </span>
          </div>
          <h2 style="font-family: var(--font-epilogue); font-size: 1.95rem; font-weight: 900; color: var(--color-on-surface); margin: 0;">
            Pedido <span id="track-order-code" style="color: #dc2626;">#NV-8492</span>
          </h2>
          <p id="track-status-sub" style="color: var(--color-on-surface-variant); font-size: 0.95rem; margin-top: 0.35rem;">
            El repartidor va en ruta por la Av. Circunvalar de Neiva hacia tu ubicación.
          </p>
        </div>

        <div style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
          <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); padding: 0.75rem 1.35rem; border-radius: var(--radius-md); text-align: right;">
            <div style="font-size: 0.75rem; font-weight: 800; color: #855300; text-transform: uppercase;">Tiempo Estimado</div>
            <div id="track-eta" style="font-family: var(--font-epilogue); font-size: 1.45rem; font-weight: 900; color: #b70011;">15-20 min</div>
          </div>
          <button onclick="App.viewReceiptForCurrentOrder()" class="btn-customize" style="background: var(--neu-bg); color: #0284c7; box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); font-size: 0.85rem; padding: 0.75rem 1.15rem;" title="Ver comprobante de pago oficial y factura electrónica">
            <span class="material-symbols-outlined" style="font-size: 18px;">receipt_long</span>
            Ver Factura
          </button>
          <button onclick="App.autoAdvanceDemoOrder()" class="btn-customize" style="background: var(--neu-bg); color: var(--color-on-surface); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); font-size: 0.85rem; padding: 0.75rem 1.15rem;" title="Simular que el pedido avanza de etapa">
            <span class="material-symbols-outlined" style="font-size: 18px; color: #fea619;">fast_forward</span>
            Simular Avance
          </button>
        </div>
      </div>

      <!-- Barra de Estado Visual Neumórfica (Stepper de 4 Pasos) -->
      <div class="tracking-stepper">
        <div class="tracking-line">
          <div id="track-progress-bar" class="tracking-line-progress" style="width: 55%;"></div>
        </div>

        <!-- Paso 0 -->
        <div id="track-step-0" class="tracking-step done">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">assignment_turned_in</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface);">Confirmado</span>
          <span style="font-size: 0.7rem; color: var(--color-on-surface-variant);">Recibido</span>
        </div>

        <!-- Paso 1 -->
        <div id="track-step-1" class="tracking-step active">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">local_fire_department</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 800; color: #dc2626;">Al Carbón</span>
          <span style="font-size: 0.7rem; color: var(--color-on-surface-variant);">En Cocina</span>
        </div>

        <!-- Paso 2 -->
        <div id="track-step-2" class="tracking-step">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">two_wheeler</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface);">En Camino</span>
          <span style="font-size: 0.7rem; color: var(--color-on-surface-variant);">Rider en ruta</span>
        </div>

        <!-- Paso 3 -->
        <div id="track-step-3" class="tracking-step">
          <div class="tracking-step-dot">
            <span class="material-symbols-outlined" style="font-size: 20px;">done_all</span>
          </div>
          <span style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface);">¡Entregado!</span>
          <span style="font-size: 0.7rem; color: var(--color-on-surface-variant);">En tu puerta</span>
        </div>
      </div>

      <!-- Estado Destacado Neumórfico -->
      <div style="background: var(--neu-bg); box-shadow: var(--neu-inset); border-radius: var(--radius-md); padding: 1.25rem 1.75rem; display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.25rem; border: 1px solid var(--color-border-subtle); flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
          <span class="material-symbols-outlined" style="color: #dc2626; font-size: 32px;">restaurant_menu</span>
          <div>
            <div id="track-status-title" style="font-weight: 800; font-size: 1.15rem; color: var(--color-on-surface);">Carbón al Rojo Vivo & Cocción</div>
            <div style="font-size: 0.85rem; color: var(--color-on-surface-variant);">El restaurante en Neiva prepara tu orden con los mejores ingredientes y carne al carbón.</div>
          </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <span class="hud-gps-pill">
            <span class="hud-gps-dot"></span>
            <span id="track-gps-indicator">GPS Neiva Conectado</span>
          </span>
          <span style="font-size: 0.8rem; background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); padding: 0.45rem 0.95rem; border-radius: 9999px; font-weight: 800; color: #1a8340; border: 1px solid var(--color-border-subtle);">
            ● Conexión MySQL Activa
          </span>
        </div>
      </div>

      <!-- Grilla: Mapa Interactivo de Neiva y Tarjeta del Repartidor -->
      <div style="display: grid; grid-template-columns: 1.55fr 1fr; gap: 2rem;">
        
        <!-- Columna Izquierda: Mapa con Telemetría HUD -->
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h4 style="font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
              <span class="material-symbols-outlined" style="color: #dc2626;">location_searching</span>
              Ubicación Satelital & Ruta en Neiva (GPS en Vivo)
            </h4>
            <span style="font-size: 0.75rem; color: var(--color-on-surface-variant); font-weight: 700;">
              Huila, Colombia
            </span>
          </div>

          <!-- Contenedor del Mapa con HUD y Controles -->
          <div class="map-view-wrapper">
            
            <!-- HUD Flotante de Telemetría sobre el mapa -->
            <div class="tracking-hud-overlay">
              <div class="hud-item">
                <span class="material-symbols-outlined" style="color: #dc2626; font-size: 20px;">speed</span>
                <div>
                  <span class="hud-item-label">Velocidad</span>
                  <span id="hud-speed" class="hud-item-value">32 km/h</span>
                </div>
              </div>

              <div class="hud-item">
                <span class="material-symbols-outlined" style="color: #fea619; font-size: 20px;">straighten</span>
                <div>
                  <span class="hud-item-label">Distancia</span>
                  <span id="hud-distance" class="hud-item-value">0.8 km</span>
                </div>
              </div>

              <div class="hud-item">
                <span class="material-symbols-outlined" style="color: #15803d; font-size: 20px;">schedule</span>
                <div>
                  <span class="hud-item-label">Tiempo</span>
                  <span id="hud-eta" class="hud-item-value">6-10 min</span>
                </div>
              </div>

              <div class="hud-item" style="max-width: 170px;">
                <span class="material-symbols-outlined" style="color: #0284c7; font-size: 20px;">near_me</span>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  <span class="hud-item-label">Vía Actual</span>
                  <span id="hud-street" class="hud-item-value" style="font-size: 0.78rem;">Av. Circunvalar</span>
                </div>
              </div>
            </div>

            <!-- Mapa Leaflet Interactivo -->
            <div id="neiva-interactive-map"></div>

            <!-- Canvas Fallback HD por si no hay conexión de tiles -->
            <canvas id="neiva-map-canvas" style="display: none; width: 100%; height: 420px;"></canvas>

            <!-- Controles Flotantes del Mapa -->
            <div class="map-floating-controls">
              <button onclick="App.centerMapOnRider()" class="map-control-btn" title="Centrar mapa en la moto del repartidor">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #dc2626;">my_location</span>
                <span>Rider</span>
              </button>
              <button onclick="App.centerMapOnRoute()" class="map-control-btn" title="Ver la ruta completa desde restaurante hasta tu casa">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #0284c7;">route</span>
                <span>Ruta</span>
              </button>
              <button onclick="App.toggleMapTilesLayer()" class="map-control-btn" title="Alternar capa satelital o callejero">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #16a34a;">layers</span>
                <span>Capa</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Tarjeta del Repartidor & Detalles -->
        <div>
          <h4 style="font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 800; margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
            <span class="material-symbols-outlined" style="color: #fea619;">person_pin</span>
            Tu Repartidor Asignado
          </h4>

          <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-lg); padding: 1.75rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.35rem;">
              <img id="track-rider-img" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80" alt="Rider" style="width: 72px; height: 72px; border-radius: 9999px; object-fit: cover; box-shadow: var(--neu-extruded-sm); border: 3px solid #fea619;">
              <div>
                <h5 id="track-rider-name" style="font-family: var(--font-epilogue); font-size: 1.25rem; font-weight: 900; color: var(--color-on-surface); margin: 0;">Carlos Mendoza</h5>
                <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; font-weight: 800; color: #855300; margin-top: 0.25rem;">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: #fea619;">star</span>
                  <span id="track-rider-rating">4.9</span> (412 entregas en Neiva)
                </div>
                <div id="track-rider-vehicle" style="font-size: 0.8rem; color: var(--color-on-surface-variant); font-weight: 600; margin-top: 2px;">
                  Bajaj Pulsar 200 NS - Negra (HUI-84E)
                </div>
              </div>
            </div>

            <!-- Posición en la Calle -->
            <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); padding: 0.95rem 1.15rem; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 1.35rem; border: 1px solid var(--color-border-subtle);">
              <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: var(--color-on-surface-variant);">Ubicación Actual en Neiva:</span>
              <div id="track-rider-street" style="color: #dc2626; font-weight: 900; font-size: 0.95rem; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.35rem;">
                <span class="material-symbols-outlined" style="font-size: 18px;">navigation</span>
                <span>Av. Circunvalar con Calle 10</span>
              </div>
            </div>

            <!-- Acciones de Contacto Directo -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
              <a id="track-rider-whatsapp" href="#" target="_blank" class="btn-customize" style="background: linear-gradient(145deg, #1fa350, #146b34); justify-content: center; text-decoration: none; box-shadow: 5px 5px 12px rgba(26,131,64,0.35); color: #fff;">
                <span class="material-symbols-outlined">chat</span>
                WhatsApp
              </a>
              <a id="track-rider-call" href="#" class="btn-customize" style="background: var(--neu-bg); color: var(--color-on-surface); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); justify-content: center; text-decoration: none;">
                <span class="material-symbols-outlined">call</span>
                Llamar
              </a>
            </div>
          </div>

          <!-- Resumen breve de entrega -->
          <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-lg); padding: 1.35rem; margin-top: 1.25rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.65rem;">
              <span style="color: var(--color-on-surface-variant);">Dirección de destino:</span>
              <strong id="track-order-address" style="text-align: right; max-width: 60%; color: var(--color-on-surface);">Cra. 5 #18-42, Centro</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 1rem; font-weight: 900; border-top: 1px dashed rgba(194,203,215,0.8); padding-top: 0.65rem;">
              <span>Total Orden:</span>
              <span id="track-order-total" style="color: #dc2626;">$40.400</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Línea de Tiempo de Eventos en Vivo (Timeline MySQL) -->
      <div style="margin-top: 2.25rem; border-top: 1px solid var(--color-border-subtle); padding-top: 1.75rem;">
        <h4 style="font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
          <span class="material-symbols-outlined" style="color: #10b981;">history</span>
          Historial & Eventos del Pedido en Tiempo Real
        </h4>
        <div id="track-timeline-events" class="tracking-timeline-box">
          <!-- Render dinámico desde MySQL en app.js -->
        </div>
      </div>

      <!-- Ítems del pedido en rastreo -->
      <div style="margin-top: 2.25rem; border-top: 1px solid var(--color-border-subtle); padding-top: 1.75rem;">
        <h4 style="font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 800; margin-bottom: 1.25rem;">
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
          <span style="font-size: 0.75rem; color: #fea619; text-transform: uppercase; font-weight: 900;" id="modal-dish-rest">
            La Estación Burger
          </span>
          <h2 id="modal-dish-title" style="font-family: var(--font-epilogue); font-size: 1.4rem; font-weight: 900; margin: 0.2rem 0; color: var(--color-on-surface);">
            Hamburguesa Monumental Huilense 200g
          </h2>
          <span style="font-size: 0.8rem; color: var(--color-on-surface-variant);">Personaliza a tu gusto con ingredientes frescos</span>
        </div>
        <button class="modal-close-btn" onclick="App.closeCustomizer()">✕</button>
      </div>

      <div class="modal-body" id="customizer-modal-body">
        <!-- Contenido generado dinámicamente según sea Comida Salada o Dulce -->
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
       FOOTER INFERIOR NEUMÓRFICO
       ========================================================= -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.85rem;">
            <div class="brand-icon" style="width: 40px; height: 40px;">
              <span class="material-symbols-outlined" style="font-size: 22px;">lunch_dining</span>
            </div>
            <h3 style="font-family: var(--font-epilogue); font-size: 1.3rem; font-weight: 900; color: var(--color-on-surface);">ANTOJO VIRTUAL</h3>
          </div>
          <p style="color: var(--color-on-surface-variant); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
            El epicentro gastronómico digital para la ciudad de Neiva, Huila con diseño Neumorfismo Soft UI. Conectamos los mejores parrilleros y maestros de comida rápida con los hogares opitas.
          </p>
          <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #855300; font-weight: 700;">
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
          <p style="font-size: 0.85rem; color: var(--color-on-surface-variant); margin-bottom: 0.5rem;">
            Horario: Lunes a Domingo<br>
            <strong>5:00 PM – 12:00 AM</strong>
          </p>
          <p style="font-size: 0.85rem; color: #b70011; font-weight: 800;">
            WhatsApp Soporte: +57 314 289 9012
          </p>
        </div>
      </div>

      <div style="border-top: 1px solid var(--color-border-subtle); padding-top: 1.75rem; text-align: center; font-size: 0.8rem; color: var(--color-on-surface-variant); font-weight: 600;">
        © <?php echo date('Y'); ?> ANTOJO VIRTUAL • Neumorphic Edition • Neiva, Huila, Colombia.
      </div>
    </div>
  </footer>

  <!-- =========================================================
       MODAL 1: PROCESAMIENTO DE LA PASARELA DE PAGOS
       ========================================================= -->
  <div id="payment-gateway-modal" class="pay-modal-backdrop">
    <div class="pay-modal-card">
      <div class="pay-modal-header">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
          <div style="width: 36px; height: 36px; border-radius: 50%; background: #1a8340; color: #fff; display: flex; align-items: center; justify-content: center;">
            <span class="material-symbols-outlined" style="font-size: 20px;">lock</span>
          </div>
          <div>
            <h3 style="margin: 0; font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 800; color: var(--color-on-surface);">
              Pasarela de Pagos Segura
            </h3>
            <span style="font-size: 0.75rem; color: var(--color-on-surface-variant);">
              AntojoPay • Red Bancaria de Colombia
            </span>
          </div>
        </div>
        <button onclick="App.closePaymentModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--color-on-surface-variant); cursor: pointer;">✕</button>
      </div>

      <div class="pay-modal-body">
        <!-- Resumen de Transacción -->
        <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); padding: 1.15rem; border-radius: var(--radius-md); border: 1px solid var(--color-border-subtle); display: flex; justify-content: space-between; align-items: center;">
          <div>
            <div style="font-size: 0.75rem; color: var(--color-on-surface-variant); text-transform: uppercase; font-weight: 700;">Método Seleccionado</div>
            <div id="gw-modal-method-text" style="font-weight: 800; font-size: 0.95rem; color: var(--color-on-surface);">Tarjeta VISA •••• 4242</div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 0.75rem; color: var(--color-on-surface-variant); text-transform: uppercase; font-weight: 700;">Total a Debitar</div>
            <div id="gw-modal-amount-text" style="font-family: var(--font-epilogue); font-weight: 900; font-size: 1.35rem; color: #b70011;">$0</div>
          </div>
        </div>

        <!-- Pasos interactivos animados de la pasarela -->
        <div class="gateway-steps">
          <div id="gw-step-1" class="gateway-step-row active">
            <div class="gateway-step-icon">
              <span class="material-symbols-outlined">wifi_tethering</span>
            </div>
            <div>
              <div style="font-weight: 700;">1. Conectando de forma segura</div>
              <div style="font-size: 0.78rem;">Estableciendo canal cifrado SSL con la entidad bancaria...</div>
            </div>
          </div>

          <div id="gw-step-2" class="gateway-step-row">
            <div class="gateway-step-icon">
              <span class="material-symbols-outlined">verified</span>
            </div>
            <div>
              <div style="font-weight: 700;">2. Validación de fondos y 3D Secure / OTP</div>
              <div style="font-size: 0.78rem;">Verificando autorización con el emisor bancario...</div>
            </div>
          </div>

          <div id="gw-step-3" class="gateway-step-row">
            <div class="gateway-step-icon">
              <span class="material-symbols-outlined">check_circle</span>
            </div>
            <div>
              <div style="font-weight: 700;">3. Aprobación y comanda digital</div>
              <div style="font-size: 0.78rem;">Generando comprobante oficial y enviando pedido a cocina...</div>
            </div>
          </div>
        </div>

        <!-- Barra de progreso -->
        <div class="gateway-progress-track">
          <div id="gw-progress-bar" class="gateway-progress-fill"></div>
        </div>

        <div id="gw-status-message" style="text-align: center; margin-top: 1.25rem; font-size: 0.85rem; font-weight: 700; color: #855300;">
          Procesando pago con la red bancaria de Neiva...
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================
       MODAL 2: COMPROBANTE OFICIAL DE PAGO Y FACTURA ELECTRÓNICA
       ========================================================= -->
  <div id="payment-receipt-modal" class="pay-modal-backdrop">
    <div class="pay-modal-card" style="max-width: 620px;">
      <div class="pay-modal-header no-print">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
          <span class="material-symbols-outlined" style="color: #00682e; font-size: 28px;">verified</span>
          <div>
            <h3 style="margin: 0; font-family: var(--font-epilogue); font-size: 1.15rem; font-weight: 900; color: var(--color-on-surface);">
              ¡Transacción Aprobada Exitosamente!
            </h3>
            <span style="font-size: 0.75rem; color: #00682e; font-weight: 800;">
              Comprobante Oficial de Pago & Factura Electrónica
            </span>
          </div>
        </div>
        <button onclick="App.closeReceiptModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--color-on-surface-variant); cursor: pointer;">✕</button>
      </div>

      <div class="pay-modal-body" style="padding: 1.5rem;">
        <div id="printable-receipt" class="receipt-voucher-card">
          <!-- Encabezado del Recibo -->
          <div class="receipt-header-row">
            <div>
              <h2 style="margin: 0; font-family: var(--font-epilogue); font-size: 1.25rem; font-weight: 900; color: #b70011;">
                ANTOJO VIRTUAL
              </h2>
              <div style="font-size: 0.75rem; color: #64748b;">
                NIT: 901.458.921-4 • Neiva, Huila<br>
                Régimen Simple de Tributación • Impoconsumo 8%
              </div>
            </div>
            <div style="text-align: right;">
              <div class="receipt-success-badge">
                <span class="material-symbols-outlined" style="font-size: 16px;">check</span>
                <span>Aprobada</span>
              </div>
              <div id="receipt-inv-number" style="font-size: 0.8rem; font-weight: 800; color: #0f172a; margin-top: 4px;">
                FE-ANTOJO-2026-00001
              </div>
            </div>
          </div>

          <!-- Grilla de Información Técnica -->
          <div class="receipt-info-grid">
            <div>
              <div class="receipt-info-label">Referencia de Pago</div>
              <div id="receipt-ref-code" class="receipt-info-value">PAY-NV-20261021-8492</div>
            </div>
            <div>
              <div class="receipt-info-label">Código Autorización</div>
              <div id="receipt-auth-code" class="receipt-info-value">AUT-948123</div>
            </div>
            <div>
              <div class="receipt-info-label">Método / Banco</div>
              <div id="receipt-method-label" class="receipt-info-value">Bancolombia S.A. (PSE)</div>
            </div>
            <div>
              <div class="receipt-info-label">Fecha & Hora</div>
              <div id="receipt-datetime" class="receipt-info-value">2026-10-21 21:52</div>
            </div>
            <div>
              <div class="receipt-info-label">Cliente / Adquirente</div>
              <div id="receipt-customer-name" class="receipt-info-value">Mateo Rodríguez</div>
            </div>
            <div>
              <div class="receipt-info-label">Documento Identidad</div>
              <div id="receipt-customer-doc" class="receipt-info-value">CC 1075304128</div>
            </div>
          </div>

          <!-- Desglose de Valores -->
          <div class="receipt-totals-table">
            <div class="receipt-total-line">
              <span>Subtotal Productos</span>
              <strong id="receipt-subtotal">$0</strong>
            </div>
            <div id="receipt-discount-row" class="receipt-total-line" style="color: #15803d; display: none;">
              <span>Descuento Cupón</span>
              <strong id="receipt-discount">-$0</strong>
            </div>
            <div class="receipt-total-line">
              <span>Impuesto Nacional al Consumo (8%)</span>
              <strong id="receipt-tax">$0</strong>
            </div>
            <div class="receipt-total-line">
              <span>Servicio de Domicilio en Neiva</span>
              <strong id="receipt-delivery">$3.500</strong>
            </div>
            <div class="receipt-total-line">
              <span>Propina Repartidor</span>
              <strong id="receipt-tip">$1.000</strong>
            </div>
            <div class="receipt-total-line final">
              <span>TOTAL PAGADO</span>
              <span id="receipt-total" style="color: #b70011;">$0</span>
            </div>
          </div>

          <!-- CUFE DIAN -->
          <div class="receipt-cufe-box">
            <div style="font-weight: 800; margin-bottom: 2px;">CUFE (Código Único de Facturación Electrónica DIAN):</div>
            <div id="receipt-cufe-code">8f89c4501b87a9d04ef124803923a1e948c27940182390abcef182903810293849102839401829384910293849102938</div>
          </div>

          <!-- Código QR Dinámico Fiscal DIAN -->
          <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem;">
            <div id="receipt-qr-container" style="background: #ffffff; padding: 6px; border-radius: 6px; border: 1px solid #cbd5e1; flex-shrink: 0;">
              <canvas id="receipt-qr-canvas" width="90" height="90" style="display: block; width: 80px; height: 80px;"></canvas>
            </div>
            <div>
              <div style="font-weight: 800; font-size: 0.82rem; color: #0f172a; display: flex; align-items: center; gap: 0.35rem;">
                <span class="material-symbols-outlined" style="font-size: 16px; color: #16a34a;">verified</span>
                Validación Fiscal DIAN en Línea
              </div>
              <div style="font-size: 0.7rem; color: #64748b; margin-top: 2px; line-height: 1.35;">
                Escanea este código QR con la cámara de tu celular para verificar la firma digital y el registro tributario de este comprobante.
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="no-print" style="display: flex; gap: 0.75rem; margin-top: 1.5rem; justify-content: flex-end;">
          <button onclick="window.print()" class="btn-customize" style="background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); color: var(--color-on-surface);">
            <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
            <span>Imprimir Recibo</span>
          </button>
          <button onclick="App.goToTrackingAfterPayment()" class="btn-customize" style="background: var(--color-primary-neu); color: #ffffff; box-shadow: var(--color-primary-neu-shadow);">
            <span class="material-symbols-outlined" style="font-size: 18px;">moped</span>
            <span>Ver Rastreo en Vivo</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================
       MODAL 3: CALIFICACIÓN & RESEÑAS POST-ENTREGA (NEUMORFISMO)
       ========================================================= -->
  <div id="review-modal" class="modal-backdrop">
    <div class="modal-card" style="max-width: 520px;">
      <div class="modal-header">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
          <span class="material-symbols-outlined" style="color: #fea619; font-size: 32px;">stars</span>
          <div>
            <h3 style="font-family: var(--font-epilogue); font-size: 1.25rem; font-weight: 900; margin: 0; color: var(--color-on-surface);">
              ¿Cómo estuvo tu antojo?
            </h3>
            <span style="font-size: 0.78rem; color: var(--color-on-surface-variant);">
              Tu calificación apoya a los restaurantes y repartidores de Neiva
            </span>
          </div>
        </div>
        <button class="modal-close-btn" onclick="App.closeReviewModal()">✕</button>
      </div>

      <div class="modal-body" style="padding: 1.5rem;">
        
        <!-- Calificación de la Comida / Restaurante -->
        <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border-radius: var(--radius-md); padding: 1.15rem; margin-bottom: 1.15rem; text-align: center; border: 1px solid var(--color-border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--color-on-surface-variant); display: block; margin-bottom: 0.5rem;">
            1. Calidad & Sabor de la Comida
          </span>
          <div class="rating-stars" id="food-stars" style="display: flex; justify-content: center; gap: 0.45rem; font-size: 2.2rem; cursor: pointer; color: #fea619;">
            <span class="star-btn" data-val="1" onclick="App.setReviewRating('food', 1)">★</span>
            <span class="star-btn" data-val="2" onclick="App.setReviewRating('food', 2)">★</span>
            <span class="star-btn" data-val="3" onclick="App.setReviewRating('food', 3)">★</span>
            <span class="star-btn" data-val="4" onclick="App.setReviewRating('food', 4)">★</span>
            <span class="star-btn" data-val="5" onclick="App.setReviewRating('food', 5)">★</span>
          </div>
          <div id="food-rating-label" style="font-weight: 800; font-size: 0.85rem; color: var(--color-on-surface); margin-top: 0.35rem;">
            5.0 - ¡Espectacular sabor huilense!
          </div>
        </div>

        <!-- Calificación del Repartidor -->
        <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border-radius: var(--radius-md); padding: 1.15rem; margin-bottom: 1.15rem; text-align: center; border: 1px solid var(--color-border-subtle);">
          <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--color-on-surface-variant); display: block; margin-bottom: 0.5rem;">
            2. Servicio & Puntualidad del Domiciliario (Carlos Mendoza)
          </span>
          <div class="rating-stars" id="rider-stars" style="display: flex; justify-content: center; gap: 0.45rem; font-size: 2.2rem; cursor: pointer; color: #fea619;">
            <span class="star-btn" data-val="1" onclick="App.setReviewRating('rider', 1)">★</span>
            <span class="star-btn" data-val="2" onclick="App.setReviewRating('rider', 2)">★</span>
            <span class="star-btn" data-val="3" onclick="App.setReviewRating('rider', 3)">★</span>
            <span class="star-btn" data-val="4" onclick="App.setReviewRating('rider', 4)">★</span>
            <span class="star-btn" data-val="5" onclick="App.setReviewRating('rider', 5)">★</span>
          </div>
          <div id="rider-rating-label" style="font-weight: 800; font-size: 0.85rem; color: var(--color-on-surface); margin-top: 0.35rem;">
            5.0 - ¡Entrega rápida y comida bien caliente!
          </div>
        </div>

        <!-- Etiquetas Rápidas -->
        <div style="margin-bottom: 1.15rem;">
          <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: var(--color-on-surface-variant); display: block; margin-bottom: 0.5rem;">
            ¿Qué fue lo mejor? (Toca para seleccionar)
          </span>
          <div id="review-tags-container" style="display: flex; flex-wrap: wrap; gap: 0.45rem;">
            <span class="cat-chip active" onclick="this.classList.toggle('active')" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; cursor: pointer;">🔥 Comida Caliente</span>
            <span class="cat-chip active" onclick="this.classList.toggle('active')" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; cursor: pointer;">⚡ Entrega Rápida</span>
            <span class="cat-chip active" onclick="this.classList.toggle('active')" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; cursor: pointer;">🍔 Sabor Increíble</span>
            <span class="cat-chip" onclick="this.classList.toggle('active')" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; cursor: pointer;">🛵 Repartidor Amable</span>
            <span class="cat-chip" onclick="this.classList.toggle('active')" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; cursor: pointer;">✨ Empaque Impecable</span>
          </div>
        </div>

        <!-- Comentario Adicional -->
        <div style="margin-bottom: 1.25rem;">
          <textarea id="review-comment-input" class="form-input" rows="2" placeholder="Escribe un comentario o mensaje opcional..." style="box-shadow: var(--neu-inset-sm); width: 100%; border-radius: var(--radius-md); padding: 0.75rem; font-family: inherit; font-size: 0.85rem;"></textarea>
        </div>

        <button onclick="App.submitOrderReview()" class="btn-customize" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #fea619, #dc2626); color: #fff; font-size: 1rem; box-shadow: var(--color-primary-neu-shadow);">
          <span class="material-symbols-outlined">send</span>
          Enviar Calificación
        </button>

      </div>
    </div>
  </div>

  <!-- Scripts: Leaflet (Mapas), Auth (JWT) y App Principal -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script src="assets/js/auth.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
