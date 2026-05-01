// ============================================
// ROULEZ.TN - Main JavaScript
// Native JS — No frameworks
// ============================================

/* ── Toast Notifications ── */
function showToast(message, type = 'info', duration = 3500) {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/* ── Auth State ── */
let currentUser = null;

function checkAuthState() {
  fetch('auth.php?action=status')
    .then(r => r.json())
    .then(data => {
      currentUser = data.logged_in ? data.user : null;
      updateNavUI();
    })
    .catch(() => updateNavUI());
}

function updateNavUI() {
  const nameEl     = document.getElementById('nav-user-name');
  const loginBtn   = document.getElementById('nav-login-btn');
  const logoutBtn  = document.getElementById('nav-logout-btn');

  if (currentUser) {
    if (nameEl)    { nameEl.textContent = currentUser.full_name.split(' ')[0]; nameEl.style.display = 'inline'; }
    if (loginBtn)  loginBtn.style.display = 'none';
    if (logoutBtn) logoutBtn.style.display = 'inline-flex';
  } else {
    if (nameEl)    nameEl.style.display = 'none';
    if (loginBtn)  loginBtn.style.display = 'inline-flex';
    if (logoutBtn) logoutBtn.style.display = 'none';
  }
}

function logout() {
  fetch('auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) })
    .then(r => r.json())
    .then(data => {
      currentUser = null;
      updateNavUI();
      showToast('Vous avez été déconnecté.', 'info');
      setTimeout(() => window.location.href = 'index.html', 900);
    });
}

/* ── Modal Management ── */
function openModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) {
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
});

/* ── Form Validation Helpers ── */
function validateField(input, rules) {
  const val = input.value.trim();
  let errorMsg = '';

  if (rules.required && !val) {
    errorMsg = 'Ce champ est obligatoire.';
  } else if (rules.email && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
    errorMsg = 'Adresse email invalide.';
  } else if (rules.minLength && val.length < rules.minLength) {
    errorMsg = `Minimum ${rules.minLength} caractères requis.`;
  } else if (rules.min && parseFloat(val) < rules.min) {
    errorMsg = `Valeur minimale: ${rules.min}.`;
  } else if (rules.phone && val && !/^[\d\s\+]{8,15}$/.test(val)) {
    errorMsg = 'Numéro de téléphone invalide.';
  }

  const errorEl = input.nextElementSibling;
  if (errorEl && errorEl.classList.contains('form-error')) {
    if (errorMsg) {
      input.classList.add('error');
      errorEl.textContent = errorMsg;
      errorEl.classList.add('visible');
    } else {
      input.classList.remove('error');
      errorEl.classList.remove('visible');
    }
  }
  return !errorMsg;
}

function clearFormErrors(form) {
  form.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));
  form.querySelectorAll('.form-error.visible').forEach(el => el.classList.remove('visible'));
}

/* ── Auth Forms (Login / Register) ── */
function setupAuthForms() {
  const loginForm    = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');

  // Tab switching
  document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      const target = this.dataset.tab;
      document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      document.querySelectorAll('.auth-panel').forEach(p => p.classList.add('hidden'));
      const panel = document.getElementById(target + '-panel');
      if (panel) panel.classList.remove('hidden');
    });
  });

  // Login
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const email    = this.querySelector('#login-email');
      const password = this.querySelector('#login-password');
      let valid = true;
      valid = validateField(email,    { required: true, email: true }) && valid;
      valid = validateField(password, { required: true }) && valid;
      if (!valid) return;

      const btn = this.querySelector('button[type=submit]');
      btn.innerHTML = '<span class="spinner"></span> Connexion...';
      btn.disabled = true;

      const formData = new FormData();
      formData.append('action', 'login');
      formData.append('email', email.value);
      formData.append('password', password.value);

      fetch('auth.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          btn.innerHTML = 'Se connecter';
          btn.disabled = false;
          if (data.success) {
            currentUser = { full_name: data.full_name };
            updateNavUI();
            showToast(data.message, 'success');
            closeModal('auth-modal');
            setTimeout(() => window.location.reload(), 600);
          } else {
            showToast(data.message, 'error');
          }
        })
        .catch(() => { btn.innerHTML = 'Se connecter'; btn.disabled = false; showToast('Erreur réseau.', 'error'); });
    });
  }

  // Register
  if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const fields = {
        'reg-name':     { required: true, minLength: 3 },
        'reg-email':    { required: true, email: true },
        'reg-phone':    { required: true, phone: true },
        'reg-cin':      { required: true, minLength: 5 },
        'reg-password': { required: true, minLength: 6 },
      };
      let valid = true;
      for (const [id, rules] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el) valid = validateField(el, rules) && valid;
      }
      if (!valid) return;

      const btn = this.querySelector('button[type=submit]');
      btn.innerHTML = '<span class="spinner"></span> Inscription...';
      btn.disabled = true;

      const formData = new FormData(this);
      formData.append('action', 'register');

      fetch('auth.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          btn.innerHTML = "S'inscrire";
          btn.disabled = false;
          if (data.success) {
            currentUser = { full_name: data.message };
            updateNavUI();
            showToast(data.message, 'success');
            closeModal('auth-modal');
            setTimeout(() => window.location.reload(), 600);
          } else {
            showToast(data.message, 'error');
          }
        })
        .catch(() => { btn.innerHTML = "S'inscrire"; btn.disabled = false; });
    });
  }
}

