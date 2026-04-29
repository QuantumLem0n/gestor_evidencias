<?php
// Header global (roles reales: Administrador, Docente, Evaluador, Super Usuario)
// - Usa variables de sesión. Si no hay sesión, no muestra dropdown de usuario.
// - Subtítulo: "Panel de <Rol>" según RNOMBRE.
// - En desktop: perfil compacto (solo avatar) + botón de tema + cerrar sesión en el dropdown.
// - En móvil: hamburguesa para abrir el left-menu + avatar con dropdown (perfil, tema, cerrar sesión).

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$loggedIn = isset($_SESSION['SESUSUARIO']);

// Normaliza el nombre del rol desde la sesión
$roleNameRaw = $_SESSION['RNOMBRE'] ?? '';
$roleName = mb_strtolower(trim($roleNameRaw), 'UTF-8');

// Determina el subtítulo visible bajo el título
$panelLabel = 'Autenticación';
if ($loggedIn) {
  if ($roleName === 'administrador') {
    $panelLabel = 'Panel de Administrador';
  } elseif ($roleName === 'docente') {
    $panelLabel = 'Panel de Docente';
  } elseif ($roleName === 'evaluador') {
    $panelLabel = 'Panel de Evaluador';
  } elseif ($roleName === 'super usuario' || $roleName === 'superusuario' || $roleName === 'super_user') {
    $panelLabel = 'Panel de Super Usuario';
  } else {
    $panelLabel = 'Panel';
  }
}

// Tema / fondo del <body> (puedes diferenciar por rol más adelante)
$bodyClass = 'teacher-theme teacher-gradient-bg';

// Datos de usuario para avatar
$fullName = trim(($_SESSION['SESUSUARIO'] ?? '') . ' ' . ($_SESSION['APELLIDO'] ?? ''));
$parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
$initials = '';
if (!empty($parts)) {
  $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
  if (count($parts) > 1) {
    $initials .= mb_strtoupper(mb_substr(end($parts), 0, 1, 'UTF-8'));
  }
}
$initials = $initials ?: 'US';
$emailUser = $_SESSION['EMAIL'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ODEA - Plataforma Académica</title>
  <link rel="shortcut icon" href="assets/icons/favicon.ico">
  <link rel="stylesheet" href="styles/global.css">
</head>
<body class="page <?= htmlspecialchars($bodyClass) ?>">
  <header class="app-header">
    <div class="header-inner container header-bar">
      <!-- IZQUIERDA: Botón hamburguesa (móvil) + Marca -->
      <div class="header-left">
        <?php if ($loggedIn): ?>
          <button id="headerHamburger" class="btn-icon only-mobile" type="button" aria-label="Abrir menú">
            <!-- Hamburguesa -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </button>
        <?php endif; ?>

        <div class="brand">
          <div class="brand-box" aria-hidden="true" title="Plataforma Académica">
            <!-- Birrete -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
              <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="brand-text">
            <h1 class="brand-title" title="Plataforma Académica">ODEA</h1>
            <p class="brand-subtitle"><?= htmlspecialchars($panelLabel) ?></p>
          </div>
        </div>
      </div>

      <!-- DERECHA: Tema (desktop) + Avatar con dropdown -->
      <div class="header-right">
        <!-- Botón de tema visible en desktop -->
        <button id="themeToggle" class="btn hide-on-mobile" type="button" aria-pressed="false">
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="btn-text">Oscuro</span>
        </button>

        <?php if ($loggedIn): ?>
          <!-- Dropdown de usuario (desktop y móvil) -->
          <div class=" dropdown dropdown-right">
            <button id="profileBtn" class="avatar avatar-button" aria-haspopup="menu" aria-expanded="false" aria-controls="profileMenu">
              <?= htmlspecialchars($initials) ?>
            </button>
            <div id="profileMenu" class="dropdown-menu dropdown-right" role="menu" aria-labelledby="profileBtn">
              <div class="dropdown-header">
                <div class="avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
                <div class="profile-text">
                  <p class="profile-name"><?= htmlspecialchars($fullName ?: 'Usuario') ?></p>
                  <p class="profile-email"><?= htmlspecialchars($emailUser) ?></p>
                </div>
              </div>

              <div class="dropdown-sep"></div>

              <!-- Toggle tema (también disponible dentro del menú; útil en móvil) -->
              <button id="themeToggleInMenu" class="dropdown-item" type="button">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Cambiar tema</span>
              </button>

              <a class="dropdown-item" href="perfil.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z" stroke="currentColor" stroke-width="1.8"/>
                  <path d="M3 21a9 9 0 0 1 18 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span>Mi Perfil</span>
              </a>

              <div class="dropdown-sep"></div>

              <a class="dropdown-item text-danger" href="logout.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                  <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span>Cerrar Sesión</span>
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
<script>
// ===== Modo oscuro persistente =====
(function(){
  const htmlEl = document.documentElement;
  const saved = localStorage.getItem('darkMode');
  let isDark = false;
  if (saved !== null) { try { isDark = JSON.parse(saved); } catch(e){} }

  function renderToggle(btn, enabled){
    if (!btn) return;
    btn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    if (btn.id === 'themeToggle') {
      btn.innerHTML = enabled
        ? `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
             <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
             <path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
           </svg><span class="btn-text">Claro</span>`
        : `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
             <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
           </svg><span class="btn-text">Oscuro</span>`;
    }
  }
  function setDark(enabled){
    htmlEl.classList.toggle('dark', enabled);
    localStorage.setItem('darkMode', JSON.stringify(enabled));
    renderToggle(document.getElementById('themeToggle'), enabled);
  }

  // Init
  setDark(isDark);

  addEventListener('DOMContentLoaded', function(){
    const deskBtn = document.getElementById('themeToggle');
    const menuBtn = document.getElementById('themeToggleInMenu');

    if (deskBtn) deskBtn.addEventListener('click', function(){
      setDark(!document.documentElement.classList.contains('dark'));
    });
    if (menuBtn) menuBtn.addEventListener('click', function(){
      setDark(!document.documentElement.classList.contains('dark'));
      closeProfileMenu();
    });
  });
})();

// ===== Dropdown perfil (desktop/móvil) =====
(function(){
  const btn  = document.getElementById('profileBtn');
  const menu = document.getElementById('profileMenu');
  if (!btn || !menu) return;

  function toggleMenu(){
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    menu.classList.toggle('open', !expanded);
  }
  function closeOnOutside(e){
    if (!menu.classList.contains('open')) return;
    if (!menu.contains(e.target) && e.target !== btn) {
      btn.setAttribute('aria-expanded', 'false');
      menu.classList.remove('open');
    }
  }
  function closeOnEsc(e){
    if (e.key === 'Escape' && menu.classList.contains('open')) {
      btn.setAttribute('aria-expanded', 'false');
      menu.classList.remove('open');
    }
  }
  btn.addEventListener('click', toggleMenu);
  document.addEventListener('click', closeOnOutside);
  document.addEventListener('keydown', closeOnEsc);

  // Exponer utilitario global
  window.closeProfileMenu = function(){
    btn.setAttribute('aria-expanded', 'false');
    menu.classList.remove('open');
  };
})();

// ===== Hamburguesa header -> abrir/cerrar left-menu (off-canvas móvil) =====
(function(){
  const hb = document.getElementById('headerHamburger');
  if (!hb) return;
  hb.addEventListener('click', function(){
    // left-menu.php usa body.sidebar-offcanvas + body.sidebar-open
    document.body.classList.toggle('sidebar-open');
  });
})();
</script>
