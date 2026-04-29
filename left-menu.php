<?php
/**
 * LEFT MENU (sidebar) dinámico por BD
 * Incluir con: include 'left-menu.php';
 *
 * Requisitos:
 * - $_SESSION['ROL'] = id_rol del usuario autenticado.
 * - Tablas: iconos, menu_pagina, menu_rol.
 * - Conexión $conn (si no existe, se incluye conexion.php).
 *
 * Comportamiento:
 * - Escritorio (>=1024px): colapsable con persistencia localStorage.
 * - Móvil/Tablet (<1024px): off-canvas con FAB/backdrop.
 * - "is-active" automático por URL actual.
 * - Si no hay sesión o no hay permisos, se muestra un fallback a login.php
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$user_role = $_SESSION['ROL'] ?? null;

// Conexión
if (!isset($conn)) {
  require_once __DIR__ . '/conexion.php';
}

// Consulta de páginas por rol
$pages = [];
if ($user_role !== null) {
  $sql = "SELECT
    mp.id_mp, mp.nombre_pagina, mp.archivo, ic.imagen AS svg
    FROM menu_pagina mp
    INNER JOIN menu_rol mr ON mr.id_pagina = mp.id_mp AND mr.id_rol = ?
    INNER JOIN iconos ic ON ic.id_icono = mp.id_icono
    WHERE COALESCE(mp.ocultar,0) = 0 AND COALESCE(mr.ocultar,0) = 0
    ORDER BY mp.id_mp
  ";
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $user_role);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $pages[] = $row;
    }
    $stmt->close();
  }
}

// Fallback si no hay sesión o no hay páginas para el rol
$renderFallback = (empty($pages));


// Detectar ruta actual o forzarla (override) para "is-active"
$overrideActiveFile = $overrideActiveFile ?? ($LEFT_ACTIVE_OVERRIDE ?? null);
$currentFile = strtolower(
  basename(
    $overrideActiveFile ? $overrideActiveFile : (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '')
  )
);

?>
<!-- Backdrop para off-canvas en móviles -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="appSidebar" aria-label="Menú lateral de la plataforma">
  <!-- Header del sidebar -->
  <div class="sidebar-header">
    <div style="display:flex; align-items:center; min-width:0;">
      <div class="brand-mini" aria-hidden="true" title="Plataforma Académica">
        <!-- Birrete -->
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
          <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2 class="brand-title">Menú</h2>
    </div>

    <!-- Botón SIEMPRE visible en escritorio (colapsa/expande) -->
    <button id="sidebarCollapseBtn" class="collapse-btn" type="button" title="Contraer menú" aria-label="Contraer menú" aria-pressed="false">
      <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
        <!-- Doble flecha ←← (se invertirá en JS cuando esté colapsado) -->
        <path d="M11 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M20 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <!-- Zona desplazable -->
  <div class="sidebar-scroll">
    <nav class="menu" id="sidebarMenu">
      <div class="menu-section">Navegación</div>

      <?php if (!$renderFallback): ?>
        <?php foreach ($pages as $p): 
          $href = $p['archivo'];
          $isActive = (strtolower(basename($href)) === $currentFile) ? ' is-active' : '';
        ?>
          <a class="menu-item<?= $isActive ?>" href="<?= htmlspecialchars($href) ?>" title="<?= htmlspecialchars($p['nombre_pagina']) ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <!-- SVG desde BD (confiable) -->
              <?= $p['svg'] ?>
            </svg>
            <span class="label"><?= htmlspecialchars($p['nombre_pagina']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Fallback sin sesión/permisos: mostrar Login -->
        <a class="menu-item<?= ($currentFile === 'login.php' ? ' is-active' : '') ?>" href="login.php" title="Login">
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="7.5" cy="15.5" r="3.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="M10.5 15.5H22M15 12v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span class="label">Login</span>
        </a>
      <?php endif; ?>

      <!-- Puedes dejar una sección de cuenta si quieres mostrar ajustes/help sólo a ciertos roles
      <div class="menu-section">Cuenta</div>
      -->
    </nav>
  </div>

  <!-- Footer del sidebar (opcional) -->
  <!--
  <div class="sidebar-footer">
    <a class="menu-item" href="help.php" title="Ayuda">
      <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
        <path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.7.35-1 1-1 1.7V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        <circle cx="12" cy="17" r="1" fill="currentColor"/>
      </svg>
      <span class="label">Ayuda</span>
    </a>
  </div>
  -->
</aside>

<!-- Botón flotante móvil para abrir/cerrar -->
<button id="sidebarFab" class="sidebar-fab" type="button" aria-label="Abrir menú">
  <!-- Hamburger -->
  <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
    <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
  </svg>
</button>

<script>
/* ====== Lógica del sidebar ======
   - Escritorio (>=1024px): expandido/colapsado con persistencia localStorage('sidebarCollapsed')
   - Móvil/Tablet (<1024px): off-canvas; abre con body.sidebar-open
   - Auto "is-active" (ya marcado en PHP; aquí solo fallback por rutas raras)
*/
(function(){
  const mqDesktop = window.matchMedia('(min-width: 1024px)');
  const body = document.body;
  const collapseBtn = document.getElementById('sidebarCollapseBtn');
  const fab = document.getElementById('sidebarFab');
  const backdrop = document.getElementById('sidebarBackdrop');
  const menu = document.getElementById('sidebarMenu');

  function setBodyState() {
    if (mqDesktop.matches) {
      body.classList.remove('sidebar-offcanvas', 'sidebar-open');
      const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      body.classList.toggle('sidebar-collapsed', collapsed);
      body.classList.toggle('sidebar-expanded', !collapsed);
      updateCollapseButton(collapsed);
    } else {
      body.classList.add('sidebar-offcanvas');
      body.classList.remove('sidebar-expanded', 'sidebar-collapsed');
      body.classList.remove('sidebar-open'); // cerrado por defecto
      updateCollapseButton(false);
    }
  }

  function updateCollapseButton(isCollapsed) {
    collapseBtn.setAttribute('title', isCollapsed ? 'Expandir menú' : 'Contraer menú');
    collapseBtn.setAttribute('aria-label', isCollapsed ? 'Expandir menú' : 'Contraer menú');
    collapseBtn.setAttribute('aria-pressed', isCollapsed ? 'true' : 'false');
    collapseBtn.innerHTML = isCollapsed
      ? `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
           <path d="M13 5l7 7-7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
           <path d="M4 5l7 7-7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
         </svg>`
      : `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
           <path d="M11 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
           <path d="M20 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
         </svg>`;
  }

  function toggleCollapse(){
    if (!mqDesktop.matches) return;
    const collapsed = body.classList.contains('sidebar-collapsed');
    body.classList.toggle('sidebar-collapsed', !collapsed);
    body.classList.toggle('sidebar-expanded', collapsed);
    localStorage.setItem('sidebarCollapsed', String(!collapsed));
    updateCollapseButton(!collapsed);
  }

  function toggleOffcanvas(){
    if (!body.classList.contains('sidebar-offcanvas')) return;
    body.classList.toggle('sidebar-open');
  }

  // Fallback extra de active por si la URL trae query o hash raros
  function ensureActive(){
    const path = window.location.pathname.split('/').pop().toLowerCase();
    const links = menu.querySelectorAll('a.menu-item');
    let found = false;
    links.forEach(a => {
      const href = (a.getAttribute('href') || '').toLowerCase();
      if (href === path) {
        a.classList.add('is-active');
        a.setAttribute('aria-current', 'page');
        found = true;
      }
    });
    if (!found && path === '') {
      // Si es la raíz y tienes index.php
      links.forEach(a => {
        const href = (a.getAttribute('href') || '').toLowerCase();
        if (href === 'index.php') {
          a.classList.add('is-active');
          a.setAttribute('aria-current', 'page');
        }
      });
    }
  }

  setBodyState();
  ensureActive();
  mqDesktop.addEventListener('change', setBodyState);

  if (collapseBtn) collapseBtn.addEventListener('click', toggleCollapse);
  if (fab) fab.addEventListener('click', toggleOffcanvas);
  if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('sidebar-open'));

  document.addEventListener('keydown', (e)=> {
    if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
      body.classList.remove('sidebar-open');
    }
  });
})();
</script>
