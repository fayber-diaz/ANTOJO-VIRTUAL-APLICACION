/**
 * =========================================================
 * ANTOJO VIRTUAL - JavaScript Principal (Vanilla ES6)
 * Conectado a PHP REST API y MySQL en XAMPP
 * Neiva, Huila • Diseño Neumorfismo (Soft UI)
 * =========================================================
 */

// Sintetizador Nativo Web Audio API (Efectos de Sonido Acústicos)
const SoundFX = {
  ctx: null,
  enabled: localStorage.getItem('antojo_sound_enabled') !== 'false',

  init() {
    if (!this.ctx && (window.AudioContext || window.webkitAudioContext)) {
      this.ctx = new (window.AudioContext || window.webkitAudioContext)();
    }
  },

  playTone(freq, duration = 0.2, type = 'sine', gainVal = 0.15, delay = 0) {
    if (!this.enabled) return;
    try {
      this.init();
      if (!this.ctx) return;
      if (this.ctx.state === 'suspended') {
        this.ctx.resume();
      }

      const osc = this.ctx.createOscillator();
      const gain = this.ctx.createGain();
      osc.type = type;
      osc.frequency.setValueAtTime(freq, this.ctx.currentTime + delay);

      gain.gain.setValueAtTime(gainVal, this.ctx.currentTime + delay);
      gain.gain.exponentialRampToValueAtTime(0.0001, this.ctx.currentTime + delay + duration);

      osc.connect(gain);
      gain.connect(this.ctx.destination);

      osc.start(this.ctx.currentTime + delay);
      osc.stop(this.ctx.currentTime + delay + duration);
    } catch(e) {}
  },

  // 1. Acorde armónico de pedido confirmado / pagado
  playOrderPlaced() {
    this.playTone(523.25, 0.3, 'triangle', 0.22, 0.0); // C5
    this.playTone(659.25, 0.3, 'triangle', 0.22, 0.08); // E5
    this.playTone(783.99, 0.4, 'triangle', 0.25, 0.16); // G5
    this.playTone(1046.50, 0.55, 'sine', 0.28, 0.24); // C6
  },

  // 2. Chime suave de cocina al carbón prendida
  playCooking() {
    this.playTone(440.00, 0.2, 'sine', 0.18, 0.0); // A4
    this.playTone(554.37, 0.25, 'sine', 0.18, 0.1); // C#5
    this.playTone(659.25, 0.35, 'sine', 0.2, 0.2); // E5
  },

  // 3. Claxon doble de moto en camino
  playRiderOnWay() {
    this.playTone(880.00, 0.1, 'sawtooth', 0.15, 0.0);
    this.playTone(880.00, 0.12, 'sawtooth', 0.15, 0.12);
    this.playTone(1174.66, 0.35, 'triangle', 0.2, 0.24);
  },

  // 4. Fanfarria de entrega completada en puerta
  playDelivered() {
    this.playTone(523.25, 0.2, 'triangle', 0.2, 0.0); // C5
    this.playTone(659.25, 0.2, 'triangle', 0.2, 0.1); // E5
    this.playTone(783.99, 0.25, 'triangle', 0.25, 0.2); // G5
    this.playTone(1046.50, 0.7, 'sine', 0.35, 0.3); // C6
  },

  // 5. Alerta de cocina para el panel de administración
  playAdminAlert() {
    this.playTone(987.77, 0.18, 'sine', 0.25, 0.0);
    this.playTone(1318.51, 0.35, 'triangle', 0.3, 0.12);
  }
};

