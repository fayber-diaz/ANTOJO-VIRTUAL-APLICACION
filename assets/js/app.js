/**
 * =========================================================
 * ANTOJO VIRTUAL - JavaScript Principal (Vanilla ES6)
 * Conectado a PHP REST API y MySQL en XAMPP
 * Neiva, Huila • Diseño Neumorfismo (Soft UI)
 * =========================================================
 */

const App = {
  // Estado global de la aplicación
  state: {
    currentScreen: 'explorar', // 'explorar' | 'checkout' | 'rastreo'
    restaurants: [],
    dishes: [],
    categories: [],
    customizerOptions: { terminos: [], panes: [], toppings: [], salsas: [] },
    selectedCategory: 'all',
    selectedComuna: 'Comuna 4 - Centro',
    searchQuery: '',
    cart: [],
    appliedCoupon: null,
    currentOrder: null, // Pedido activo para rastreo
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
      const res = await fetch(`api/restaurants.php?category=${encodeURIComponent(this.state.selectedCategory)}&search=${encodeURIComponent(this.state.searchQuery)}`);
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
      const res = await fetch(`api/menu.php?category=${encodeURIComponent(this.state.selectedCategory)}&search=${encodeURIComponent(this.state.searchQuery)}`);
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
      const res = await fetch('api/menu.php?options=1');
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
            <div class="dish-badge" style="background: rgba(220, 38, 38, 0.9); color: white;">
              <span class="material-symbols-outlined" style="font-size: 15px; color: #fea619;">local_fire_department</span>
              Especialidad al Carbón
            </div>
          ` : ''}
        </div>
        <div class="dish-content">
          <span class="dish-rest-name">${d.restaurant_name}</span>
          <h3 class="dish-title">${d.name}</h3>
          <p class="dish-desc">${d.description}</p>
          <div class="dish-tags">
            ${(d.tags_array || []).map(t => `<span class="dish-tag-item">${t}</span>`).join('')}
          </div>
          <div class="dish-footer">
            <span class="dish-price">${d.price_formatted}</span>
            <button class="btn-customize" onclick="App.openCustomizer(${d.id})">
              <span class="material-symbols-outlined" style="font-size: 18px;">tune</span>
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

    // Valores por defecto
    const defaultTermino = this.state.customizerOptions.terminos.find(t => t.is_default)?.name || 'Tres Cuartos - 3/4 (Punto ideal de la casa)';
    const defaultBread = this.state.customizerOptions.panes.find(p => p.is_default)?.name || 'Pan Brioche Dorado Tradicional';

    this.state.customizerSelection = {
      termino: defaultTermino,
      bread: defaultBread,
      toppings: {},
      sauces: ['Tártara Especial de la Abuela', 'BBQ Artesanal con Guayaba Huilense'],
      notes: ''
    };

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

    // Renderizar Términos
    const terminosContainer = document.getElementById('modal-terminos-list');
    terminosContainer.innerHTML = this.state.customizerOptions.terminos.map(t => `
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
    `).join('');

    // Renderizar Panes
    const panesContainer = document.getElementById('modal-panes-list');
    panesContainer.innerHTML = this.state.customizerOptions.panes.map(p => `
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
    `).join('');

    // Renderizar Toppings
    const toppingsContainer = document.getElementById('modal-toppings-list');
    toppingsContainer.innerHTML = this.state.customizerOptions.toppings.map(top => {
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
    }).join('');

    // Renderizar Salsas
    const salsasContainer = document.getElementById('modal-salsas-list');
    salsasContainer.innerHTML = this.state.customizerOptions.salsas.map(s => {
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
    }).join('');

    this.calculateModalTotal();
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

    const selectedBreadObj = this.state.customizerOptions.panes.find(p => p.name === this.state.customizerSelection.bread);
    if (selectedBreadObj) {
      total += selectedBreadObj.price;
    }

    for (const key in this.state.customizerSelection.toppings) {
      const top = this.state.customizerSelection.toppings[key];
      total += (top.price * top.count);
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

    const cartItem = {
      id: 'item-' + Date.now(),
      name: dish.name,
      restaurant: dish.restaurant_name,
      basePrice: dish.price,
      quantity: 1,
      doneness: this.state.customizerSelection.termino,
      bread: this.state.customizerSelection.bread,
      toppings: toppingsArray,
      sauces: [...this.state.customizerSelection.sauces],
      notes: notes
    };

    this.state.cart.unshift(cartItem);
    this.saveCartToStorage();
    this.renderCartBadge();
    this.closeCustomizer();
    this.showToast(`¡${dish.name} agregada a tu orden!`);
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

            ${item.doneness ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant); margin-top: 0.25rem;">🥩 <strong>Término:</strong> ${item.doneness}</div>` : ''}
            ${item.bread ? `<div style="font-size: 0.8rem; color: var(--color-on-surface-variant);">🍞 <strong>Pan:</strong> ${item.bread}</div>` : ''}

            ${item.toppings && item.toppings.length > 0 ? `
              <div style="margin-top: 0.35rem; font-size: 0.8rem; color: var(--color-on-surface);">
                <strong>Toppings extras:</strong>
                ${item.toppings.map(t => `<span class="dish-tag-item" style="margin-right:4px;">+ ${t.name} ($${(t.price).toLocaleString('es-CO')})</span>`).join('')}
              </div>
            ` : ''}

            ${item.sauces && item.sauces.length > 0 ? `
              <div style="font-size: 0.8rem; color: #1a8340; margin-top: 0.25rem;">
                <strong>Salsas:</strong> ${item.sauces.join(', ')}
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

  // Confirmar Pedido y Enviar a MySQL
  async submitOrder() {
    if (this.state.cart.length === 0) {
      this.showToast('Agrega productos al carrito antes de confirmar.');
      return;
    }

    const customerName = document.getElementById('customer-name')?.value || 'Mateo Rodríguez';
    const customerPhone = document.getElementById('customer-phone')?.value || '+57 314 289 9012';
    const deliveryAddress = document.getElementById('delivery-address')?.value || 'Cra. 5 #18-42, Centro';
    const comuna = document.getElementById('delivery-comuna')?.value || this.state.selectedComuna;
    const instructions = document.getElementById('delivery-instructions')?.value || '';
    const paymentMethod = document.querySelector('.pay-card.active')?.dataset.method || 'nequi';
    const cashChange = document.getElementById('cash-change-select')?.value || 'exacto';

    const payload = {
      customer_name: customerName,
      customer_phone: customerPhone,
      delivery_address: deliveryAddress,
      comuna: comuna,
      delivery_timing: 'immediate',
      instructions: instructions,
      payment_method: paymentMethod,
      cash_change_for: cashChange,
      coupon_code: this.state.appliedCoupon?.code || '',
      items: this.state.cart
    };

    const confirmBtn = document.getElementById('btn-confirm-order-action');
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Guardando en MySQL...';
    }

    try {
      const res = await fetch('api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        this.showToast('¡Pedido guardado en MySQL con éxito! Conectando con repartidor...');
        
        localStorage.setItem('antojo_active_order_code', data.order_code);

        this.state.cart = [];
        this.saveCartToStorage();
        this.renderCartBadge();

        setTimeout(() => {
          this.fetchOrderForTracking(data.order_code, true);
        }, 800);
      } else {
        this.showToast(data.message || 'Error al procesar el pedido');
        if (confirmBtn) {
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Confirmar Pedido';
        }
      }
    } catch (err) {
      this.showToast('Error de conexión con el backend PHP');
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Confirmar Pedido';
      }
    }
  },

  // Rastreo en Vivo (Live Tracking)
  async fetchOrderForTracking(orderCode, navigateToScreen = false) {
    try {
      const res = await fetch(`api/tracking.php?code=${encodeURIComponent(orderCode)}`);
      const data = await res.json();

      if (data.success) {
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

    document.getElementById('track-order-code').textContent = order.order_code;
    document.getElementById('track-status-title').textContent = order.status_title;
    document.getElementById('track-status-sub').textContent = order.status_subtext;
    document.getElementById('track-eta').textContent = order.eta_minutes;
    document.getElementById('track-progress-bar').style.width = order.progress_percent + '%';

    const stepIdx = order.step_index;
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

    if (order.rider) {
      document.getElementById('track-rider-name').textContent = order.rider.name;
      document.getElementById('track-rider-vehicle').textContent = `${order.rider.vehicle} (${order.rider.plate})`;
      document.getElementById('track-rider-rating').textContent = order.rider.rating;
      document.getElementById('track-rider-street').textContent = order.rider.street;
      document.getElementById('track-rider-img').src = order.rider.photo;
      document.getElementById('track-rider-whatsapp').href = `https://wa.me/${order.rider.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(`Hola ${order.rider.name}, te escribo por mi pedido ${order.order_code} de Antojo Virtual.`)}`;
      document.getElementById('track-rider-call').href = `tel:${order.rider.phone.replace(/[^0-9]/g, '')}`;
    }

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

    document.getElementById('track-order-total').textContent = order.payment?.total_formatted || '$0';
    document.getElementById('track-order-address').textContent = `${order.customer?.address} (${order.customer?.comuna})`;

    this.renderNeivaMap(order);
  },

  // Simulación del mapa de Neiva Neumórfico con Canvas
  renderNeivaMap(order) {
    const canvas = document.getElementById('neiva-map-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.parentElement.clientWidth || 600;
    const h = canvas.height = 330;

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

  // Simulación rápida de avance de orden (para demostración al cliente)
  async autoAdvanceDemoOrder() {
    const code = this.state.currentOrder?.order_code || localStorage.getItem('antojo_active_order_code') || '#NV-8492';
    try {
      const res = await fetch('api/admin.php', {
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

  // Filtros
  filterByCategory(catSlug) {
    this.state.selectedCategory = catSlug;
    document.querySelectorAll('.cat-chip').forEach(c => {
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

  // Configuración de listeners
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

    document.querySelectorAll('.pay-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.pay-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        const isCash = card.dataset.method === 'cash_efectivo';
        const cashBox = document.getElementById('cash-change-box');
        if (cashBox) {
          cashBox.style.display = isCash ? 'block' : 'none';
        }
      });
    });

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
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
