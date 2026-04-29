<?php
  // Forzamos modo Estudiante para esta vista
  $roleClass = 'student';
  $currentUser = ['name' => 'Ana García', 'email' => 'ana.garcia@universidad.edu'];
  include 'header.php';
  include 'left-menu.php';
?>

<div class="space-y-6">
  <!-- ====== Student Stats ====== -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Carrera -->
    <div class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center" style="gap:8px;">
          <!-- BookOpen -->
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:20px;height:20px;color:var(--student-primary);">
            <path d="M2 7a4 4 0 0 1 4-4h6v18H6a4 4 0 0 0-4 4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M22 7a4 4 0 0 0-4-4h-6v18h6a4 4 0 0 1 4 4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          </svg>
          <span>Carrera</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="st-career" class="font-medium"></p>
        <p id="st-semester" class="text-sm text-muted-foreground"></p>
      </div>
    </div>

    <!-- Materias activas -->
    <div class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center" style="gap:8px;">
          <!-- Users -->
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:20px;height:20px;color:#10b981;">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span>Materias Activas</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="st-active-count" class="text-2xl font-semibold">0</p>
        <p class="text-sm text-muted-foreground">Este semestre</p>
      </div>
    </div>

    <!-- Créditos -->
    <div class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center" style="gap:8px;">
          <!-- Calendar -->
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:20px;height:20px;color:#7c3aed;">
            <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <path d="M16 3v4M8 3v4M3 11h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span>Créditos</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="st-credits" class="text-2xl font-semibold">0</p>
        <p class="text-sm text-muted-foreground">Total registrados</p>
      </div>
    </div>
  </div>

  <!-- ====== Tabs ====== -->
  <div id="studentTabs" class="tabs space-y-4">
    <div class="tabs-list">
      <button class="tabs-trigger" data-tab="overview" data-active="true">Resumen</button>
      <button class="tabs-trigger" data-tab="schedule" data-active="false">Horario Completo</button>
      <button class="tabs-trigger" data-tab="classes" data-active="false">Mis Materias</button>
    </div>

    <!-- ====== OVERVIEW ====== -->
    <div id="tab-overview" class="tabs-content space-y-6" data-active="true">
      <div class="card shadow-lg border-2 student-border-light">
        <div class="card-header" style="background: color-mix(in srgb, var(--student-secondary) 50%, transparent); border-bottom: 2px solid var(--student-border-light);">
          <h3 class="flex items-center" style="gap:8px; color: var(--student-primary);">
            <!-- Clock -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:20px;height:20px;">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
              <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>Clases de Hoy</span>
          </h3>
          <p class="card-description" id="st-today-date"></p>
        </div>
        <div class="card-content">
          <div id="st-today-list" class="space-y-4"></div>
          <p id="st-today-empty" class="text-center text-muted-foreground" style="display:none; padding: 32px 0;">
            No tienes clases programadas para hoy.
          </p>
        </div>
      </div>
    </div>

    <!-- ====== SCHEDULE ====== -->
    <div id="tab-schedule" class="tabs-content" data-active="false">
      <div class="card shadow-lg border-2 student-border-light">
        <div class="card-header" style="background: color-mix(in srgb, var(--student-secondary) 50%, transparent); border-bottom: 2px solid var(--student-border-light);">
          <h3 class="text-student-primary">Horario Semanal</h3>
          <p class="card-description">Tu horario completo de clases para esta semana</p>
        </div>
        <div class="card-content">
          <table class="table" id="st-schedule-table">
            <thead>
              <tr>
                <th>Día</th>
                <th>Horario</th>
                <th>Materia</th>
                <th>Profesor</th>
                <th>Lugar</th>
                <th>Modalidad</th>
              </tr>
            </thead>
            <tbody><!-- render dinámico --></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== CLASSES (cards) ====== -->
    <div id="tab-classes" class="tabs-content" data-active="false">
      <div id="st-classes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </div>
  </div>
</div>