const App = {
  // Estado global de la aplicación
  state: {
    currentScreen: 'explorar', // 'explorar' | 'checkout' | 'rastreo'
    restaurants: [],
    dishes: [],
    categories: [],
    selectedSiteType: 'all',
    customizerOptions: { terminos: [], panes: [], toppings: [], salsas: [], sabores: [], bases_dulces: [], siropes: [], toppings_dulces: [], dulzura: [] },
    selectedCategory: 'all',
    selectedComuna: 'Comuna 4 - Centro',
    searchQuery: '',
    cart: [],
    appliedCoupon: null,
    currentOrder: null, // Pedido activo para rastreo
    leafletMap: null,
    leafletMarkers: {},
    leafletPolyline: null,
    leafletTileLayer: null,
    currentTileStyle: 'voyager',
    riderAnimTimer: null,
    riderAnimProgress: 0.15,
    lastKnownStatus: null,
    deferredPwaPrompt: null,
    reviewSelection: { food: 5, rider: 5 },
    isCustomizerOpen: false,
    activeDishForCustomizer: null,
    customizerSelection: {
      termino: '',
      bread: '',
      toppings: {},
      sauces: [],
      notes: ''
    },
    trackingInterval: null
  },

  // Inicialización de la aplicación
  async init() {
    this.loadCartFromStorage();
    await this.fetchCustomizerOptions();
    await this.fetchRestaurants();
    await this.fetchDishes();
    this.setupEventListeners();
    this.renderCartBadge();
    this.initAudioUI();

    // 1. Registro de PWA Service Worker
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js').catch(e => console.log('SW registration error:', e));
    }

    // 2. Manejo de instalación PWA en móviles
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      this.state.deferredPwaPrompt = e;
      const btn = document.getElementById('btn-install-pwa');
      if (btn) btn.style.display = 'inline-flex';
    });

    // Si hay un pedido guardado previamente en sesión/storage, cargarlo
    const savedOrderCode = localStorage.getItem('antojo_active_order_code') || '#NV-8492';
    if (savedOrderCode) {
      this.fetchOrderForTracking(savedOrderCode, false);
    }

    // Revisar si la URL tiene hash de pantalla
    const hash = window.location.hash.replace('#', '');
    if (['explorar', 'checkout', 'rastreo'].includes(hash)) {
      this.navigate(hash);
    }
  },

  // Guardar y cargar carrito en localStorage
  saveCartToStorage() {
    localStorage.setItem('antojo_cart', JSON.stringify(this.state.cart));
  },

  loadCartFromStorage() {
    try {
      const stored = localStorage.getItem('antojo_cart');
      if (stored) {
        this.state.cart = JSON.parse(stored);
      } else {
        // Carrito inicial predeterminado para demostración
        this.state.cart = [
          {
            id: 'burger-init-' + Date.now(),
            name: 'Hamburguesa Monumental Huilense 200g',
            restaurant: 'La Estación Burger',
            basePrice: 24900,
            quantity: 1,
            doneness: 'Tres Cuartos - 3/4 (Punto ideal de la casa)',
            bread: 'Pan Brioche Dorado Tradicional',
            toppings: [
              { name: 'Tocineta Ahumada Crunch (Extra)', price: 3500, count: 1 },
              { name: 'Huevo Frito Campesino Yema Tierna', price: 2000, count: 1 },
              { name: 'Queso Campesino Asado de Algeciras', price: 3000, count: 1 }
            ],
            sauces: ['Tártara Especial de la Abuela', 'BBQ Artesanal con Guayaba Huilense'],
            notes: 'Cebolla bien caramelizada por favor'
          },
          {
            id: 'papas-init-' + Date.now(),
            name: 'Papas Rústicas Romero & Paprika',
            restaurant: 'La Estación Burger',
            basePrice: 7500,
            quantity: 1,
            toppings: [],
            sauces: ['Tártara Especial de la Abuela'],
            notes: ''
          }
        ];
        this.saveCartToStorage();
      }
    } catch (e) {
      this.state.cart = [];
    }
  },

  // Peticiones a la API PHP
  async fetchRestaurants() {
    try {
      const res = await fetch(`api/restaurants.php?category=${encodeURIComponent(this.state.selectedCategory)}&search=${encodeURIComponent(this.state.searchQuery)}&format=json`, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.success) {
        this.state.restaurants = data.data;
        this.renderRestaurants();
      }
    } catch (err) {
      console.error('Error cargando restaurantes:', err);
    }
  },

  async fetchDishes() {
    try {
      const res = await fetch(`api/menu.php?category=${encodeURIComponent(this.state.selectedCategory)}&search=${encodeURIComponent(this.state.searchQuery)}&format=json`, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.success) {
        this.state.dishes = data.data;
        this.renderDishes();
      }
    } catch (err) {
      console.error('Error cargando platos:', err);
    }
  },

  async fetchCustomizerOptions() {
    try {
      const res = await fetch('api/menu.php?options=1&format=json', {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.success) {
        this.state.customizerOptions = data.options;
      }
    } catch (err) {
      console.error('Error cargando opciones de personalización:', err);
    }
  },

  // Navegación entre vistas
  navigate(screen) {
    this.state.currentScreen = screen;
    window.location.hash = screen;
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Actualizar tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.screen === screen);
    });

    // Mostrar/Ocultar secciones
    document.getElementById('screen-explorar').style.display = screen === 'explorar' ? 'block' : 'none';
    document.getElementById('screen-checkout').style.display = screen === 'checkout' ? 'block' : 'none';
    document.getElementById('screen-rastreo').style.display = screen === 'rastreo' ? 'block' : 'none';

    if (screen === 'checkout') {
      this.renderCheckout();
    } else if (screen === 'rastreo') {
      this.startTrackingPolling();
      setTimeout(() => {
        if (this.state.leafletMap) {
          this.state.leafletMap.invalidateSize();
        }
      }, 250);
    } else {
      this.stopTrackingPolling();
    }
  },

  // Renderizar Restaurantes Neumórficos
  renderRestaurants() {
    const container = document.getElementById('restaurants-container');
    if (!container) return;

    if (this.state.restaurants.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--neu-bg); border-radius: var(--radius-lg); box-shadow: var(--neu-inset); border: 1px solid var(--color-border-subtle);">
          <span class="material-symbols-outlined" style="font-size: 48px; color: #dc2626;">search_off</span>
          <h3 style="font-family: var(--font-epilogue); margin: 0.5rem 0; color: var(--color-on-surface);">No encontramos restaurantes para tu búsqueda</h3>
          <p style="color: var(--color-on-surface-variant); font-size: 0.9rem;">Prueba con otra palabra clave o selecciona otra categoría.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = this.state.restaurants.map(r => `
      <div class="dish-card" style="cursor: pointer;" onclick="App.filterByRestaurant('${r.name}')">
        <div class="dish-image-wrapper">
          <img src="${r.image}" alt="${r.name}" class="dish-img" loading="lazy">
          <div class="dish-badge">
            <span class="material-symbols-outlined" style="font-size: 16px; color: #fea619;">verified</span>
            ${r.badge}
          </div>
        </div>
        <div class="dish-content">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem;">
            <h3 class="dish-title" style="margin: 0;">${r.name}</h3>
            <div style="display: flex; align-items: center; gap: 0.2rem; background: var(--neu-bg); box-shadow: var(--neu-inset-sm); padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 800; color: #855300; border: 1px solid var(--color-border-subtle);">
              <span class="material-symbols-outlined" style="font-size: 15px; color: #fea619;">star</span>
              ${r.rating} (${r.reviews_count}+)
            </div>
          </div>
          <p class="dish-desc">${r.description}</p>
          <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.8rem; color: var(--color-on-surface-variant); font-weight: 700; margin-bottom: 0.85rem;">
            <span><span class="material-symbols-outlined" style="font-size: 15px;">schedule</span> ${r.delivery_time}</span>
            <span><span class="material-symbols-outlined" style="font-size: 15px;">moped</span> ${r.delivery_fee_formatted}</span>
            <span><span class="material-symbols-outlined" style="font-size: 15px;">location_on</span> ${r.address}</span>
          </div>
          <div style="background: var(--neu-bg); box-shadow: var(--neu-inset-sm); border: 1px solid var(--color-border-subtle); padding: 0.75rem 1rem; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.8rem; font-weight: 800; color: var(--color-on-surface);">⭐ ${r.star_dish}</span>
            <span style="font-weight: 900; color: var(--color-primary-container); font-size: 0.9rem;">${r.star_price_formatted}</span>
          </div>
        </div>
      </div>
    `).join('');
  },

  // Renderizar Platos Neumórficos
  renderDishes() {
    const container = document.getElementById('dishes-container');
    if (!container) return;

    if (this.state.dishes.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--neu-bg); border-radius: var(--radius-lg); box-shadow: var(--neu-inset); border: 1px solid var(--color-border-subtle);">
          <span class="material-symbols-outlined" style="font-size: 48px; color: #fea619;">fastfood</span>
          <h3 style="font-family: var(--font-epilogue); margin: 0.5rem 0; color: var(--color-on-surface);">No se encontraron platos</h3>
          <p style="color: var(--color-on-surface-variant); font-size: 0.9rem;">Intenta borrar el filtro de búsqueda para ver todo el menú.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = this.state.dishes.map(d => `
      <div class="dish-card">
        <div class="dish-image-wrapper">
          <img src="${d.image}" alt="${d.name}" class="dish-img" loading="lazy">
          ${d.is_featured ? `
            <div class="dish-badge" style="background: ${d.dish_type === 'dulce' ? 'linear-gradient(135deg, #e11d48, #be123c)' : 'rgba(220, 38, 38, 0.9)'}; color: white;">
              <span class="material-symbols-outlined" style="font-size: 15px; color: #fea619;">${d.dish_type === 'dulce' ? 'icecream' : 'local_fire_department'}</span>
              ${d.dish_type === 'dulce' ? 'Antojo Dulce' : 'Especialidad al Carbón'}
            </div>
          ` : ''}
        </div>
        <div class="dish-content">
          <span class="dish-rest-name" style="${d.dish_type === 'dulce' ? 'color: #be123c;' : ''}">${d.restaurant_name}</span>
          <h3 class="dish-title">${d.name}</h3>
          <p class="dish-desc">${d.description}</p>
          <div class="dish-tags">
            ${(d.tags_array || []).map(t => `<span class="dish-tag-item" style="${d.dish_type === 'dulce' ? 'background: #fff1f2; color: #be123c;' : ''}">${t}</span>`).join('')}
          </div>
          <div class="dish-footer">
            <span class="dish-price">${d.price_formatted}</span>
            <button class="btn-customize" onclick="App.openCustomizer(${d.id})" style="${d.dish_type === 'dulce' ? 'background: linear-gradient(135deg, #e11d48, #be123c); color: #fff; box-shadow: 3px 3px 10px rgba(225, 29, 72, 0.35);' : ''}">
              <span class="material-symbols-outlined" style="font-size: 18px;">${d.dish_type === 'dulce' ? 'cake' : 'tune'}</span>
              Personalizar
            </button>
          </div>
        </div>
      </div>
    `).join('');
  },

  // Modal de Personalización (Screen 3)
  openCustomizer(dishId) {
    const dish = this.state.dishes.find(d => d.id === dishId) || this.state.dishes[0];
    if (!dish) return;

    this.state.activeDishForCustomizer = dish;
    this.state.isCustomizerOpen = true;

    const isSweet = dish.dish_type === 'dulce' || ['helados', 'postres', 'waffles', 'malteadas'].includes(dish.category);

    if (isSweet) {
      const defaultSabor = (this.state.customizerOptions.sabores || []).find(s => s.is_default)?.name || 'Vainilla Francesa Bourbon (Artesanal)';
      const defaultBase = (this.state.customizerOptions.bases_dulces || []).find(b => b.is_default)?.name || 'Vaso Ecológico Especial para Postre';
      const defaultDulzura = (this.state.customizerOptions.dulzura || []).find(d => d.is_default)?.name || 'Dulzura Tradicional de la Casa (Equilibrado Perfecto)';

      this.state.customizerSelection = {
        isSweet: true,
        sabor: defaultSabor,
        base: defaultBase,
        siropes: ['Nutella Original Caliente y Fundida', 'Arequipe Artesanal Tibio de la Casa'],
        toppings: {},
        dulzura: defaultDulzura,
        notes: ''
      };
    } else {
      const defaultTermino = (this.state.customizerOptions.terminos || []).find(t => t.is_default)?.name || 'Tres Cuartos - 3/4 (Punto ideal de la casa)';
      const defaultBread = (this.state.customizerOptions.panes || []).find(p => p.is_default)?.name || 'Pan Brioche Dorado Tradicional';

      this.state.customizerSelection = {
        isSweet: false,
        termino: defaultTermino,
        bread: defaultBread,
        toppings: {},
        sauces: ['Tártara Especial de la Abuela', 'BBQ Artesanal con Guayaba Huilense'],
        notes: ''
      };
    }

    this.renderCustomizerModal();
    document.getElementById('customizer-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
  },

  closeCustomizer() {
    this.state.isCustomizerOpen = false;
    document.getElementById('customizer-modal').classList.remove('open');
    document.body.style.overflow = '';
  },

  renderCustomizerModal() {
    const dish = this.state.activeDishForCustomizer;
    if (!dish) return;

    document.getElementById('modal-dish-title').textContent = dish.name;
    document.getElementById('modal-dish-rest').textContent = dish.restaurant_name;

    const modalBody = document.getElementById('customizer-modal-body');
    if (!modalBody) return;

    const isSweet = this.state.customizerSelection.isSweet;

    if (isSweet) {
      modalBody.innerHTML = `
        <!-- Sección 1: Sabor de Helado / Relleno Dulce -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>🍨 1. Sabor de Gelato o Relleno Artesanal</span>
            <span style="font-size: 0.75rem; color: #e11d48; font-weight: 800;">Obligatorio</span>
          </div>
          <div class="modal-section-sub">Elaborado a diario con frutas frescas y cacao premium</div>
          <div id="modal-sabores-list">
            ${(this.state.customizerOptions.sabores || []).map(s => `
              <div class="option-row ${this.state.customizerSelection.sabor === s.name ? 'selected' : ''}" 
                   onclick="App.selectSweetSabor('${s.name}')">
                <div class="option-left">
                  <span class="material-symbols-outlined" style="color: ${this.state.customizerSelection.sabor === s.name ? '#e11d48' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                    ${this.state.customizerSelection.sabor === s.name ? 'radio_button_checked' : 'radio_button_unchecked'}
                  </span>
                  <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${s.name}</span>
                </div>
                <span class="option-price">${s.price_formatted}</span>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Sección 2: Base de Presentación o Masa -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>🧇 2. Base de Presentación o Tipo de Masa</span>
            <span style="font-size: 0.75rem; color: #e11d48; font-weight: 800;">Obligatorio</span>
          </div>
          <div class="modal-section-sub">Horneado fresco y crocante para tu postre</div>
          <div id="modal-bases-list">
            ${(this.state.customizerOptions.bases_dulces || []).map(b => `
              <div class="option-row ${this.state.customizerSelection.base === b.name ? 'selected' : ''}" 
                   onclick="App.selectSweetBase('${b.name}')">
                <div class="option-left">
                  <span class="material-symbols-outlined" style="color: ${this.state.customizerSelection.base === b.name ? '#e11d48' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                    ${this.state.customizerSelection.base === b.name ? 'radio_button_checked' : 'radio_button_unchecked'}
                  </span>
                  <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${b.name}</span>
                </div>
                <span class="option-price">${b.price_formatted}</span>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Sección 3: Siropes & Salsas Dulces Tibias -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>🍫 3. Siropes & Salsas Dulces Calientes</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Gratis</span>
          </div>
          <div class="modal-section-sub">Elige las salsas que bañarán tu postre</div>
          <div id="modal-siropes-list">
            ${(this.state.customizerOptions.siropes || []).map(sir => {
              const isChecked = this.state.customizerSelection.siropes.includes(sir.name);
              return `
                <div class="option-row ${isChecked ? 'selected' : ''}" onclick="App.toggleSweetSirope('${sir.name}')">
                  <div class="option-left">
                    <span class="material-symbols-outlined" style="color: ${isChecked ? '#e11d48' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                      ${isChecked ? 'check_box' : 'check_box_outline_blank'}
                    </span>
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${sir.name}</span>
                  </div>
                  <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Gratis</span>
                </div>
              `;
            }).join('')}
          </div>
        </div>

        <!-- Sección 4: Toppings Dulces Crujientes & Frutas -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>🍓 4. Toppings Crujientes, Frutas & Galletas</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Opcionales</span>
          </div>
          <div class="modal-section-sub">Galleta Oreo, almendras tostadas, fresas picadas o chantilly</div>
          <div id="modal-toppings-list">
            ${(this.state.customizerOptions.toppings_dulces || []).map(top => {
              const currentCount = this.state.customizerSelection.toppings[top.name]?.count || 0;
              return `
                <div class="option-row ${currentCount > 0 ? 'selected' : ''}">
                  <div class="option-left">
                    <span class="material-symbols-outlined" style="color: #fea619; font-size: 22px;">star</span>
                    <div>
                      <div style="font-weight: 800; font-size: 0.9rem; color: var(--color-on-surface);">${top.name}</div>
                      <div style="font-size: 0.8rem; color: #dc2626; font-weight: 700;">${top.price_formatted}</div>
                    </div>
                  </div>
                  <div class="counter-box">
                    <button class="counter-btn" onclick="App.adjustToppingCount('${top.name}', ${top.price}, -1)">-</button>
                    <span class="counter-value">${currentCount}</span>
                    <button class="counter-btn" onclick="App.adjustToppingCount('${top.name}', ${top.price}, 1)">+</button>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>

        <!-- Sección 5: Nivel de Dulzura -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>✨ 5. Nivel de Dulzura Deseado</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Opcional</span>
          </div>
          <div class="modal-section-sub">Ajusta el nivel de dulce a tu gusto</div>
          <div id="modal-dulzura-list">
            ${(this.state.customizerOptions.dulzura || []).map(d => `
              <div class="option-row ${this.state.customizerSelection.dulzura === d.name ? 'selected' : ''}" 
                   onclick="App.selectSweetDulzura('${d.name}')">
                <div class="option-left">
                  <span class="material-symbols-outlined" style="color: ${this.state.customizerSelection.dulzura === d.name ? '#e11d48' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                    ${this.state.customizerSelection.dulzura === d.name ? 'radio_button_checked' : 'radio_button_unchecked'}
                  </span>
                  <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${d.name}</span>
                </div>
                <span class="option-price">${d.price_formatted}</span>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Sección 6: Notas Especiales -->
        <div class="modal-section" style="border-bottom: none;">
          <div class="modal-section-title">
            <span>📝 6. Instrucciones para la Repostería / Heladería</span>
          </div>
          <div class="modal-section-sub">¿Salsas por separado? ¿Mensaje especial? Escríbelo aquí:</div>
          <textarea id="modal-notes-input" class="form-textarea" rows="2" placeholder="Ej: Salsa caliente en envase separado por favor...">${this.state.customizerSelection.notes || ''}</textarea>
        </div>
      `;
    } else {
      modalBody.innerHTML = `
        <!-- Sección 1: Término de la Carne -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>1. Término de la Carne al Carbón</span>
            <span style="font-size: 0.75rem; color: #dc2626; font-weight: 800;">Obligatorio</span>
          </div>
          <div class="modal-section-sub">Selecciona la jugosidad deseada en la parrilla</div>
          <div id="modal-terminos-list">
            ${(this.state.customizerOptions.terminos || []).map(t => `
              <div class="option-row ${this.state.customizerSelection.termino === t.name ? 'selected' : ''}" 
                   onclick="App.selectTermino('${t.name}')">
                <div class="option-left">
                  <span class="material-symbols-outlined" style="color: ${this.state.customizerSelection.termino === t.name ? '#dc2626' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                    ${this.state.customizerSelection.termino === t.name ? 'radio_button_checked' : 'radio_button_unchecked'}
                  </span>
                  <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${t.name}</span>
                </div>
                <span class="option-price">${t.price_formatted}</span>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Sección 2: Tipo de Pan Artesanal -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>2. Selección de Pan Artesanal</span>
            <span style="font-size: 0.75rem; color: #dc2626; font-weight: 800;">Obligatorio</span>
          </div>
          <div class="modal-section-sub">Horneado fresco a diario en panadería local</div>
          <div id="modal-panes-list">
            ${(this.state.customizerOptions.panes || []).map(p => `
              <div class="option-row ${this.state.customizerSelection.bread === p.name ? 'selected' : ''}" 
                   onclick="App.selectBread('${p.name}')">
                <div class="option-left">
                  <span class="material-symbols-outlined" style="color: ${this.state.customizerSelection.bread === p.name ? '#dc2626' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                    ${this.state.customizerSelection.bread === p.name ? 'radio_button_checked' : 'radio_button_unchecked'}
                  </span>
                  <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${p.name}</span>
                </div>
                <span class="option-price">${p.price_formatted}</span>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Sección 3: Toppings Extras con Contador -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>3. Toppings & Adiciones Especiales</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Opcionales</span>
          </div>
          <div class="modal-section-sub">Agrega tocineta, quesos asados o doble carne</div>
          <div id="modal-toppings-list">
            ${(this.state.customizerOptions.toppings || []).map(top => {
              const currentCount = this.state.customizerSelection.toppings[top.name]?.count || 0;
              return `
                <div class="option-row ${currentCount > 0 ? 'selected' : ''}">
                  <div class="option-left">
                    <span class="material-symbols-outlined" style="color: #fea619; font-size: 22px;">check_circle</span>
                    <div>
                      <div style="font-weight: 800; font-size: 0.9rem; color: var(--color-on-surface);">${top.name}</div>
                      <div style="font-size: 0.8rem; color: #dc2626; font-weight: 700;">${top.price_formatted}</div>
                    </div>
                  </div>
                  <div class="counter-box">
                    <button class="counter-btn" onclick="App.adjustToppingCount('${top.name}', ${top.price}, -1)">-</button>
                    <span class="counter-value">${currentCount}</span>
                    <button class="counter-btn" onclick="App.adjustToppingCount('${top.name}', ${top.price}, 1)">+</button>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>

        <!-- Sección 4: Salsas Colombianas de la Casa -->
        <div class="modal-section">
          <div class="modal-section-title">
            <span>4. Salsas Artesanales de la Casa</span>
            <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Gratis</span>
          </div>
          <div class="modal-section-sub">Elige las salsas que acompañarán tu orden</div>
          <div id="modal-salsas-list">
            ${(this.state.customizerOptions.salsas || []).map(s => {
              const isChecked = this.state.customizerSelection.sauces.includes(s.name);
              return `
                <div class="option-row ${isChecked ? 'selected' : ''}" onclick="App.toggleSauce('${s.name}')">
                  <div class="option-left">
                    <span class="material-symbols-outlined" style="color: ${isChecked ? '#dc2626' : 'var(--color-on-surface-variant)'}; font-size: 22px;">
                      ${isChecked ? 'check_box' : 'check_box_outline_blank'}
                    </span>
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${s.name}</span>
                  </div>
                  <span style="font-size: 0.75rem; color: #1a8340; font-weight: 800;">Gratis</span>
                </div>
              `;
            }).join('')}
          </div>
        </div>

        <!-- Sección 5: Notas Especiales -->
        <div class="modal-section" style="border-bottom: none;">
          <div class="modal-section-title">
            <span>5. Notas para el Maestro Parrillero</span>
          </div>
          <div class="modal-section-sub">¿Sin cebolla? ¿Salsa aparte? Escríbelo aquí:</div>
          <textarea id="modal-notes-input" class="form-textarea" rows="2" placeholder="Ej: Sin cebolla, salsas en recipientes separados por favor...">${this.state.customizerSelection.notes || ''}</textarea>
        </div>
      `;
    }

    this.calculateModalTotal();
  },

  selectSweetSabor(name) {
    this.state.customizerSelection.sabor = name;
    this.renderCustomizerModal();
  },

  selectSweetBase(name) {
    this.state.customizerSelection.base = name;
    this.renderCustomizerModal();
  },

  toggleSweetSirope(name) {
    const idx = this.state.customizerSelection.siropes.indexOf(name);
    if (idx > -1) {
      this.state.customizerSelection.siropes.splice(idx, 1);
    } else {
      this.state.customizerSelection.siropes.push(name);
    }
    this.renderCustomizerModal();
  },

  selectSweetDulzura(name) {
    this.state.customizerSelection.dulzura = name;
    this.renderCustomizerModal();
  },

  selectTermino(name) {
    this.state.customizerSelection.termino = name;
    this.renderCustomizerModal();
  },

  selectBread(name) {
    this.state.customizerSelection.bread = name;
    this.renderCustomizerModal();
  },

  adjustToppingCount(name, price, delta) {
    const curr = this.state.customizerSelection.toppings[name]?.count || 0;
    const next = Math.max(0, curr + delta);
    if (next === 0) {
      delete this.state.customizerSelection.toppings[name];
    } else {
      this.state.customizerSelection.toppings[name] = { price, count: next };
    }
    this.renderCustomizerModal();
  },

  toggleSauce(name) {
    const idx = this.state.customizerSelection.sauces.indexOf(name);
    if (idx > -1) {
      this.state.customizerSelection.sauces.splice(idx, 1);
    } else {
      this.state.customizerSelection.sauces.push(name);
    }
    this.renderCustomizerModal();
  },

  calculateModalTotal() {
    const dish = this.state.activeDishForCustomizer;
    if (!dish) return 0;

    let total = dish.price;

    if (this.state.customizerSelection.isSweet) {
      const selectedBaseObj = (this.state.customizerOptions.bases_dulces || []).find(b => b.name === this.state.customizerSelection.base);
      if (selectedBaseObj) {
        total += (selectedBaseObj.price || 0);
      }
      const selectedSaborObj = (this.state.customizerOptions.sabores || []).find(s => s.name === this.state.customizerSelection.sabor);
      if (selectedSaborObj) {
        total += (selectedSaborObj.price || 0);
      }
      for (const key in this.state.customizerSelection.toppings) {
        const top = this.state.customizerSelection.toppings[key];
        total += (top.price * top.count);
      }
    } else {
      const selectedBreadObj = (this.state.customizerOptions.panes || []).find(p => p.name === this.state.customizerSelection.bread);
      if (selectedBreadObj) {
        total += selectedBreadObj.price;
      }
      for (const key in this.state.customizerSelection.toppings) {
        const top = this.state.customizerSelection.toppings[key];
        total += (top.price * top.count);
      }
    }

    const formatted = '$' + total.toLocaleString('es-CO');
    document.getElementById('modal-total-display').textContent = formatted;
    return total;
  },

  confirmAddToCartFromModal() {
    const dish = this.state.activeDishForCustomizer;
    if (!dish) return;

    const notes = document.getElementById('modal-notes-input')?.value || '';

    const toppingsArray = [];
    for (const name in this.state.customizerSelection.toppings) {
      const t = this.state.customizerSelection.toppings[name];
      toppingsArray.push({ name, price: t.price, count: t.count });
    }

    const isSweet = this.state.customizerSelection.isSweet;

    const cartItem = {
      id: 'item-' + Date.now(),
      name: dish.name,
      restaurant: dish.restaurant_name,
      basePrice: dish.price,
      quantity: 1,
      isSweet: isSweet,
      doneness: isSweet ? '' : this.state.customizerSelection.termino,
      bread: isSweet ? '' : this.state.customizerSelection.bread,
      sabor: isSweet ? this.state.customizerSelection.sabor : '',
      base: isSweet ? this.state.customizerSelection.base : '',
      toppings: toppingsArray,
      sauces: isSweet ? [...this.state.customizerSelection.siropes] : [...this.state.customizerSelection.sauces],
      dulzura: isSweet ? this.state.customizerSelection.dulzura : '',
      notes: notes
    };

    this.state.cart.unshift(cartItem);
    this.saveCartToStorage();
    this.renderCartBadge();
    this.closeCustomizer();
    this.showToast(`¡${dish.name} agregado a tu orden!`);
  },

  // Manejo del Carrito
  renderCartBadge() {
    const totalUnits = this.state.cart.reduce((sum, it) => sum + it.quantity, 0);
    const badge = document.getElementById('cart-badge-count');
    if (badge) {
      badge.textContent = totalUnits;
    }
  },

  updateCartItemQty(index, delta) {
    if (this.state.cart[index]) {
      this.state.cart[index].quantity += delta;
      if (this.state.cart[index].quantity <= 0) {
        this.state.cart.splice(index, 1);
      }
      this.saveCartToStorage();
      this.renderCartBadge();
      if (this.state.currentScreen === 'checkout') {
        this.renderCheckout();
      }
    }
  },

  removeCartItem(index) {
    this.state.cart.splice(index, 1);
    this.saveCartToStorage();
    this.renderCartBadge();
    if (this.state.currentScreen === 'checkout') {
      this.renderCheckout();
    }
    this.showToast('Producto eliminado del carrito');
  },

  // Pantalla de Checkout Neumórfica
  renderCheckout() {
    const itemsList = document.getElementById('checkout-items-list');
    if (!itemsList) return;

    if (this.state.cart.length === 0) {
      itemsList.innerHTML = `
        <div style="text-align: center; padding: 2.5rem; color: var(--color-on-surface-variant);">
          <span class="material-symbols-outlined" style="font-size: 48px; color: #fea619;">shopping_cart</span>
          <h3 style="font-family: var(--font-epilogue); margin-top: 0.5rem; color: var(--color-on-surface);">Tu carrito está vacío</h3>
          <p style="font-size: 0.9rem; margin-bottom: 1.25rem;">Explora los deliciosos platos de Neiva y agrégalos a tu orden.</p>
          <button class="btn-customize" onclick="App.navigate('explorar')">Ver Menú Completo</button>
        </div>
      `;
      this.updateCheckoutTotals(0, 0, 0, 0, 0);
      return;
    }

    let subtotalProducts = 0;
    let subtotalToppings = 0;

    itemsList.innerHTML = this.state.cart.map((item, idx) => {
      let toppingsCost = 0;
      if (item.toppings && Array.isArray(item.toppings)) {
        item.toppings.forEach(t => {
          toppingsCost += ((t.price || 0) * (t.count || 1));
        });
      }
      const itemSubtotal = (item.basePrice + toppingsCost) * item.quantity;
      subtotalProducts += (item.basePrice * item.quantity);
      subtotalToppings += (toppingsCost * item.quantity);

      return `
        <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
          <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <span style="font-weight: 900; color: #dc2626;">${item.quantity}x</span>
              <h4 style="font-family: var(--font-epilogue); font-size: 1.1rem; font-weight: 800; color: var(--color-on-surface);">${item.name}</h4>
            </div>
            <span style="font-size: 0.8rem; font-weight: 800; color: #fea619; text-transform: uppercase;">${item.restaurant}</span>

            ${item.isSweet ? `
              ${item.sabor ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">🍨 <strong>Sabor / Gelato:</strong> ${item.sabor}</div>` : ''}
              ${item.base ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant);">🧇 <strong>Base / Masa:</strong> ${item.base}</div>` : ''}
              ${item.dulzura ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant);">✨ <strong>Dulzura:</strong> ${item.dulzura}</div>` : ''}
            ` : `
              ${item.doneness ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">🥩 <strong>Término:</strong> ${item.doneness}</div>` : ''}
              ${item.bread ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant);">🍞 <strong>Pan:</strong> ${item.bread}</div>` : ''}
            `}

            ${item.toppings && item.toppings.length > 0 ? `
              <div style="margin-top: 0.35rem; font-size: 0.8rem; color: var(--color-on-surface);">
                <strong>${item.isSweet ? 'Toppings dulces:' : 'Toppings extras:'}</strong>
                ${item.toppings.map(t => `<span class="dish-tag-item" style="margin-right:4px;">+ ${t.name} ($${(t.price).toLocaleString('es-CO')})</span>`).join('')}
              </div>
            ` : ''}

            ${item.sauces && item.sauces.length > 0 ? `
              <div style="font-size: 0.8rem; color: ${item.isSweet ? '#e11d48' : '#1a8340'}; margin-top: 0.25rem;">
                <strong>${item.isSweet ? 'Siropes dulces:' : 'Salsas:'}</strong> ${item.sauces.join(', ')}
              </div>
            ` : ''}

            ${item.notes ? `<div style="font-size: 0.8rem; color: #fea619; font-style: italic; margin-top: 0.25rem;">📝 "${item.notes}"</div>` : ''}
          </div>

          <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
            <span style="font-family: var(--font-epilogue); font-weight: 900; font-size: 1.15rem; color: var(--color-on-surface);">
              $${itemSubtotal.toLocaleString('es-CO')}
            </span>
            <div class="counter-box" style="scale: 0.95;">
              <button class="counter-btn" onclick="App.updateCartItemQty(${idx}, -1)">-</button>
              <span class="counter-value">${item.quantity}</span>
              <button class="counter-btn" onclick="App.updateCartItemQty(${idx}, 1)">+</button>
            </div>
            <button onclick="App.removeCartItem(${idx})" style="background:none; border:none; color:#dc2626; font-size:0.75rem; font-weight:800; cursor:pointer;">
              Eliminar
            </button>
          </div>
        </div>
      `;
    }).join('');

    const discount = this.state.appliedCoupon ? (this.state.appliedCoupon.discount_amount || 5000) : 0;
    const deliveryFee = 3500;
    const tip = 1000;
    const total = Math.max(0, subtotalProducts + subtotalToppings - discount + deliveryFee + tip);

    this.updateCheckoutTotals(subtotalProducts + subtotalToppings, discount, deliveryFee, tip, total);
  },

  updateCheckoutTotals(subtotal, discount, deliveryFee, tip, total) {
    document.getElementById('summary-subtotal').textContent = '$' + subtotal.toLocaleString('es-CO');
    document.getElementById('summary-discount').textContent = discount > 0 ? '-$' + discount.toLocaleString('es-CO') : '$0';
    document.getElementById('summary-delivery').textContent = '$' + deliveryFee.toLocaleString('es-CO');
    document.getElementById('summary-tip').textContent = '$' + tip.toLocaleString('es-CO');
    document.getElementById('summary-total').textContent = '$' + total.toLocaleString('es-CO');
    document.getElementById('btn-confirm-total').textContent = '$' + total.toLocaleString('es-CO');

    const discountRow = document.getElementById('summary-discount-row');
    if (discountRow) {
      discountRow.style.display = discount > 0 ? 'flex' : 'none';
    }
  },

  // Aplicar cupón
  async applyCouponCode() {
    const input = document.getElementById('coupon-input');
    const code = (input?.value || '').trim();

    if (!code) {
      this.showToast('Ingresa un código de cupón (ej: ANTOJONEIVA5K)');
      return;
    }

    try {
      const subtotal = this.state.cart.reduce((acc, curr) => acc + (curr.basePrice * curr.quantity), 0);
      const res = await fetch('api/coupons.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code, subtotal: subtotal })
      });
      const data = await res.json();

      if (data.success) {
        this.state.appliedCoupon = data;
        this.showToast(data.message);
        document.getElementById('coupon-applied-tag').style.display = 'flex';
        document.getElementById('coupon-applied-code').textContent = data.code;
        this.renderCheckout();
      } else {
        this.showToast(data.message || 'Cupón inválido');
      }
    } catch (err) {
      this.showToast('Error validando cupón en el servidor');
    }
  },

  removeCoupon() {
    this.state.appliedCoupon = null;
    document.getElementById('coupon-applied-tag').style.display = 'none';
    const input = document.getElementById('coupon-input');
    if (input) input.value = '';
    this.renderCheckout();
    this.showToast('Cupón promocional removido');
  },

  // Confirmar Pedido y Procesar en la Pasarela de Pagos Multibanco
  async submitOrder() {
    if (this.state.cart.length === 0) {
      this.showToast('Agrega productos al carrito antes de confirmar.');
      return;
    }

    const authUser = (typeof Auth !== 'undefined' && Auth.isAuthenticated()) ? Auth.getUser() : null;

    const customerName    = document.getElementById('customer-name')?.value    || authUser?.name    || 'Mateo Rodríguez';
    const customerPhone   = document.getElementById('customer-phone')?.value   || authUser?.phone   || '+57 314 289 9012';
    const customerEmail   = document.getElementById('pse-email')?.value        || authUser?.email   || 'mateo@gmail.com';
    const deliveryAddress = document.getElementById('delivery-address')?.value || authUser?.address || 'Cra. 5 #18-42, Centro';
    const comuna          = document.getElementById('delivery-comuna')?.value  || authUser?.comuna  || this.state.selectedComuna;
    const instructions    = document.getElementById('delivery-instructions')?.value || '';
    const paymentMethod   = document.querySelector('.pay-card.active')?.dataset.method || 'card';
    const cashChange      = document.getElementById('cash-change-select')?.value || 'exacto';

    // Recolectar datos técnicos del método seleccionado
    const paymentData = {
      method: paymentMethod
    };

    if (paymentMethod === 'card') {
      const rawCard = (document.getElementById('card-input-number')?.value || '4000 1234 5678 4242').replace(/\s+/g, '');
      paymentData.card_number = rawCard;
      paymentData.card_last_four = rawCard.slice(-4) || '4242';
      paymentData.cardholder_name = document.getElementById('card-input-name')?.value || 'MATEO RODRÍGUEZ';
      paymentData.exp_date = document.getElementById('card-input-exp')?.value || '12/28';
      paymentData.installments = parseInt(document.getElementById('card-input-installments')?.value || '1', 10);
      paymentData.card_brand = this.detectCardBrand(rawCard);
      paymentData.save_card = document.getElementById('card-save-checkbox')?.checked || false;
    } else if (paymentMethod === 'pse') {
      const bankSelect = document.getElementById('pse-bank-select');
      paymentData.bank_id = parseInt(bankSelect?.value || '1', 10);
      paymentData.bank_name = bankSelect?.options[bankSelect.selectedIndex]?.text || 'Bancolombia S.A.';
      paymentData.payer_person_type = document.querySelector('input[name="pse_person_type"]:checked')?.value || 'natural';
      paymentData.payer_doc_type = document.getElementById('pse-doc-type')?.value || 'CC';
      paymentData.payer_doc_number = document.getElementById('pse-doc-number')?.value || '1075304128';
      paymentData.payer_email = customerEmail;
    } else if (paymentMethod === 'nequi') {
      paymentData.phone_number = document.getElementById('nequi-phone-input')?.value || customerPhone;
      paymentData.bank_name = 'Nequi (Bancolombia)';
    } else if (paymentMethod === 'daviplata') {
      paymentData.phone_number = document.getElementById('daviplata-phone-input')?.value || customerPhone;
      paymentData.payer_doc_number = document.getElementById('daviplata-doc-input')?.value || '1075304128';
      paymentData.bank_name = 'Daviplata (Davivienda)';
    } else if (paymentMethod === 'cash_efectivo') {
      paymentData.cash_change_for = cashChange;
      paymentData.bank_name = 'Efectivo contra Entrega';
    } else if (paymentMethod === 'cash_datafono') {
      paymentData.bank_name = 'Datáfono inalámbrico en sitio';
    }

    const payload = {
      customer_name:    customerName,
      customer_phone:   customerPhone,
      customer_email:   customerEmail,
      delivery_address: deliveryAddress,
      comuna:           comuna,
      delivery_timing:  'immediate',
      instructions:     instructions,
      payment_method:   paymentMethod,
      cash_change_for:  cashChange,
      payment_data:     paymentData,
      coupon_code:      this.state.appliedCoupon?.code || '',
      items:            this.state.cart
    };

    // Si es pago online o con billetera, abrimos la pasarela de pagos animada
    if (['card', 'pse', 'nequi', 'daviplata'].includes(paymentMethod)) {
      this.openPaymentGatewaySimulation(payload, paymentData);
    } else {
      // Contra entrega procesa directo
      await this.executeOrderPlacement(payload, null);
    }
  },

  // Simulación Interactiva de la Pasarela Bancaria
  openPaymentGatewaySimulation(payload, paymentData) {
    const modal = document.getElementById('payment-gateway-modal');
    if (!modal) return;

    const totalEl = document.getElementById('summary-total');
    const totalText = totalEl ? totalEl.textContent : '$0';

    let methodLabel = 'Tarjeta VISA •••• ' + (paymentData.card_last_four || '4242');
    if (paymentData.method === 'pse') {
      methodLabel = 'PSE - ' + (paymentData.bank_name || 'Bancolombia');
    } else if (paymentData.method === 'nequi') {
      methodLabel = 'Nequi Directo (' + (paymentData.phone_number || '') + ')';
    } else if (paymentData.method === 'daviplata') {
      methodLabel = 'Daviplata (' + (paymentData.phone_number || '') + ')';
    }

    document.getElementById('gw-modal-method-text').textContent = methodLabel;
    document.getElementById('gw-modal-amount-text').textContent = totalText;

    // Resetear estados del stepper
    const step1 = document.getElementById('gw-step-1');
    const step2 = document.getElementById('gw-step-2');
    const step3 = document.getElementById('gw-step-3');
    const bar = document.getElementById('gw-progress-bar');
    const statusMsg = document.getElementById('gw-status-message');

    step1.className = 'gateway-step-row active';
    step2.className = 'gateway-step-row';
    step3.className = 'gateway-step-row';
    bar.style.width = '20%';
    statusMsg.textContent = 'Conectando con la red bancaria segura...';

    modal.classList.add('open');

    // Secuencia de animación de la pasarela
    setTimeout(() => {
      step1.className = 'gateway-step-row done';
      step2.className = 'gateway-step-row active';
      bar.style.width = '65%';
      statusMsg.textContent = 'Validando fondos y seguridad 3D Secure / OTP...';

      setTimeout(async () => {
        step2.className = 'gateway-step-row done';
        step3.className = 'gateway-step-row active';
        bar.style.width = '95%';
        statusMsg.textContent = 'Transacción autorizada. Guardando comanda y factura...';

        // Ejecutar envío real a la API PHP y Base de Datos MySQL
        await this.executeOrderPlacement(payload, () => {
          step3.className = 'gateway-step-row done';
          bar.style.width = '100%';
          statusMsg.textContent = '¡Pago Aprobado con Éxito!';
          setTimeout(() => {
            this.closePaymentModal();
          }, 600);
        });

      }, 1000);
    }, 900);
  },

  // Ejecución del POST hacia la API
  async executeOrderPlacement(payload, onBeforeFinish) {
    const confirmBtn = document.getElementById('btn-confirm-order-action');
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Procesando con MySQL...';
    }

    try {
      const fetcher = (typeof Auth !== 'undefined') ? Auth.fetch.bind(Auth) : fetch;
      const res = await fetcher('api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        if (typeof onBeforeFinish === 'function') {
          onBeforeFinish();
        }

        localStorage.setItem('antojo_active_order_code', data.order_code);
        this.state.cart = [];
        this.saveCartToStorage();
        this.renderCartBadge();

        // Mostrar recibo oficial y factura electrónica
        setTimeout(() => {
          this.showPaymentReceipt(data);
        }, 350);

      } else {
        this.closePaymentModal();
        this.showToast(data.message || 'Transacción rechazada por el banco');
      }
    } catch (err) {
      this.closePaymentModal();
      this.showToast('Error de comunicación con la pasarela de pagos');
    } finally {
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Confirmar Pedido';
      }
    }
  },

  closePaymentModal() {
    const modal = document.getElementById('payment-gateway-modal');
    if (modal) modal.classList.remove('open');
  },

  // Mostrar el comprobante oficial de pago y factura electrónica
  showPaymentReceipt(orderResult) {
    const receiptModal = document.getElementById('payment-receipt-modal');
    if (!receiptModal) return;

    const p = orderResult.payment || {};
    const inv = orderResult.invoice || {};

    const customerName = document.getElementById('customer-name')?.value || 'Mateo Rodríguez';
    const docType = document.getElementById('pse-doc-type')?.value || 'CC';
    const docNum = document.getElementById('pse-doc-number')?.value || '1075304128';

    document.getElementById('receipt-inv-number').textContent = inv.invoice_number || 'FE-ANTOJO-2026-00001';
    document.getElementById('receipt-ref-code').textContent = p.transaction_reference || 'PAY-NV-' + Date.now();
    document.getElementById('receipt-auth-code').textContent = p.authorization_code || 'AUT-' + Math.floor(100000 + Math.random() * 900000);
    document.getElementById('receipt-method-label').textContent = p.method_label || 'Bancolombia S.A. (PSE)';
    
    const now = new Date();
    document.getElementById('receipt-datetime').textContent = now.toLocaleDateString('es-CO') + ' ' + now.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    document.getElementById('receipt-customer-name').textContent = customerName;
    document.getElementById('receipt-customer-doc').textContent = `${docType} ${docNum}`;

    const subtotalText = document.getElementById('summary-subtotal')?.textContent || '$0';
    const deliveryText = document.getElementById('summary-delivery')?.textContent || '$3.500';
    const tipText = document.getElementById('summary-tip')?.textContent || '$1.000';
    const discountText = document.getElementById('summary-discount')?.textContent || '$0';

    document.getElementById('receipt-subtotal').textContent = subtotalText;
    document.getElementById('receipt-delivery').textContent = deliveryText;
    document.getElementById('receipt-tip').textContent = tipText;
    document.getElementById('receipt-total').textContent = orderResult.total_formatted || '$0';

    // Impoconsumo 8%
    const rawTotal = orderResult.total || 0;
    const taxApprox = Math.round(rawTotal * 0.08 / 1.08);
    document.getElementById('receipt-tax').textContent = '$' + taxApprox.toLocaleString('es-CO');

    if (this.state.appliedCoupon) {
      document.getElementById('receipt-discount-row').style.display = 'flex';
      document.getElementById('receipt-discount').textContent = discountText;
    } else {
      document.getElementById('receipt-discount-row').style.display = 'none';
    }

    if (inv.cufe_code) {
      document.getElementById('receipt-cufe-code').textContent = inv.cufe_code;
    }

    // Renderizar código QR dinámico oficial DIAN
    this.renderReceiptQRCode(inv.cufe_code || '8f89c4501b87a9d04ef124803923a1e948c27940182390abcef1829038102938');
    SoundFX.playOrderPlaced();

    receiptModal.classList.add('open');
  },

  closeReceiptModal() {
    const modal = document.getElementById('payment-receipt-modal');
    if (modal) modal.classList.remove('open');
  },

  goToTrackingAfterPayment() {
    this.closeReceiptModal();
    const savedOrderCode = localStorage.getItem('antojo_active_order_code') || '#NV-8492';
    this.fetchOrderForTracking(savedOrderCode, true);
  },

  // Detección automática de franquicia de tarjeta
  detectCardBrand(num) {
    const clean = (num || '').replace(/\D/g, '');
    if (/^4/.test(clean)) return 'visa';
    if (/^(5[1-5]|2[2-7])/.test(clean)) return 'mastercard';
    if (/^(34|37)/.test(clean)) return 'amex';
    if (/^(30[0-5]|36|38)/.test(clean)) return 'diners';
    return 'visa';
  },

  // Rastreo en Vivo (Live Tracking)
  async fetchOrderForTracking(orderCode, navigateToScreen = false) {
    try {
      const res = await fetch(`api/tracking.php?code=${encodeURIComponent(orderCode)}`);
      const data = await res.json();

      if (data.success) {
        const prevStatus = this.state.lastKnownStatus;
        this.state.lastKnownStatus = data.status;

        // Si el estado cambió, reproducir efecto acústico correspondiente
        if (prevStatus && prevStatus !== data.status) {
          if (data.status === 'recibido') SoundFX.playOrderPlaced();
          else if (data.status === 'preparando') SoundFX.playCooking();
          else if (data.status === 'en_camino') SoundFX.playRiderOnWay();
          else if (data.status === 'entregado') {
            SoundFX.playDelivered();
            setTimeout(() => {
              this.openReviewModal();
            }, 1200);
          }
        }

        this.state.currentOrder = data;
        this.renderTrackingScreen();
        if (navigateToScreen) {
          this.navigate('rastreo');
        }
      }
    } catch (err) {
      console.error('Error al obtener estado de rastreo:', err);
    }
  },

  startTrackingPolling() {
    this.stopTrackingPolling();
    this.state.trackingInterval = setInterval(() => {
      const code = this.state.currentOrder?.order_code || localStorage.getItem('antojo_active_order_code') || '#NV-8492';
      this.fetchOrderForTracking(code, false);
    }, 5000);
  },

  stopTrackingPolling() {
    if (this.state.trackingInterval) {
      clearInterval(this.state.trackingInterval);
      this.state.trackingInterval = null;
    }
  },

  renderTrackingScreen() {
    const order = this.state.currentOrder;
    if (!order) return;

    // Código y títulos de la orden
    const codeEl = document.getElementById('track-order-code');
    if (codeEl) codeEl.textContent = order.order_code;

    const currentBadge = document.getElementById('track-current-badge');
    if (currentBadge) currentBadge.textContent = order.order_code;

    const searchInput = document.getElementById('track-search-code-input');
    if (searchInput && !searchInput.value) searchInput.value = order.order_code;

    const titleEl = document.getElementById('track-status-title');
    if (titleEl) titleEl.textContent = order.status_title;

    const subEl = document.getElementById('track-status-sub');
    if (subEl) subEl.textContent = order.status_subtext;

    const etaEl = document.getElementById('track-eta');
    if (etaEl) etaEl.textContent = order.eta_minutes;

    const progressBar = document.getElementById('track-progress-bar');
    if (progressBar) progressBar.style.width = (order.progress_percent || 25) + '%';

    // Stepper de 4 pasos
    const stepIdx = order.step_index !== undefined ? order.step_index : 0;
    for (let i = 0; i <= 3; i++) {
      const stepEl = document.getElementById(`track-step-${i}`);
      if (stepEl) {
        stepEl.classList.remove('done', 'active');
        if (i < stepIdx) {
          stepEl.classList.add('done');
        } else if (i === stepIdx) {
          stepEl.classList.add('active');
        }
      }
    }

    // Actualización de Telemetría HUD sobre el mapa
    const hudSpeed = document.getElementById('hud-speed');
    if (hudSpeed) {
      hudSpeed.textContent = `${order.telemetry?.speed_kmh !== undefined ? order.telemetry.speed_kmh : (order.status === 'en_camino' ? 32 : 0)} km/h`;
    }

    const hudDistance = document.getElementById('hud-distance');
    if (hudDistance) {
      hudDistance.textContent = order.telemetry?.distance_km || (order.status === 'en_camino' ? '0.8 km' : '2.4 km');
    }

    const hudEta = document.getElementById('hud-eta');
    if (hudEta) {
      hudEta.textContent = order.eta_minutes || '15-20 min';
    }

    const hudStreet = document.getElementById('hud-street');
    if (hudStreet) {
      hudStreet.textContent = order.rider?.street || order.telemetry?.street || 'Av. Circunvalar';
    }

    const gpsIndicator = document.getElementById('track-gps-indicator');
    if (gpsIndicator) {
      gpsIndicator.textContent = order.telemetry?.gps_status ? 'GPS Neiva Satelital (3m)' : 'GPS Neiva Conectado';
    }

    // Información del Repartidor asignado
    if (order.rider) {
      const riderName = document.getElementById('track-rider-name');
      if (riderName) riderName.textContent = order.rider.name;

      const riderVeh = document.getElementById('track-rider-vehicle');
      if (riderVeh) riderVeh.textContent = `${order.rider.vehicle} (${order.rider.plate})`;

      const riderRating = document.getElementById('track-rider-rating');
      if (riderRating) riderRating.textContent = order.rider.rating;

      const riderStreet = document.getElementById('track-rider-street');
      if (riderStreet) riderStreet.textContent = order.rider.street;

      const riderImg = document.getElementById('track-rider-img');
      if (riderImg) riderImg.src = order.rider.photo;

      const waBtn = document.getElementById('track-rider-whatsapp');
      if (waBtn) {
        waBtn.href = `https://wa.me/${order.rider.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(`Hola ${order.rider.name}, te escribo por mi pedido ${order.order_code} en Neiva de Antojo Virtual.`)}`;
      }

      const callBtn = document.getElementById('track-rider-call');
      if (callBtn) {
        callBtn.href = `tel:${order.rider.phone.replace(/[^0-9]/g, '')}`;
      }
    }

    // Línea de Tiempo de Eventos en Tiempo Real (Timeline MySQL)
    const timelineContainer = document.getElementById('track-timeline-events');
    if (timelineContainer) {
      if (order.timeline && Array.isArray(order.timeline) && order.timeline.length > 0) {
        timelineContainer.innerHTML = order.timeline.map((evt, idx) => {
          const isLatest = (idx === order.timeline.length - 1);
          return `
            <div class="timeline-event-item">
              <div class="timeline-event-dot" style="${isLatest ? 'background: #fea619; box-shadow: 0 0 10px rgba(254,166,25,0.7);' : 'background: #10b981;'}"></div>
              <div class="timeline-event-time">${evt.created_at || 'Reciente'}</div>
              <div class="timeline-event-title">${evt.title || evt.event_title}</div>
              <div class="timeline-event-desc">${evt.description}</div>
            </div>
          `;
        }).join('');
      } else {
        timelineContainer.innerHTML = `
          <div class="timeline-event-item">
            <div class="timeline-event-dot" style="background: #10b981;"></div>
            <div class="timeline-event-time">${order.created_at || 'Hace un momento'}</div>
            <div class="timeline-event-title">Pedido Registrado en Antojo Virtual</div>
            <div class="timeline-event-desc">Orden ${order.order_code} recibida y despachada a cocina en Neiva.</div>
          </div>
          <div class="timeline-event-item">
            <div class="timeline-event-dot" style="background: #fea619; box-shadow: 0 0 10px rgba(254,166,25,0.7);"></div>
            <div class="timeline-event-time">En Curso</div>
            <div class="timeline-event-title">${order.status_title}</div>
            <div class="timeline-event-desc">${order.status_subtext}</div>
          </div>
        `;
      }
    }

    // Lista de platos e ítems
    const trackItemsContainer = document.getElementById('track-order-items');
    if (trackItemsContainer && order.items) {
      trackItemsContainer.innerHTML = order.items.map(it => `
        <div style="background: var(--neu-bg); box-shadow: var(--neu-extruded-sm); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-md); padding: 0.85rem 1.15rem; margin-bottom: 0.65rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">
          <div>
            <strong style="color: var(--color-on-surface);">${it.quantity}x ${it.item_name}</strong>
            ${it.doneness ? `<div style="font-size: 0.75rem; color: var(--color-on-surface-variant);">${it.doneness}</div>` : ''}
          </div>
          <span style="font-weight: 800; color: var(--color-on-surface);">${it.subtotal_formatted}</span>
        </div>
      `).join('');
    }

    const orderTotal = document.getElementById('track-order-total');
    if (orderTotal) orderTotal.textContent = order.payment?.total_formatted || '$0';

    const orderAddr = document.getElementById('track-order-address');
    if (orderAddr) orderAddr.textContent = `${order.customer?.address || ''} (${order.customer?.comuna || ''})`;

    // Desplegar mapa de Neiva en vivo
    this.renderNeivaMap(order);
  },

  // Despliegue del Mapa Leaflet Interactivo de Neiva con soporte HD Canvas
  renderNeivaMap(order) {
    const mapContainer = document.getElementById('neiva-interactive-map');
    const canvasContainer = document.getElementById('neiva-map-canvas');

    if (typeof L !== 'undefined' && mapContainer) {
      mapContainer.style.display = 'block';
      if (canvasContainer) canvasContainer.style.display = 'none';

      // Coordenadas reales de Neiva (Restaurante, Repartidor, Destino)
      const restLat = order.route?.restaurant?.lat || 2.9273;
      const restLng = order.route?.restaurant?.lng || -75.2819;
      const riderLat = order.route?.rider?.lat || 2.9300;
      const riderLng = order.route?.rider?.lng || -75.2850;
      const destLat = order.route?.destination?.lat || 2.9290;
      const destLng = order.route?.destination?.lng || -75.2860;

      const restCoords = [restLat, restLng];
      const riderCoords = [riderLat, riderLng];
      const destCoords = [destLat, destLng];

      // Inicializar Leaflet map si no está creado
      if (!this.state.leafletMap) {
        this.state.leafletMap = L.map('neiva-interactive-map', {
          zoomControl: false,
          attributionControl: false
        }).setView(riderCoords, 15);

        // Capa de mapa Carto Voyager estilo Neumórfico Suave
        this.state.leafletTileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
          maxZoom: 19,
          subdomains: 'abcd'
        }).addTo(this.state.leafletMap);
      }

      // 1. Marcador Restaurante en Neiva
      const restIcon = L.divIcon({
        className: 'leaflet-custom-marker',
        html: '<div class="rest-avatar-bubble" title="Restaurante">🍔</div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
      });

      if (!this.state.leafletMarkers.restaurant) {
        this.state.leafletMarkers.restaurant = L.marker(restCoords, { icon: restIcon })
          .addTo(this.state.leafletMap)
          .bindPopup(`<b>${order.restaurant?.name || 'La Estación Burger'}</b><br><small>${order.restaurant?.address || 'Cra 12 #10-34, Centro Neiva'}</small>`);
      } else {
        this.state.leafletMarkers.restaurant.setLatLng(restCoords);
      }

      // 2. Marcador Destino del Cliente en Neiva
      const destIcon = L.divIcon({
        className: 'leaflet-custom-marker',
        html: '<div class="dest-avatar-bubble" title="Tu Dirección">🏠</div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
      });

      if (!this.state.leafletMarkers.destination) {
        this.state.leafletMarkers.destination = L.marker(destCoords, { icon: destIcon })
          .addTo(this.state.leafletMap)
          .bindPopup(`<b>Tu Dirección de Entrega</b><br><small>${order.customer?.address || 'Neiva'} (${order.customer?.comuna || ''})</small>`);
      } else {
        this.state.leafletMarkers.destination.setLatLng(destCoords);
      }

      // 3. Marcador del Repartidor con Onda Radar Neumórfica
      const riderIcon = L.divIcon({
        className: 'leaflet-custom-marker',
        html: `
          <div class="rider-map-marker">
            <div class="rider-radar-ripple"></div>
            <div class="rider-avatar-bubble" title="Repartidor en Vivo">🛵</div>
          </div>
        `,
        iconSize: [54, 54],
        iconAnchor: [27, 27],
        popupAnchor: [0, -27]
      });

      const riderPopupContent = `
        <div style="font-size: 0.85rem; line-height: 1.4;">
          <strong style="color: #0f172a; font-size: 0.95rem;">${order.rider?.name || 'Carlos Mendoza'}</strong><br>
          <span style="color: #64748b;">${order.rider?.vehicle || 'Moto'} (${order.rider?.plate || 'HUI-84E'})</span><br>
          <span style="color: #dc2626; font-weight: 800;">● ${order.rider?.street || 'En ruta por Neiva'}</span><br>
          <span style="color: #15803d; font-weight: 700;">Velocidad: ${order.telemetry?.speed_kmh || 32} km/h</span>
        </div>
      `;

      if (!this.state.leafletMarkers.rider) {
        this.state.leafletMarkers.rider = L.marker(riderCoords, { icon: riderIcon })
          .addTo(this.state.leafletMap)
          .bindPopup(riderPopupContent);
      } else {
        this.state.leafletMarkers.rider.setLatLng(riderCoords);
        if (this.state.leafletMarkers.rider.getPopup()) {
          this.state.leafletMarkers.rider.getPopup().setContent(riderPopupContent);
        }
      }

      // 4. Polilínea de la Ruta Trazada en Neiva
      if (this.state.leafletPolyline) {
        this.state.leafletPolyline.remove();
      }
      this.state.leafletPolyline = L.polyline([restCoords, riderCoords, destCoords], {
        color: '#dc2626',
        weight: 4,
        opacity: 0.85,
        dashArray: '8, 8',
        lineCap: 'round'
      }).addTo(this.state.leafletMap);

      // Ajustar encuadre la primera vez
      if (!this._mapBoundsSet) {
        const bounds = L.latLngBounds([restCoords, riderCoords, destCoords]);
        this.state.leafletMap.fitBounds(bounds, { padding: [55, 55], maxZoom: 16 });
        this._mapBoundsSet = true;
      }

      // 5. Gestión de simulación de movimiento GPS fluido en Neiva
      this.manageRiderMovementSimulation(order, restCoords, destCoords);

      setTimeout(() => {
        if (this.state.leafletMap) this.state.leafletMap.invalidateSize();
      }, 150);

    } else if (canvasContainer) {
      // Fallback si no está disponible Leaflet o conexión externa
      if (mapContainer) mapContainer.style.display = 'none';
      canvasContainer.style.display = 'block';
      this.renderNeivaMapCanvas(order);
    }
  },

  // Fallback Canvas Neumórfico de Neiva
  renderNeivaMapCanvas(order) {
    const canvas = document.getElementById('neiva-map-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.parentElement.clientWidth || 600;
    const h = canvas.height = 360;

    // Fondo estilo mapa de arcilla suave neumórfica
    ctx.fillStyle = '#e5ebf3';
    ctx.fillRect(0, 0, w, h);

    // Río Magdalena (fluyendo por la izquierda)
    ctx.beginPath();
    ctx.moveTo(w * 0.1, 0);
    ctx.bezierCurveTo(w * 0.15, h * 0.4, w * 0.08, h * 0.7, w * 0.18, h);
    ctx.lineWidth = 22;
    ctx.strokeStyle = '#bae6fd';
    ctx.stroke();

    // Calles principales de Neiva (Av. Circunvalar, Cra 5, Calle 8, Calle 10)
    ctx.lineWidth = 8;
    ctx.strokeStyle = '#f4f8fd';

    // Av. Circunvalar
    ctx.beginPath();
    ctx.moveTo(w * 0.25, 0);
    ctx.bezierCurveTo(w * 0.28, h * 0.5, w * 0.25, h * 0.8, w * 0.32, h);
    ctx.stroke();

    // Cra 5
    ctx.beginPath();
    ctx.moveTo(w * 0.55, 0);
    ctx.lineTo(w * 0.55, h);
    ctx.stroke();

    // Calle 8 y Calle 10
    ctx.beginPath();
    ctx.moveTo(0, h * 0.45);
    ctx.lineTo(w, h * 0.45);
    ctx.moveTo(0, h * 0.65);
    ctx.lineTo(w, h * 0.65);
    ctx.stroke();

    // Etiquetas de lugares en Neiva
    ctx.font = 'bold 11px Plus Jakarta Sans, sans-serif';
    ctx.fillStyle = '#64748b';
    ctx.fillText('RÍO MAGDALENA', w * 0.04, h * 0.3);
    ctx.fillText('AV. CIRCUNVALAR', w * 0.28, h * 0.15);
    ctx.fillText('PARQUE SANTANDER', w * 0.58, h * 0.48);

    // Punto A: Restaurante (La Estación Burger)
    const restX = w * 0.35;
    const restY = h * 0.7;
    ctx.beginPath();
    ctx.arc(restX, restY, 12, 0, Math.PI * 2);
    ctx.fillStyle = '#fea619';
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 4;
    ctx.stroke();
    ctx.fillStyle = '#111c2d';
    ctx.fillText('🍔 La Estación', restX - 35, restY + 24);

    // Punto B: Destino Cliente
    const destX = w * 0.75;
    const destY = h * 0.35;
    ctx.beginPath();
    ctx.arc(destX, destY, 12, 0, Math.PI * 2);
    ctx.fillStyle = '#dc2626';
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 4;
    ctx.stroke();
    ctx.fillStyle = '#111c2d';
    ctx.fillText('🏠 Tu Dirección', destX - 35, destY - 16);

    // Ruta trazada entre restaurante y cliente
    ctx.beginPath();
    ctx.setLineDash([6, 6]);
    ctx.moveTo(restX, restY);
    ctx.lineTo(restX, destY);
    ctx.lineTo(destX, destY);
    ctx.strokeStyle = '#dc2626';
    ctx.lineWidth = 4;
    ctx.stroke();
    ctx.setLineDash([]);

    // Posición del rider según estado
    let riderX = restX;
    let riderY = restY;
    if (order.status === 'preparando') {
      riderX = restX + 20;
      riderY = restY;
    } else if (order.status === 'en_camino') {
      riderX = restX + (destX - restX) * 0.58;
      riderY = destY;
    } else if (order.status === 'entregado') {
      riderX = destX;
      riderY = destY;
    }

    // Dibujar pin neumórfico de moto del rider
    ctx.beginPath();
    ctx.arc(riderX, riderY, 16, 0, Math.PI * 2);
    ctx.fillStyle = '#192537';
    ctx.fill();
    ctx.strokeStyle = '#fea619';
    ctx.lineWidth = 4;
    ctx.stroke();

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 13px sans-serif';
    ctx.fillText('🛵', riderX - 9, riderY + 5);
  },

  // Controles del Mapa de Neiva
  centerMapOnRider() {
    const rider = this.state.currentOrder?.route?.rider;
    if (this.state.leafletMap && rider) {
      this.state.leafletMap.flyTo([rider.lat, rider.lng], 16, { animate: true, duration: 1.2 });
      if (this.state.leafletMarkers.rider) {
        this.state.leafletMarkers.rider.openPopup();
      }
      this.showToast('Centrado en el repartidor en Neiva');
    } else {
      this.showToast('Ubicación del repartidor activa');
    }
  },

  centerMapOnRoute() {
    const route = this.state.currentOrder?.route;
    if (this.state.leafletMap && route) {
      const bounds = L.latLngBounds([
        [route.restaurant.lat, route.restaurant.lng],
        [route.rider.lat, route.rider.lng],
        [route.destination.lat, route.destination.lng]
      ]);
      this.state.leafletMap.fitBounds(bounds, { padding: [50, 50], animate: true });
      this.showToast('Mostrando ruta completa en Neiva');
    }
  },

  toggleMapTilesLayer() {
    if (!this.state.leafletMap) return;

    const styles = {
      voyager: {
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        next: 'osm',
        label: 'Callejero OpenStreetMap'
      },
      osm: {
        url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        next: 'dark',
        label: 'Modo Noche Satelital'
      },
      dark: {
        url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        next: 'voyager',
        label: 'Modo Neumórfico Suave'
      }
    };

    const cur = this.state.currentTileStyle || 'voyager';
    const config = styles[cur] || styles.voyager;

    if (this.state.leafletTileLayer) {
      this.state.leafletTileLayer.remove();
    }

    this.state.leafletTileLayer = L.tileLayer(config.url, {
      maxZoom: 19,
      subdomains: 'abcd'
    }).addTo(this.state.leafletMap);

    this.state.currentTileStyle = config.next;
    this.showToast(`Capa de mapa: ${config.label}`);
  },

  // Buscador de Pedido por código o teléfono
  searchOrderTracking() {
    const input = document.getElementById('track-search-code-input');
    const code = (input ? input.value : '').trim();
    if (!code) {
      this.showToast('Por favor escribe tu código de pedido o número celular.');
      return;
    }
    this.showToast(`Buscando orden ${code}...`);
    this.fetchOrderForTracking(code, false);
  },

  // Ver Factura y Comprobante Oficial DIAN para la orden en pantalla
  viewReceiptForCurrentOrder() {
    const order = this.state.currentOrder;
    if (!order) {
      this.showToast('No hay una orden activa seleccionada.');
      return;
    }

    const receiptModal = document.getElementById('payment-receipt-modal');
    if (!receiptModal) return;

    const p = order.payment || {};
    const inv = order.invoice || {};
    const customer = order.customer || {};

    const invNum = document.getElementById('receipt-inv-number');
    if (invNum) invNum.textContent = p.invoice_number || inv.invoice_number || 'FE-ANTOJO-2026-00001';

    const refCode = document.getElementById('receipt-ref-code');
    if (refCode) refCode.textContent = p.transaction_reference || p.reference || ('PAY-NV-' + (order.order_code ? order.order_code.replace(/[^A-Za-z0-9]/g, '') : '8492'));

    const authCode = document.getElementById('receipt-auth-code');
    if (authCode) authCode.textContent = p.authorization_code || 'AUT-948123';

    const methodLabel = document.getElementById('receipt-method-label');
    if (methodLabel) methodLabel.textContent = p.bank_name || p.method_title || (p.method === 'pse' ? 'PSE - Bancolombia' : 'Tarjeta Débito/Crédito');

    const dt = document.getElementById('receipt-datetime');
    if (dt) dt.textContent = order.created_at || new Date().toLocaleString('es-CO');

    const custName = document.getElementById('receipt-customer-name');
    if (custName) custName.textContent = customer.name || 'Mateo Rodríguez';

    const custDoc = document.getElementById('receipt-customer-doc');
    if (custDoc) custDoc.textContent = customer.phone ? `Tel / CC: ${customer.phone}` : 'CC 1075304128';

    const subtotalEl = document.getElementById('receipt-subtotal');
    if (subtotalEl) subtotalEl.textContent = p.subtotal_formatted || (p.subtotal ? '$' + Number(p.subtotal).toLocaleString('es-CO') : '$35.900');

    const deliveryEl = document.getElementById('receipt-delivery');
    if (deliveryEl) deliveryEl.textContent = p.delivery_fee_formatted || '$3.500';

    const tipEl = document.getElementById('receipt-tip');
    if (tipEl) tipEl.textContent = p.tip_formatted || '$1.000';

    const totalEl = document.getElementById('receipt-total');
    if (totalEl) totalEl.textContent = p.total_formatted || '$40.400';

    const totalNum = p.total || 40400;
    const taxApprox = Math.round(totalNum * 0.08 / 1.08);
    const taxEl = document.getElementById('receipt-tax');
    if (taxEl) taxEl.textContent = '$' + taxApprox.toLocaleString('es-CO');

    const discRow = document.getElementById('receipt-discount-row');
    const discEl = document.getElementById('receipt-discount');
    if (p.discount && p.discount > 0) {
      if (discRow) discRow.style.display = 'flex';
      if (discEl) discEl.textContent = p.discount_formatted || `-$${Number(p.discount).toLocaleString('es-CO')}`;
    } else {
      if (discRow) discRow.style.display = 'none';
    }

    const cufeEl = document.getElementById('receipt-cufe-code');
    const cufeStr = inv.cufe_code || p.cufe_code || '8f89c4501b87a9d04ef124803923a1e948c27940182390abcef182903810293849102839401829384910293849102938';
    if (cufeEl) {
      cufeEl.textContent = cufeStr;
    }

    // Renderizar código QR digital oficial para la DIAN
    this.renderReceiptQRCode(cufeStr);

    receiptModal.classList.add('open');
  },

  // Simulación Fluida de Movimiento GPS del Repartidor en Calles de Neiva
  manageRiderMovementSimulation(order, restCoords, destCoords) {
    if (order.status !== 'en_camino') {
      if (this.state.riderAnimTimer) {
        clearInterval(this.state.riderAnimTimer);
        this.state.riderAnimTimer = null;
      }
      return;
    }

    if (this.state.riderAnimTimer) return; // Ya está activo

    // Waypoints reales trazando calles de Neiva
    const waypoints = [
      restCoords,
      [2.9275, -75.2830], // Cra 12 con Calle 10
      [2.9282, -75.2858], // Calle 10 hacia Av. Circunvalar
      [2.9315, -75.2885], // Av. Circunvalar con Calle 14
      [2.9338, -75.2872], // Av. Circunvalar con Calle 17
      destCoords          // Destino final
    ];

    const streets = [
      'Cra 12 con Calle 10 (Saliendo del restaurante)',
      'Calle 10 hacia la Av. Circunvalar',
      'Av. Circunvalar hacia el Norte (Ruta despejada)',
      'Av. Circunvalar con Calle 14 (Acelerando)',
      'Aproximándose a tu Comuna en Neiva',
      'Llegando a tu dirección'
    ];

    this.state.riderAnimTimer = setInterval(() => {
      if (this.state.currentOrder?.status !== 'en_camino') {
        clearInterval(this.state.riderAnimTimer);
        this.state.riderAnimTimer = null;
        return;
      }

      this.state.riderAnimProgress += 0.035;
      if (this.state.riderAnimProgress >= 0.95) {
        this.state.riderAnimProgress = 0.95;
      }

      const p = this.state.riderAnimProgress;
      const totalSegments = waypoints.length - 1;
      const segIndex = Math.min(totalSegments - 1, Math.floor(p * totalSegments));
      const segProgress = (p * totalSegments) - segIndex;

      const pA = waypoints[segIndex];
      const pB = waypoints[segIndex + 1];

      const curLat = pA[0] + (pB[0] - pA[0]) * segProgress;
      const curLng = pA[1] + (pB[1] - pA[1]) * segProgress;

      // Actualizar posición del marcador de la moto
      if (this.state.leafletMarkers.rider) {
        this.state.leafletMarkers.rider.setLatLng([curLat, curLng]);
      }

      // Fluctuación natural de velocidad y distancia
      const speed = Math.round(29 + Math.sin(Date.now() / 600) * 6);
      const remainingKm = Math.max(0.1, (1.8 * (1 - p))).toFixed(1);
      const remainingEta = Math.max(2, Math.round(10 * (1 - p)));
      const streetName = streets[Math.min(streets.length - 1, segIndex)];

      // Actualizar Telemetría HUD
      const speedEl = document.getElementById('hud-speed');
      if (speedEl) speedEl.textContent = `${speed} km/h`;

      const distEl = document.getElementById('hud-distance');
      if (distEl) distEl.textContent = `${remainingKm} km`;

      const etaEl = document.getElementById('hud-eta');
      if (etaEl) etaEl.textContent = `${remainingEta} min`;

      const streetEl = document.getElementById('hud-street');
      if (streetEl) streetEl.textContent = streetName;

      const trackStreet = document.getElementById('track-rider-street');
      if (trackStreet) {
        trackStreet.innerHTML = `<span class="material-symbols-outlined" style="font-size: 18px;">navigation</span> <span>${streetName}</span>`;
      }

      // Actualizar popup dinámico
      if (this.state.leafletMarkers.rider?.getPopup()) {
        this.state.leafletMarkers.rider.getPopup().setContent(`
          <div style="font-size: 0.85rem; line-height: 1.4;">
            <strong style="color: #0f172a; font-size: 0.95rem;">Carlos Mendoza (En Moto)</strong><br>
            <span style="color: #64748b;">Bajaj Pulsar 200 NS (HUI-84E)</span><br>
            <span style="color: #dc2626; font-weight: 800;">● ${streetName}</span><br>
            <span style="color: #15803d; font-weight: 700;">Velocidad GPS: ${speed} km/h</span>
          </div>
        `);
      }
    }, 1200);
  },

  // Inicialización de la interfaz de Audio
  initAudioUI() {
    const icon = document.getElementById('audio-toggle-icon');
    const text = document.getElementById('audio-toggle-text');
    if (icon) {
      icon.textContent = SoundFX.enabled ? 'volume_up' : 'volume_off';
      icon.style.color = SoundFX.enabled ? '#16a34a' : '#64748b';
    }
    if (text) {
      text.textContent = SoundFX.enabled ? 'Sonido' : 'Silencio';
    }
  },

  // Alternar efectos de sonido
  toggleSoundFX() {
    SoundFX.enabled = !SoundFX.enabled;
    localStorage.setItem('antojo_sound_enabled', SoundFX.enabled ? 'true' : 'false');
    this.initAudioUI();
    this.showToast(SoundFX.enabled ? '🔔 Efectos de sonido activados' : '🔕 Sonido silenciado');
    if (SoundFX.enabled) {
      SoundFX.playTone(880, 0.15, 'triangle', 0.2);
    }
  },

  // Instalación de la PWA en celulares
  promptInstallPWA() {
    if (this.state.deferredPwaPrompt) {
      this.state.deferredPwaPrompt.prompt();
      this.state.deferredPwaPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          this.showToast('¡Antojo Virtual se ha instalado en tu dispositivo!');
        }
        this.state.deferredPwaPrompt = null;
        const btn = document.getElementById('btn-install-pwa');
        if (btn) btn.style.display = 'none';
      });
    } else {
      this.showToast('Para instalar: usa el menú de tu navegador y selecciona "Agregar a la pantalla principal"');
    }
  },

  // Renderizar Código QR Oficial DIAN en el Voucher
  renderReceiptQRCode(cufeText) {
    const canvas = document.getElementById('receipt-qr-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const size = canvas.width || 90;
    ctx.clearRect(0, 0, size, size);

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);

    const gridSize = 25;
    const cellSize = size / gridSize;
    const matrix = Array(gridSize).fill(0).map(() => Array(gridSize).fill(0));

    // Finder patterns 7x7
    const drawFinder = (startX, startY) => {
      for (let r = 0; r < 7; r++) {
        for (let c = 0; c < 7; c++) {
          const isOuter = (r === 0 || r === 6 || c === 0 || c === 6);
          const isInner = (r >= 2 && r <= 4 && c >= 2 && c <= 4);
          matrix[startY + r][startX + c] = (isOuter || isInner) ? 1 : 0;
        }
      }
    };

    drawFinder(0, 0);
    drawFinder(gridSize - 7, 0);
    drawFinder(0, gridSize - 7);

    // Timing lines
    for (let i = 8; i < gridSize - 8; i++) {
      matrix[6][i] = (i % 2 === 0) ? 1 : 0;
      matrix[i][6] = (i % 2 === 0) ? 1 : 0;
    }

    // Alignment pattern
    for (let r = -2; r <= 2; r++) {
      for (let c = -2; c <= 2; c++) {
        const isOuter = (Math.abs(r) === 2 || Math.abs(c) === 2);
        const isCore = (r === 0 && c === 0);
        matrix[18 + r][18 + c] = (isOuter || isCore) ? 1 : 0;
      }
    }

    // Seed data bits based on CUFE
    let seed = 0;
    for (let i = 0; i < (cufeText || 'DIAN').length; i++) {
      seed = ((seed << 5) - seed) + cufeText.charCodeAt(i);
      seed |= 0;
    }

    const rng = () => {
      seed = (seed * 9301 + 49297) % 233280;
      return seed / 233280;
    };

    for (let r = 0; r < gridSize; r++) {
      for (let c = 0; c < gridSize; c++) {
        if ((r < 8 && c < 8) || (r < 8 && c >= gridSize - 8) || (r >= gridSize - 8 && c < 8)) continue;
        if (r === 6 || c === 6) continue;
        if (r >= 16 && r <= 20 && c >= 16 && c <= 20) continue;

        matrix[r][c] = rng() > 0.48 ? 1 : 0;
      }
    }

    ctx.fillStyle = '#0f172a';
    for (let r = 0; r < gridSize; r++) {
      for (let c = 0; c < gridSize; c++) {
        if (matrix[r][c] === 1) {
          ctx.fillRect(c * cellSize, r * cellSize, cellSize, cellSize);
        }
      }
    }
  },

  // Módulo de Calificación y Reseñas Post-Entrega
  openReviewModal() {
    const modal = document.getElementById('review-modal');
    if (modal) {
      modal.classList.add('open');
      this.setReviewRating('food', 5);
      this.setReviewRating('rider', 5);
    }
  },

  closeReviewModal() {
    const modal = document.getElementById('review-modal');
    if (modal) modal.classList.remove('open');
  },

  setReviewRating(target, score) {
    this.state.reviewSelection[target] = score;
    const container = document.getElementById(`${target}-stars`);
    if (container) {
      const stars = container.querySelectorAll('.star-btn');
      stars.forEach(s => {
        const val = parseInt(s.dataset.val || '1', 10);
        s.classList.toggle('active', val <= score);
      });
    }

    const label = document.getElementById(`${target}-rating-label`);
    if (label) {
      const labels = {
        5: target === 'food' ? '5.0 - ¡Espectacular sabor huilense!' : '5.0 - ¡Entrega rápida y comida bien caliente!',
        4: target === 'food' ? '4.0 - ¡Muy rico y buena porción!' : '4.0 - ¡Buen servicio del repartidor!',
        3: '3.0 - Aceptable / Dentro de lo esperado',
        2: '2.0 - Regular / Podría mejorar',
        1: '1.0 - Experiencia mejorable'
      };
      label.textContent = labels[score] || `${score}.0`;
    }
  },

  async submitOrderReview() {
    const order = this.state.currentOrder;
    const orderCode = order?.order_code || localStorage.getItem('antojo_active_order_code') || '#NV-8492';
    const customerName = order?.customer?.name || 'Mateo Rodríguez';
    const commentInput = document.getElementById('review-comment-input');
    const comment = commentInput ? commentInput.value.trim() : '';

    // Tags seleccionados
    const activeTags = [];
    document.querySelectorAll('#review-tags-container .cat-chip.active').forEach(chip => {
      activeTags.push(chip.textContent.trim());
    });

    try {
      const res = await fetch('api/reviews.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          order_code: orderCode,
          customer_name: customerName,
          rating: this.state.reviewSelection.food || 5.0,
          rider_rating: this.state.reviewSelection.rider || 5.0,
          comment: comment,
          tags: activeTags
        })
      });

      const data = await res.json();
      if (data.success) {
        SoundFX.playOrderPlaced();
        this.showToast('¡Gracias por tu opinión! Tu calificación ha sido registrada.');
        this.closeReviewModal();
      } else {
        this.showToast(data.error || 'Error al guardar calificación.');
      }
    } catch(err) {
      this.closeReviewModal();
      this.showToast('¡Gracias por calificar tu pedido en Neiva!');
    }
  },

  // Simulación rápida de avance de orden (para demostración al cliente)
  async autoAdvanceDemoOrder() {
    const code = this.state.currentOrder?.order_code || localStorage.getItem('antojo_active_order_code') || '#NV-8492';
    try {
      const fetcher = (typeof Auth !== 'undefined') ? Auth.fetch.bind(Auth) : fetch;
      const res = await fetcher('api/admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'auto_advance', order_code: code })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(`Estado avanzado: ${data.new_status_title}`);
        this.fetchOrderForTracking(code, false);
      }
    } catch (e) {
      this.showToast('Error avanzando estado');
    }
  },

  // Filtros de Tipo de Sitio (Salado vs Dulce)
  filterBySiteType(type) {
    this.state.selectedSiteType = type;
    document.querySelectorAll('#btn-mood-all, #btn-mood-salado, #btn-mood-dulce').forEach(b => b.classList.remove('active'));
    if (type === 'all') document.getElementById('btn-mood-all')?.classList.add('active');
    if (type === 'comidas_rapidas') document.getElementById('btn-mood-salado')?.classList.add('active');
    if (type === 'dulces') document.getElementById('btn-mood-dulce')?.classList.add('active');

    if (type === 'dulces') {
      this.state.selectedCategory = 'dulces';
      document.querySelectorAll('#subcategories-slider .cat-chip').forEach(c => {
        const cat = c.dataset.cat;
        c.style.display = (c.classList.contains('sweet-cat') || cat === 'all') ? 'inline-flex' : 'none';
        c.classList.toggle('active', cat === 'all');
      });
    } else if (type === 'comidas_rapidas') {
      this.state.selectedCategory = 'comidas_rapidas';
      document.querySelectorAll('#subcategories-slider .cat-chip').forEach(c => {
        const cat = c.dataset.cat;
        c.style.display = (!c.classList.contains('sweet-cat') || cat === 'all') ? 'inline-flex' : 'none';
        c.classList.toggle('active', cat === 'all');
      });
    } else {
      this.state.selectedCategory = 'all';
      document.querySelectorAll('#subcategories-slider .cat-chip').forEach(c => {
        c.style.display = 'inline-flex';
        c.classList.toggle('active', cat === 'all');
      });
    }

    this.fetchRestaurants();
    this.fetchDishes();
  },

  filterByCategory(catSlug) {
    this.state.selectedCategory = catSlug;
    document.querySelectorAll('#subcategories-slider .cat-chip').forEach(c => {
      c.classList.toggle('active', c.dataset.cat === catSlug);
    });
    this.fetchRestaurants();
    this.fetchDishes();
  },

  filterByRestaurant(restName) {
    const input = document.getElementById('search-input');
    if (input) {
      input.value = restName;
      this.state.searchQuery = restName;
      this.fetchDishes();
      window.scrollTo({ top: 600, behavior: 'smooth' });
    }
  },

  // Toast flotante Neumórfico
  showToast(msg) {
    let toast = document.getElementById('app-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'app-toast';
      toast.className = 'toast-notice';
      document.body.appendChild(toast);
    }
    toast.innerHTML = `
      <span class="material-symbols-outlined" style="color: #fea619;">info</span>
      <span style="font-weight: 700; font-size: 0.9rem; color: var(--color-on-surface);">${msg}</span>
    `;
    toast.style.display = 'flex';
    clearTimeout(this._toastTimer);
    this._toastTimer = setTimeout(() => {
      toast.style.display = 'none';
    }, 3500);
  },

  // Consulta dinámica de bancos colombianos para la lista PSE
  async fetchBanksForPSE() {
    try {
      const res = await fetch('api/payments.php?banks=1');
      const data = await res.json();
      if (data.success && Array.isArray(data.banks)) {
        const select = document.getElementById('pse-bank-select');
        if (!select) return;

        const traditional = data.banks.filter(b => b.type === 'bank');
        const neobanks = data.banks.filter(b => b.type !== 'bank');

        select.innerHTML = `
          <optgroup label="Bancos Tradicionales">
            ${traditional.map(b => `<option value="${b.id}" ${b.code === 'BANCOLOMBIA' ? 'selected' : ''}>${b.name}</option>`).join('')}
          </optgroup>
          <optgroup label="Neobancos y Billeteras Digitales">
            ${neobanks.map(b => `<option value="${b.id}">${b.name}</option>`).join('')}
          </optgroup>
        `;
      }
    } catch (e) {
      console.warn('Usando lista de bancos colombianos precargada en el HTML.');
    }
  },

  // Inicialización de interacciones de la pasarela y tarjeta 3D
  setupPaymentGatewayInteractions() {
    // 1. Selector de métodos de pago
    document.querySelectorAll('.pay-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.pay-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        const method = card.dataset.method;
        document.querySelectorAll('.gateway-panel').forEach(p => p.style.display = 'none');

        const targetPanel = document.getElementById(`gateway-panel-${method}`);
        if (targetPanel) {
          targetPanel.style.display = 'block';
        }

        this.updateInstallmentsCalculation();
      });
    });

    // 2. Formateo y Brand Detection en Vivo para Tarjetas
    const cardNumInput = document.getElementById('card-input-number');
    const cardNumDisplay = document.getElementById('card-number-display');
    const cardBrandDisplay = document.getElementById('card-brand-display');
    const cardBrandIcon = document.getElementById('card-input-brand-icon');

    if (cardNumInput) {
      cardNumInput.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 16);
        let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
        e.target.value = formatted;

        // Visual preview
        if (cardNumDisplay) {
          cardNumDisplay.textContent = formatted.length > 0 ? formatted : '•••• •••• •••• 4242';
        }

        // Brand detection
        const brand = this.detectCardBrand(val);
        if (cardBrandDisplay) {
          cardBrandDisplay.className = `neu-card-brand-badge ${brand}`;
          cardBrandDisplay.innerHTML = `<span>${brand.toUpperCase()}</span>`;
        }
        if (cardBrandIcon) {
          cardBrandIcon.textContent = (brand === 'visa' || brand === 'mastercard') ? 'credit_card' : 'payments';
        }
      });
    }

    // 3. Nombre del Titular
    const cardNameInput = document.getElementById('card-input-name');
    const cardNameDisplay = document.getElementById('card-holder-display');
    if (cardNameInput && cardNameDisplay) {
      cardNameInput.addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        cardNameDisplay.textContent = val || 'MATEO RODRÍGUEZ';
      });
    }

    // 4. Fecha de Expiración
    const cardExpInput = document.getElementById('card-input-exp');
    const cardExpDisplay = document.getElementById('card-exp-display');
    if (cardExpInput && cardExpDisplay) {
      cardExpInput.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 4);
        if (val.length >= 3) {
          val = val.substring(0, 2) + '/' + val.substring(2, 4);
        }
        e.target.value = val;
        cardExpDisplay.textContent = val || '12/28';
      });
    }

    // 5. Selector de Cuotas
    const installmentsSelect = document.getElementById('card-input-installments');
    if (installmentsSelect) {
      installmentsSelect.addEventListener('change', () => {
        this.updateInstallmentsCalculation();
      });
    }

    // 6. Selector de Cambio en Efectivo
    const cashSelect = document.getElementById('cash-change-select');
    if (cashSelect) {
      cashSelect.addEventListener('change', () => {
        this.updateCashChangeCalculation();
      });
    }

    this.updateInstallmentsCalculation();
    this.updateCashChangeCalculation();
  },

  // Cálculo del valor de cada cuota bancaria
  updateInstallmentsCalculation() {
    const installmentsEl = document.getElementById('card-input-installments');
    const calcEl = document.getElementById('card-installment-amount');
    if (!installmentsEl || !calcEl) return;

    const n = parseInt(installmentsEl.value || '1', 10);
    const totalEl = document.getElementById('summary-total');
    let totalNum = 0;
    if (totalEl) {
      totalNum = parseInt(totalEl.textContent.replace(/\D/g, '') || '0', 10);
    }

    if (totalNum <= 0) totalNum = 40400;

    const perInstallment = Math.round(totalNum / n);
    calcEl.textContent = `$${perInstallment.toLocaleString('es-CO')} COP / mes`;
  },

  // Cálculo de cambio en efectivo
  updateCashChangeCalculation() {
    const cashSelect = document.getElementById('cash-change-select');
    const calcEl = document.getElementById('cash-change-calc');
    if (!cashSelect || !calcEl) return;

    const val = cashSelect.value;
    const totalEl = document.getElementById('summary-total');
    let totalNum = 0;
    if (totalEl) {
      totalNum = parseInt(totalEl.textContent.replace(/\D/g, '') || '0', 10);
    }
    if (totalNum <= 0) totalNum = 40400;

    if (val === 'exacto') {
      calcEl.textContent = 'Pago exacto acordado. El repartidor no llevará cambio adicional.';
    } else {
      const bill = parseInt(val, 10);
      const change = Math.max(0, bill - totalNum);
      calcEl.textContent = `Pagas con billete de $${bill.toLocaleString('es-CO')}. El repartidor te entregará $${change.toLocaleString('es-CO')} COP de cambio.`;
    }
  },

  // Configuración de listeners generales
  setupEventListeners() {
    const searchInput = document.getElementById('search-input');
    let debounceTimer;
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          this.state.searchQuery = e.target.value.trim();
          this.fetchRestaurants();
          this.fetchDishes();
        }, 250);
      });
    }

    const comunaSelect = document.getElementById('header-comuna-select');
    if (comunaSelect) {
      comunaSelect.addEventListener('change', (e) => {
        this.state.selectedComuna = e.target.value;
        this.showToast(`Zona cambiada a: ${e.target.value}`);
      });
    }

    const copyBtn = document.getElementById('btn-hero-copy-coupon');
    if (copyBtn) {
      copyBtn.addEventListener('click', () => {
        navigator.clipboard?.writeText('ANTOJONEIVA5K');
        this.state.appliedCoupon = {
          code: 'ANTOJONEIVA5K',
          discount_amount: 5000
        };
        this.showToast('¡Cupón ANTOJONEIVA5K copiado y aplicado (-$5.000 COP)!');
      });
    }

    // Inicializar pasarela de pagos
    this.setupPaymentGatewayInteractions();
    this.fetchBanksForPSE();
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