/* ── Machine Grid Renderer ── */
const TYPE_ICONS  = { car: '🚗', bike: '🚲', motorcycle: '🏍️', scooter: '🛵' };
const TYPE_LABELS = { car: 'Voiture', bike: 'Vélo', motorcycle: 'Moto', scooter: 'Scooter' };

function renderMachineCard(m) {
  const icon    = TYPE_ICONS[m.type] || '🚗';
  const label   = TYPE_LABELS[m.type] || m.type;
  const imgHTML = m.photo
    ? `<img src="uploads/${m.photo}" alt="${m.brand} ${m.model}" loading="lazy">`
    : `<div class="img-placeholder">${icon}</div>`;

  const from = new Date(m.available_from).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  const to   = new Date(m.available_to).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });

  return `
    <article class="card machine-card" data-id="${m.id}">
      <div class="card-img">
        ${imgHTML}
        <span class="card-type-badge">${label}</span>
      </div>
      <div class="card-body">
        <h3 class="card-title">${m.brand} ${m.model} (${m.year})</h3>
        <p class="card-city">${m.city}</p>
        <p class="card-dates">📅 ${from} → ${to}</p>
        <div class="card-footer">
          <div class="card-price">${parseFloat(m.price_per_day).toFixed(0)} DT<span>/jour</span></div>
          <button class="btn btn-primary btn-sm" onclick="viewMachine(${m.id})">Voir détails</button>
        </div>
      </div>
    </article>`;
}

/* ── Navigate to detail ── */
function viewMachine(id) {
  window.location.href = `detail.html?id=${id}`;
}

/* ── Credit Card Formatting ── */
function setupCardInputs() {
  const cardNum = document.getElementById('card-number');
  if (cardNum) {
    cardNum.addEventListener('input', function() {
      let v = this.value.replace(/\D/g, '').slice(0, 16);
      this.value = v.replace(/(.{4})/g, '$1 ').trim();
      const display = document.getElementById('card-num-display');
      if (display) {
        const padded = (v + '################').slice(0, 16);
        display.textContent = padded.replace(/(.{4})/g, '$1 ').trim();
      }
    });
  }
  const cardExp = document.getElementById('card-expiry');
  if (cardExp) {
    cardExp.addEventListener('input', function() {
      let v = this.value.replace(/\D/g, '').slice(0, 4);
      if (v.length >= 2) v = v.slice(0, 2) + '/' + v.slice(2);
      this.value = v;
    });
  }
  const cardName = document.getElementById('card-name');
  if (cardName) {
    cardName.addEventListener('input', function() {
      const display = document.getElementById('card-name-display');
      if (display) display.textContent = this.value || 'VOTRE NOM';
    });
  }
}

/* ── Nav Mobile Toggle ── */
function setupMobileNav() {
  const toggle = document.querySelector('.nav-mobile-toggle');
  const links  = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => links.classList.toggle('open'));
  }
}

/* ── Highlight active nav link ── */
function highlightActiveNav() {
  const path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.getAttribute('href') === path) a.classList.add('active');
  });
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', function() {
  checkAuthState();
  setupAuthForms();
  setupCardInputs();
  setupMobileNav();
  highlightActiveNav();
});