<script>
(function(){
  // ===== Mock data (equivalente a mockStudentData) =====
  const data = {
    schedule: [
      { id:'1', subject:'Cálculo Diferencial', professor:'Dr. María López', time:'08:00 - 10:00', day:'Lunes', classroom:'Aula 201', modality:'Presencial', career:'Ingeniería en Sistemas' },
      { id:'2', subject:'Programación I',      professor:'Ing. Carlos Ruiz', time:'10:30 - 12:30', day:'Lunes', classroom:'Lab 105',  modality:'Presencial', career:'Ingeniería en Sistemas' },
      { id:'3', subject:'Inglés Técnico',      professor:'Lic. Ana Torres',  time:'14:00 - 15:30', day:'Martes', classroom:'Virtual',  modality:'En línea',   career:'Ingeniería en Sistemas' },
      { id:'4', subject:'Física I',            professor:'Dr. Roberto Silva',time:'08:00 - 10:00', day:'Miércoles', classroom:'Aula 303', modality:'Presencial', career:'Ingeniería en Sistemas' },
      { id:'5', subject:'Álgebra Lineal',      professor:'Dra. Patricia Hernández', time:'10:30 - 12:30', day:'Jueves', classroom:'Aula 205', modality:'Presencial', career:'Ingeniería en Sistemas' }
    ],
    todayClasses: [
      { id:'1', subject:'Cálculo Diferencial', professor:'Dr. María López', time:'08:00 - 10:00', classroom:'Aula 201', modality:'Presencial', status:'En curso' },
      { id:'2', subject:'Programación I',      professor:'Ing. Carlos Ruiz', time:'10:30 - 12:30', classroom:'Lab 105',  modality:'Presencial', status:'Próxima' }
    ],
    career: 'Ingeniería en Sistemas',
    semester: '3er Semestre',
    totalCredits: 22
  };

  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  // Estadísticos
  $('#st-career').textContent = data.career;
  $('#st-semester').textContent = data.semester;
  $('#st-active-count').textContent = String(data.schedule.length);
  $('#st-credits').textContent = String(data.totalCredits);

  // Fecha “hoy”
  $('#st-today-date').textContent = new Date().toLocaleDateString('es-ES', {
    weekday:'long', year:'numeric', month:'long', day:'numeric'
  });

  // ===== Badges helper =====
  function badge(label, variant='default'){
    const cls = variant === 'secondary' ? 'secondary' : 'default';
    return `<span class="badge ${cls}">${label}</span>`;
  }

  // ===== OVERVIEW → clases de hoy =====
  const todayList = $('#st-today-list');
  const todayEmpty = $('#st-today-empty');

  if (data.todayClasses.length === 0) {
    todayEmpty.style.display = 'block';
  } else {
    data.todayClasses.forEach((cl) => {
      const isPresencial = cl.modality === 'Presencial';
      const row = document.createElement('div');
      row.className = 'flex items-center justify-between';
      row.style.padding = '16px';
      row.style.border = '2px solid var(--student-border-light)';
      row.style.borderRadius = '12px';
      row.style.transition = 'background-color .2s';
      row.addEventListener('mouseenter',()=> row.style.background = 'color-mix(in srgb, var(--student-secondary) 30%, transparent)');
      row.addEventListener('mouseleave',()=> row.style.background = 'transparent');

      row.innerHTML = `
        <div class="space-y-1">
          <h4 class="font-medium" style="color: var(--student-primary);">${cl.subject}</h4>
          <div class="flex items-center space-x-4" style="color: var(--muted-foreground); font-size: 14px;">
            <span class="flex items-center" style="gap:6px;">
              <!-- Clock -->
              <svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
              <span>${cl.time}</span>
            </span>
            <span class="flex items-center" style="gap:6px;">
              ${isPresencial
                ? `<svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;"><path d="M3 21V8l9-6 9 6v13" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg><span>${cl.classroom}</span>`
                : `<svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 21h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span>En línea</span>`
              }
            </span>
          </div>
          <p class="text-sm text-muted-foreground">${cl.professor}</p>
        </div>
        ${badge(cl.status, cl.status === 'En curso' ? 'default' : 'secondary')}
      `;
      todayList.appendChild(row);
    });
  }

  // ===== Tabs =====
  const tabsRoot = $('#studentTabs');
  const triggers = $$('.tabs-trigger', tabsRoot);
  const contents = $$('.tabs-content', tabsRoot);
  function setTab(tab) {
    triggers.forEach(t => t.setAttribute('data-active', t.getAttribute('data-tab') === tab ? 'true' : 'false'));
    contents.forEach(c => c.setAttribute('data-active', c.id === 'tab-' + tab ? 'true' : 'false'));
  }
  triggers.forEach(t => t.addEventListener('click', () => setTab(t.getAttribute('data-tab'))));

  // ===== SCHEDULE (tabla) =====
  const tbody = $('#st-schedule-table tbody');
  function renderSchedule(){
    tbody.innerHTML = '';
    data.schedule.forEach(cl=>{
      const tr = document.createElement('tr');
      const isPresencial = cl.modality === 'Presencial';
      tr.innerHTML = `
        <td class="font-medium">${cl.day}</td>
        <td>${cl.time}</td>
        <td>${cl.subject}</td>
        <td>${cl.professor}</td>
        <td>
          <span class="flex items-center" style="gap:6px;">
            ${isPresencial
              ? `<svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;"><path d="M3 21V8l9-6 9 6v13" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>`
              : `<svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 21h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`
            }
            <span>${cl.classroom}</span>
          </span>
        </td>
        <td>${badge(cl.modality, cl.modality === 'Presencial' ? 'default' : 'secondary')}</td>
      `;
      tbody.appendChild(tr);
    });
  }
  renderSchedule();

  // ===== CLASSES (cards) =====
  const classesGrid = $('#st-classes-grid');
  function classCard(cl){
    const isPresencial = cl.modality === 'Presencial';
    const headBg = 'linear-gradient(90deg, color-mix(in srgb, var(--student-secondary) 30%, transparent), color-mix(in srgb, var(--student-accent) 20%, transparent))';
    const el = document.createElement('div');
    el.className = 'card border-2 student-border-light hover:shadow-lg hover:border-student-border';
    el.innerHTML = `
      <div class="card-header" style="background:${headBg};">
        <h4 class="text-lg" style="color: var(--student-primary);">${cl.subject}</h4>
        <p class="card-description" style="margin-top:4px;">${cl.professor}</p>
      </div>
      <div class="card-content">
        <div class="space-y-2" style="font-size:14px;">
          <div class="flex items-center" style="gap:8px; color: var(--muted-foreground);">
            <!-- Calendar -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;">
              <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/>
              <path d="M16 3v4M8 3v4M3 11h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>${cl.day} • ${cl.time}</span>
          </div>
          <div class="flex items-center" style="gap:8px; color: var(--muted-foreground);">
            ${isPresencial
              ? `<svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;"><path d="M3 21V8l9-6 9 6v13" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>`
              : `<svg class="icon" viewBox="0 0 24 24" fill="none" style="width:16px;height:16px;"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 21h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`}
            <span>${cl.classroom}</span>
          </div>
          ${badge(cl.modality, cl.modality === 'Presencial' ? 'default' : 'secondary')}
        </div>
      </div>
    `;
    return el;
  }
  function renderClasses(){
    classesGrid.innerHTML = '';
    data.schedule.forEach(cl => classesGrid.appendChild(classCard(cl)));
  }
  renderClasses();
})();
</script>

<?php include 'footer.php'; ?>
