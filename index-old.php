<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Plataforma Académica — Header (HTML/CSS/JS)</title>
  <link rel="stylesheet" href="styles/global.css">
</head>
<body class="page student-theme student-gradient-bg">
  <!-- 
    Cambia 'student-theme student-gradient-bg' por 
    'teacher-theme teacher-gradient-bg' para ver los colores de profesor.
  -->
  <header class="app-header">
    <div class="header-inner container">
      <div class="brand">
        <div class="brand-box" aria-hidden="true" title="Plataforma Académica">
          <!-- Icono Birrete -->
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <h1 class="brand-title">Plataforma Académica</h1>
          <p class="brand-subtitle" id="roleSubtitle">Panel de Estudiante</p>
        </div>
      </div>

      <div class="right">
        <div class="profile">
          <div class="avatar" id="avatar">AG</div>
          <div class="profile-text">
            <p class="profile-name" id="userName">Ana García</p>
            <p class="profile-email" id="userEmail">ana.garcia@universidad.edu</p>
          </div>
        </div>

        <button id="themeToggle" class="btn" type="button" aria-pressed="false">
          <!-- Icono Luna (modo claro → botón muestra Luna para pasar a oscuro) -->
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="btn-text">Oscuro</span>
        </button>

        <button class="btn" type="button" disabled>
          Cerrar Sesión
        </button>
      </div>
    </div>
  </header>

  <main class="container">
    <!-- Aquí irá tu contenido/dashboards en pasos posteriores -->
    <div style="padding:16px; border:1px dashed var(--border); border-radius:12px; background: color-mix(in srgb, var(--muted) 50%, transparent);">
      <strong>Placeholder de contenido</strong><br/>
      En el siguiente paso integraremos el Login y los dashboards de Estudiante/Profesor.
    </div>
  </main>

  <script>
    // ===========================
    // Modo oscuro: persistencia y toggle
    // ===========================
    const prefers = localStorage.getItem('darkMode');
    const htmlEl = document.documentElement;
    const toggleBtn = document.getElementById('themeToggle');
    const btnText = toggleBtn.querySelector('.btn-text');

    function setDarkMode(enabled) {
      if (enabled) {
        htmlEl.classList.add('dark');
        toggleBtn.setAttribute('aria-pressed', 'true');
        // Icono Sol
        toggleBtn.innerHTML = `
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span class="btn-text">Claro</span>
        `;
      } else {
        htmlEl.classList.remove('dark');
        toggleBtn.setAttribute('aria-pressed', 'false');
        // Icono Luna
        toggleBtn.innerHTML = `
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="btn-text">Oscuro</span>
        `;
      }
      localStorage.setItem('darkMode', JSON.stringify(enabled));
    }

    // Inicializa en base a localStorage
    let isDark = false;
    if (prefers !== null) {
      try { isDark = JSON.parse(prefers); } catch (e) { isDark = false; }
    }
    setDarkMode(isDark);

    toggleBtn.addEventListener('click', () => {
      isDark = !htmlEl.classList.contains('dark');
      setDarkMode(isDark);
    });

    // ===========================
    // Datos “mock” (equivalentes al currentUser del TSX)
    // Puedes cambiar role/textos si lo necesitas para la vista
    // ===========================
    const currentUser = {
      role: 'student', // Cambia a 'teacher' para profesor si gustas (y en <body> la clase a teacher-theme + teacher-gradient-bg)
      name: 'Ana García',
      email: 'ana.garcia@universidad.edu'
    };

    // Rellena avatar y textos con currentUser
    const nameEl = document.getElementById('userName');
    const emailEl = document.getElementById('userEmail');
    const avatarEl = document.getElementById('avatar');
    const roleSubtitle = document.getElementById('roleSubtitle');

    nameEl.textContent = currentUser.name;
    emailEl.textContent = currentUser.email;
    avatarEl.textContent = currentUser.name.split(' ').map(n => n[0]).join('').slice(0,3).toUpperCase();
    roleSubtitle.textContent = currentUser.role === 'student' ? 'Panel de Estudiante' : 'Panel de Profesor';
  </script>
</body>
</html>
