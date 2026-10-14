/**
 * =========================================================
 * ANTOJO VIRTUAL — Módulo de Autenticación JWT (Frontend)
 * Neiva, Huila • Neumorfismo Soft UI
 *
 * Responsabilidades:
 *  - Guardar / leer el access token (localStorage + memoria)
 *  - Renovar el access token automáticamente antes de que expire
 *  - Exponer helpers: Auth.fetch(), Auth.getUser(), Auth.logout()
 *  - Renderizar el botón de perfil / modal login-registro en el header
 * =========================================================
 */

const Auth = (() => {
  // ── Estado interno ──────────────────────────────────────
  const STORAGE_KEY      = 'antojo_access_token';
  const REFRESH_KEY      = 'antojo_refresh_token';
  const USER_KEY         = 'antojo_user';
  const API              = 'api/auth.php';
  let   _refreshTimer    = null;
  let   _refreshPromise  = null; // evita múltiples refrescos simultáneos

  // ── Almacenamiento ───────────────────────────────────────
  function saveTokens(accessToken, refreshToken, user) {
    try {
      localStorage.setItem(STORAGE_KEY, accessToken);
      if (refreshToken) localStorage.setItem(REFRESH_KEY, refreshToken);
      if (user)         localStorage.setItem(USER_KEY, JSON.stringify(user));
    } catch (e) {}
  }

  function clearTokens() {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(REFRESH_KEY);
    localStorage.removeItem(USER_KEY);
  }

  function getAccessToken()  { return localStorage.getItem(STORAGE_KEY); }
  function getRefreshToken() { return localStorage.getItem(REFRESH_KEY); }
  function getStoredUser()   {
    try { return JSON.parse(localStorage.getItem(USER_KEY)); } catch { return null; }
  }

  // ── Decodificación del JWT (sin verificar firma — cliente) ──
  function decodeToken(token) {
    if (!token) return null;
    try {
      const parts = token.split('.');
      if (parts.length !== 3) return null;
      const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
      return payload;
    } catch { return null; }
  }

  function isTokenExpired(token) {
    const payload = decodeToken(token);
    if (!payload || !payload.exp) return true;
    return Date.now() >= payload.exp * 1000;
  }

  function getExpiresInMs(token) {
    const payload = decodeToken(token);
    if (!payload || !payload.exp) return 0;
    return Math.max(0, payload.exp * 1000 - Date.now());
  }

  // ── Renovación automática del access token ───────────────
  function scheduleRefresh(token) {
    clearTimeout(_refreshTimer);
    const msLeft  = getExpiresInMs(token);
    // Renovar 60 segundos antes de que expire
    const delay   = Math.max(0, msLeft - 60_000);
    _refreshTimer = setTimeout(() => silentRefresh(), delay);
  }

  async function silentRefresh() {
    if (_refreshPromise) return _refreshPromise;

    _refreshPromise = (async () => {
      const refreshToken = getRefreshToken();
      if (!refreshToken) { _onSessionExpired(); return null; }

      try {
        const res  = await fetch(`${API}?action=refresh`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ refresh_token: refreshToken })
        });
        const data = await res.json();

        if (data.success && data.access_token) {
          saveTokens(data.access_token, data.refresh_token || refreshToken, null);
          scheduleRefresh(data.access_token);
          _renderAuthButton(); // Actualizar UI si cambió algo
          return data.access_token;
        } else {
          _onSessionExpired();
          return null;
        }
      } catch (err) {
        console.warn('[Auth] Error renovando token:', err);
        return null;
      } finally {
        _refreshPromise = null;
      }
    })();

    return _refreshPromise;
  }

  function _onSessionExpired() {
    clearTokens();
    clearTimeout(_refreshTimer);
    _renderAuthButton();
    if (typeof App !== 'undefined' && App.showToast) {
      App.showToast('⚠️ Sesión expirada. Inicia sesión nuevamente.');
    }
  }

  // ── Fetch autenticado (añade Authorization header automáticamente) ─
  async function authFetch(url, options = {}) {
    let token = getAccessToken();

    // Si el token está expirado, intentar renovar primero
    if (token && isTokenExpired(token)) {
      token = await silentRefresh();
    }

    const headers = { ...(options.headers || {}) };
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return fetch(url, { ...options, headers });
  }

  // ── API pública ───────────────────────────────────────────

  async function login(email, password) {
    const res  = await fetch(`${API}?action=login`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ email, password })
    });
    const data = await res.json();

    if (data.success) {
      saveTokens(data.access_token, data.refresh_token, data.user);
      scheduleRefresh(data.access_token);
      _renderAuthButton();
    }
    return data;
  }

  async function register(name, email, password, phone, address, comuna) {
    const res  = await fetch(`${API}?action=register`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ name, email, password, phone, address, comuna })
    });
    const data = await res.json();

    if (data.success) {
      saveTokens(data.access_token, data.refresh_token, data.user);
      scheduleRefresh(data.access_token);
      _renderAuthButton();
    }
    return data;
  }

  async function logout(allDevices = false) {
    const accessToken  = getAccessToken();
    const refreshToken = getRefreshToken();

    try {
      const headers = { 'Content-Type': 'application/json' };
      if (accessToken) headers['Authorization'] = `Bearer ${accessToken}`;

      await fetch(`${API}?action=logout`, {
        method:  'POST',
        headers,
        body:    JSON.stringify({ refresh_token: refreshToken, all_devices: allDevices })
      });
    } catch (e) { /* Ignorar errores de red en logout */ }

    clearTokens();
    clearTimeout(_refreshTimer);
    _renderAuthButton();
    if (typeof App !== 'undefined' && App.showToast) {
      App.showToast('Sesión cerrada. ¡Hasta pronto!');
    }
  }

  async function getProfile() {
    const res  = await authFetch(`${API}?action=me`);
    const data = await res.json();
    if (data.success && data.user) {
      saveTokens(getAccessToken(), getRefreshToken(), data.user);
    }
    return data;
  }

  async function verifyToken() {
    const token = getAccessToken();
    if (!token) return { authenticated: false };
    if (isTokenExpired(token)) {
      const newToken = await silentRefresh();
      if (!newToken) return { authenticated: false };
    }
    const res  = await authFetch(`${API}?action=verify`);
    return await res.json();
  }

  function getUser()         { return getStoredUser(); }
  function isAuthenticated() {
    const t = getAccessToken();
    return !!t && !isTokenExpired(t);
  }

  // ── UI: Modal de Login / Registro ────────────────────────

  function openModal(tab = 'login') {
    let modal = document.getElementById('auth-modal');
    if (!modal) _createModal();
    modal = document.getElementById('auth-modal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    _switchTab(tab);
  }

  function closeModal() {
    const modal = document.getElementById('auth-modal');
    if (modal) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  function _switchTab(tab) {
    document.getElementById('auth-tab-login')?.classList.toggle('active', tab === 'login');
    document.getElementById('auth-tab-register')?.classList.toggle('active', tab === 'register');
    document.getElementById('auth-form-login')?.style.setProperty('display', tab === 'login' ? 'block' : 'none');
    document.getElementById('auth-form-register')?.style.setProperty('display', tab === 'register' ? 'block' : 'none');
    document.getElementById('auth-modal-error')?.style.setProperty('display', 'none');
  }

  function _showError(msg) {
    const el = document.getElementById('auth-modal-error');
    if (el) { el.textContent = msg; el.style.display = 'block'; }
  }

  function _createModal() {
    const modal = document.createElement('div');
    modal.id        = 'auth-modal';
    modal.className = 'auth-modal-overlay';
    modal.innerHTML = `
      <div class="auth-modal-box" role="dialog" aria-modal="true" aria-label="Autenticación">
        <!-- Cerrar -->
        <button class="auth-modal-close" onclick="Auth.closeModal()" aria-label="Cerrar">
          <span class="material-symbols-outlined">close</span>
        </button>

        <!-- Logo -->
        <div style="text-align:center; margin-bottom:1.5rem;">
          <div class="auth-logo-circle">
            <span class="material-symbols-outlined" style="font-size:32px; color:#fea619;">lunch_dining</span>
          </div>
          <h2 class="auth-modal-title">ANTOJO VIRTUAL</h2>
          <p class="auth-modal-subtitle">Neiva, Huila • Tu antojo, nuestra misión</p>
        </div>

        <!-- Error -->
        <div id="auth-modal-error" class="auth-error-box" style="display:none;"></div>

        <!-- Tabs -->
        <div class="auth-tabs">
          <button id="auth-tab-login"    class="auth-tab active" onclick="Auth._switchTab('login')">
            <span class="material-symbols-outlined" style="font-size:18px;">login</span> Iniciar Sesión
          </button>
          <button id="auth-tab-register" class="auth-tab" onclick="Auth._switchTab('register')">
            <span class="material-symbols-outlined" style="font-size:18px;">person_add</span> Crear Cuenta
          </button>
        </div>

        <!-- Form Login -->
        <form id="auth-form-login" class="auth-form" onsubmit="Auth._handleLogin(event)">
          <div class="auth-field-group">
            <label class="auth-label" for="auth-email-login">
              <span class="material-symbols-outlined">email</span> Correo
            </label>
            <input id="auth-email-login"    type="email"    class="auth-input" placeholder="tu@correo.com"  required autocomplete="email">
          </div>
          <div class="auth-field-group">
            <label class="auth-label" for="auth-password-login">
              <span class="material-symbols-outlined">lock</span> Contraseña
            </label>
            <div class="auth-password-wrapper">
              <input id="auth-password-login" type="password" class="auth-input" placeholder="••••••••" required autocomplete="current-password">
              <button type="button" class="auth-eye-btn" onclick="Auth._togglePassword('auth-password-login', this)" aria-label="Ver contraseña">
                <span class="material-symbols-outlined">visibility</span>
              </button>
            </div>
          </div>
          <button type="submit" id="auth-btn-login" class="auth-submit-btn">
            <span class="material-symbols-outlined">login</span>
            <span id="auth-btn-login-text">Iniciar Sesión</span>
          </button>
          <p class="auth-switch-text">
            ¿No tienes cuenta? <a onclick="Auth._switchTab('register')" style="cursor:pointer; color: var(--color-primary-container); font-weight:700;">Regístrate gratis</a>
          </p>

          <!-- Credenciales de prueba -->
          <div class="auth-demo-creds">
            <p style="font-size:0.75rem; color:var(--color-on-surface-variant); font-weight:600; margin-bottom:0.4rem;">
              <span class="material-symbols-outlined" style="font-size:14px; vertical-align:-2px;">info</span>
              Cuentas de demostración:
            </p>
            <button type="button" class="auth-demo-btn" onclick="Auth._fillDemo('admin@antojo.com','Admin2026!')">
              🔴 Admin: admin@antojo.com / Admin2026!
            </button>
            <button type="button" class="auth-demo-btn" onclick="Auth._fillDemo('mateo@gmail.com','Admin2026!')">
              🟢 Cliente: mateo@gmail.com / Admin2026!
            </button>
          </div>
        </form>

        <!-- Form Registro -->
        <form id="auth-form-register" class="auth-form" style="display:none;" onsubmit="Auth._handleRegister(event)">
          <div class="auth-fields-grid">
            <div class="auth-field-group">
              <label class="auth-label" for="auth-reg-name">
                <span class="material-symbols-outlined">person</span> Nombre completo
              </label>
              <input id="auth-reg-name"     type="text"     class="auth-input" placeholder="Tu nombre" required autocomplete="name">
            </div>
            <div class="auth-field-group">
              <label class="auth-label" for="auth-reg-phone">
                <span class="material-symbols-outlined">phone</span> Teléfono
              </label>
              <input id="auth-reg-phone"    type="tel"      class="auth-input" placeholder="+57 310 000 0000" autocomplete="tel">
            </div>
          </div>
          <div class="auth-field-group">
            <label class="auth-label" for="auth-reg-email">
              <span class="material-symbols-outlined">email</span> Correo electrónico
            </label>
            <input id="auth-reg-email"    type="email"    class="auth-input" placeholder="tu@correo.com" required autocomplete="email">
          </div>
          <div class="auth-field-group">
            <label class="auth-label" for="auth-reg-password">
              <span class="material-symbols-outlined">lock</span> Contraseña
            </label>
            <div class="auth-password-wrapper">
              <input id="auth-reg-password" type="password" class="auth-input" placeholder="Mín. 8 chars, 1 mayúscula, 1 número" required autocomplete="new-password">
              <button type="button" class="auth-eye-btn" onclick="Auth._togglePassword('auth-reg-password', this)" aria-label="Ver contraseña">
                <span class="material-symbols-outlined">visibility</span>
              </button>
            </div>
            <div id="auth-pwd-strength" class="auth-pwd-strength"></div>
          </div>
          <div class="auth-field-group">
            <label class="auth-label" for="auth-reg-address">
              <span class="material-symbols-outlined">home</span> Dirección de entrega
            </label>
            <input id="auth-reg-address"  type="text"     class="auth-input" placeholder="Cra 5 #10-00, Neiva" autocomplete="street-address">
          </div>
          <div class="auth-field-group">
            <label class="auth-label" for="auth-reg-comuna">
              <span class="material-symbols-outlined">location_on</span> Comuna de Neiva
            </label>
            <select id="auth-reg-comuna" class="auth-input auth-select">
              <option>Comuna 4 - Centro</option>
              <option>Comuna 1 - Cándido / Camilo Torres</option>
              <option>Comuna 2 - Las Granjas / Los Mártires</option>
              <option>Comuna 3 - Quirinal / Santa Inés</option>
              <option>Comuna 5 - Altico / La Floresta</option>
              <option>Comuna 6 - Timanco / Puertas del Sol</option>
              <option>Comuna 7 - Ipanema / Las Brisas</option>
              <option>Comuna 8 - Las Américas</option>
              <option>Comuna 9 - Luis Carlos Galán</option>
              <option>Comuna 10 - Las Palmas</option>
            </select>
          </div>
          <button type="submit" id="auth-btn-register" class="auth-submit-btn">
            <span class="material-symbols-outlined">person_add</span>
            <span id="auth-btn-register-text">Crear Cuenta Gratis</span>
          </button>
          <p class="auth-switch-text">
            ¿Ya tienes cuenta? <a onclick="Auth._switchTab('login')" style="cursor:pointer; color: var(--color-primary-container); font-weight:700;">Inicia sesión aquí</a>
          </p>
        </form>
      </div>
    `;
    document.body.appendChild(modal);

    // Cerrar al hacer click en el overlay
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });

    // Indicador de fortaleza de contraseña
    const pwdInput = document.getElementById('auth-reg-password');
    if (pwdInput) {
      pwdInput.addEventListener('input', () => _checkPasswordStrength(pwdInput.value));
    }
  }

  // ── Manejadores de formularios ─────────────────────────

  async function _handleLogin(e) {
    e.preventDefault();
    const btn  = document.getElementById('auth-btn-login');
    const text = document.getElementById('auth-btn-login-text');
    const email    = document.getElementById('auth-email-login')?.value.trim();
    const password = document.getElementById('auth-password-login')?.value;

    btn.disabled = true;
    text.textContent = 'Verificando...';
    document.getElementById('auth-modal-error').style.display = 'none';

    const data = await login(email, password);

    if (data.success) {
      closeModal();
      if (typeof App !== 'undefined' && App.showToast) {
        App.showToast(`✅ ¡Bienvenido, ${data.user.name}!`);
      }
    } else {
      _showError(data.message || 'Credenciales inválidas.');
    }

    btn.disabled = false;
    text.textContent = 'Iniciar Sesión';
  }

  async function _handleRegister(e) {
    e.preventDefault();
    const btn  = document.getElementById('auth-btn-register');
    const text = document.getElementById('auth-btn-register-text');

    const name     = document.getElementById('auth-reg-name')?.value.trim();
    const email    = document.getElementById('auth-reg-email')?.value.trim();
    const password = document.getElementById('auth-reg-password')?.value;
    const phone    = document.getElementById('auth-reg-phone')?.value.trim();
    const address  = document.getElementById('auth-reg-address')?.value.trim();
    const comuna   = document.getElementById('auth-reg-comuna')?.value;

    btn.disabled = true;
    text.textContent = 'Creando cuenta...';
    document.getElementById('auth-modal-error').style.display = 'none';

    const data = await register(name, email, password, phone, address, comuna);

    if (data.success) {
      closeModal();
      if (typeof App !== 'undefined' && App.showToast) {
        App.showToast(`🎉 ¡Cuenta creada! Bienvenido a Antojo Virtual, ${data.user.name}!`);
      }
    } else {
      _showError(data.message || 'Error al crear la cuenta.');
    }

    btn.disabled = false;
    text.textContent = 'Crear Cuenta Gratis';
  }

  function _fillDemo(email, password) {
    const emailEl = document.getElementById('auth-email-login');
    const passEl  = document.getElementById('auth-password-login');
    if (emailEl) emailEl.value = email;
    if (passEl)  passEl.value  = password;
  }

  function _togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    const icon = btn.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = isHidden ? 'visibility_off' : 'visibility';
  }

  function _checkPasswordStrength(pwd) {
    const el = document.getElementById('auth-pwd-strength');
    if (!el) return;

    let score = 0;
    let label = '';
    let color = '';

    if (pwd.length >= 8)              score++;
    if (/[A-Z]/.test(pwd))            score++;
    if (/[0-9]/.test(pwd))            score++;
    if (/[^A-Za-z0-9]/.test(pwd))    score++;
    if (pwd.length >= 12)             score++;

    if      (score <= 1) { label = 'Muy débil';  color = '#ef4444'; }
    else if (score === 2) { label = 'Débil';      color = '#f97316'; }
    else if (score === 3) { label = 'Regular';    color = '#eab308'; }
    else if (score === 4) { label = 'Fuerte';     color = '#22c55e'; }
    else                  { label = 'Muy fuerte'; color = '#16a34a'; }

    const pct = Math.min(100, score * 20);
    el.innerHTML = `
      <div style="height:4px; background:var(--color-border-subtle); border-radius:2px; overflow:hidden; margin-top:6px;">
        <div style="height:100%; width:${pct}%; background:${color}; transition: width 0.3s ease; border-radius:2px;"></div>
      </div>
      <span style="font-size:0.72rem; color:${color}; font-weight:700;">${label}</span>
    `;
  }

  // ── Botón de auth en el header ────────────────────────

  function _renderAuthButton() {
    const container = document.getElementById('header-auth-btn');
    if (!container) return;

    const user = getUser();
    if (user && isAuthenticated()) {
      const roleIcon = { admin: '🔴', restaurant: '🟡', rider: '🟢', customer: '🟢' }[user.role] || '👤';
      container.innerHTML = `
        <div class="auth-user-chip" id="auth-user-menu-trigger" title="Cuenta: ${user.name}">
          <div class="auth-avatar">${user.name.charAt(0).toUpperCase()}</div>
          <span class="auth-user-name">${user.name.split(' ')[0]}</span>
          <span class="material-symbols-outlined" style="font-size:16px; opacity:0.7;">expand_more</span>
        </div>
        <div class="auth-dropdown" id="auth-user-dropdown">
          <div class="auth-dropdown-header">
            <strong>${user.name}</strong>
            <span style="font-size:0.75rem; color:var(--color-on-surface-variant);">${roleIcon} ${user.role}</span>
          </div>
          <a class="auth-dropdown-item" onclick="Auth._goToProfile()">
            <span class="material-symbols-outlined">person</span> Mi Perfil
          </a>
          ${user.role === 'admin' || user.role === 'restaurant' ? `
          <a class="auth-dropdown-item" href="admin.php">
            <span class="material-symbols-outlined">kitchen</span> Panel Cocina
          </a>` : ''}
          <hr style="border-color: var(--color-border-subtle); margin: 0.3rem 0;">
          <a class="auth-dropdown-item auth-logout-item" onclick="Auth._confirmLogout()">
            <span class="material-symbols-outlined">logout</span> Cerrar Sesión
          </a>
        </div>
      `;

      // Toggle del dropdown
      const trigger = document.getElementById('auth-user-menu-trigger');
      const dropdown = document.getElementById('auth-user-dropdown');
      if (trigger && dropdown) {
        trigger.onclick = (e) => {
          e.stopPropagation();
          dropdown.classList.toggle('open');
        };
        document.addEventListener('click', () => dropdown.classList.remove('open'), { once: false });
      }
    } else {
      container.innerHTML = `
        <button class="btn-auth-login" onclick="Auth.openModal('login')" id="auth-header-login-btn">
          <span class="material-symbols-outlined" style="font-size:18px;">person</span>
          <span>Iniciar Sesión</span>
        </button>
      `;
    }
  }

  async function _goToProfile() {
    document.getElementById('auth-user-dropdown')?.classList.remove('open');
    const data = await getProfile();
    if (data.success) {
      const u = data.user;
      const orders = data.orders || [];
      if (typeof App !== 'undefined' && App.showToast) {
        App.showToast(`👤 ${u.name} • ${orders.length} pedidos en historial`);
      }
    }
  }

  function _confirmLogout() {
    document.getElementById('auth-user-dropdown')?.classList.remove('open');
    if (confirm('¿Seguro que quieres cerrar sesión?')) {
      logout();
    }
  }

  // ── Inicialización ─────────────────────────────────────

  function init() {
    _renderAuthButton();

    // Si hay token vigente, programar el refresco automático
    const token = getAccessToken();
    if (token && !isTokenExpired(token)) {
      scheduleRefresh(token);
    } else if (getRefreshToken()) {
      // Intentar refresco silencioso al cargar
      silentRefresh();
    }
  }

  // ── Interfaz pública ───────────────────────────────────
  return {
    init,
    login,
    register,
    logout,
    getProfile,
    verifyToken,
    getUser,
    isAuthenticated,
    getAccessToken,
    fetch: authFetch,
    openModal,
    closeModal,
    // Expuestos para llamadas desde HTML inline
    _switchTab,
    _handleLogin,
    _handleRegister,
    _fillDemo,
    _togglePassword,
    _goToProfile,
    _confirmLogout
  };
})();

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => Auth.init());
