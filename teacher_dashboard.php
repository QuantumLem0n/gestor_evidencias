<?php
  // Forzamos modo Profesor para esta vista
  $roleClass = 'teacher';
  $currentUser = ['name' => 'Dr. Carlos Mendoza', 'email' => 'carlos.mendoza@universidad.edu'];
  include 'header.php';
  include 'left-menu.php';
?>

<div class="space-y-6">
  <!-- ====== Teacher Stats ====== -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Materias -->
    <div class="card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center">
          <!-- BookOpen -->
          <svg class="icon icon-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-right:8px; color: var(--teacher-primary);">
            <path d="M2 7a4 4 0 0 1 4-4h6v18H6a4 4 0 0 0-4 4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M22 7a4 4 0 0 0-4-4h-6v18h6a4 4 0 0 1 4 4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          </svg>
          <span>Materias</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="stat-classes" class="text-2xl font-semibold">0</p>
        <p class="text-sm text-muted-foreground">Activas este semestre</p>
      </div>
    </div>

    <!-- Estudiantes -->
    <div class="card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center">
          <!-- Users -->
          <svg class="icon icon-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-right:8px; color:#10b981;">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span>Estudiantes</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="stat-students" class="text-2xl font-semibold">0</p>
        <p class="text-sm text-muted-foreground">Total inscritos</p>
      </div>
    </div>

    <!-- Carreras -->
    <div class="card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center">
          <!-- GraduationCap -->
          <svg class="icon icon-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-right:8px; color:#7c3aed;">
            <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Carreras</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="stat-careers" class="text-2xl font-semibold">0</p>
        <p class="text-sm text-muted-foreground">Que enseño</p>
      </div>
    </div>

    <!-- Hoy -->
    <div class="card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold flex items-center">
          <!-- Clock -->
          <svg class="icon icon-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-right:8px; color:#d97706;">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span>Hoy</span>
        </h3>
      </div>
      <div class="card-content">
        <p id="stat-today" class="text-2xl font-semibold">0</p>
        <p class="text-sm text-muted-foreground">Clases programadas</p>
      </div>
    </div>
  </div>

  <!-- ====== Tabs ====== -->
  <div id="teacherTabs" class="tabs space-y-4">
    <div class="tabs-list">
      <button class="tabs-trigger" data-tab="overview" data-active="true">Resumen</button>
      <button class="tabs-trigger" data-tab="classes" data-active="false">Mis Materias</button>
      <button class="tabs-trigger" data-tab="careers" data-active="false">Carreras</button>
      <button class="tabs-trigger" data-tab="schedule" data-active="false">Horario</button>
    </div>

    <!-- ====== OVERVIEW ====== -->
    <div id="tab-overview" class="tabs-content space-y-6" data-active="true">
      <div class="card shadow-lg border-2 teacher-border-light">
        <div class="card-header card-header-bar">
          <h3 class="flex items-center text-teacher-primary" style="gap:8px;">
            <!-- Clock -->
            <svg class="icon icon-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
              <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>Clases de Hoy</span>
          </h3>
          <p class="card-description" id="todayDate"></p>
        </div>
        <div class="card-content">
          <div id="todayList" class="space-y-4"></div>
        </div>
      </div>
    </div>

    <!-- ====== CLASSES ====== -->
    <div id="tab-classes" class="tabs-content space-y-6" data-active="false">
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-semibold">Mis Materias</h2>
        <button id="openAddClass" class="btn flex items-center">
          <!-- Plus -->
          <svg class="icon icon-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-right:6px;">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span>Nueva Materia</span>
        </button>
      </div>

      <div id="classesGrid" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
    </div>

    <!-- ====== CAREERS ====== -->
    <div id="tab-careers" class="tabs-content" data-active="false">
      <div class="card shadow-lg border-2 teacher-border-light">
        <div class="card-header card-header-bar">
          <h3 class="text-teacher-primary">Carreras que Enseño</h3>
          <p class="card-description">Programas académicos donde imparto materias</p>
        </div>
        <div class="card-content">
          <div id="careersGrid" class="grid grid-cols-1 md:grid-cols-2 grid-cols-3 gap-4"></div>
        </div>
      </div>
    </div>

    <!-- ====== SCHEDULE ====== -->
    <div id="tab-schedule" class="tabs-content" data-active="false">
      <div class="card shadow-lg border-2 teacher-border-light">
        <div class="card-header card-header-bar">
          <h3 class="text-teacher-primary">Horario Semanal</h3>
          <p class="card-description">Tu horario completo de clases</p>
        </div>
        <div class="card-content">
          <div class="table-responsive">
            <table class="table" id="scheduleTable">
              <thead>
                <tr>
                  <th>Materia</th>
                  <th>Carrera</th>
                  <th>Horario</th>
                  <th>Lugar</th>
                  <th>Estudiantes</th>
                  <th>Modalidad</th>
                </tr>
              </thead>
              <tbody><!-- render dinámico --></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ====== DIALOG: Nueva Materia ====== -->
<div class="dialog-backdrop" id="dialogBackdrop" data-open="false"></div>
<div class="dialog" id="dialog" data-open="false" aria-hidden="true">
  <div class="dialog-card">
    <div class="dialog-header">
      <h4 class="dialog-title">Agregar Nueva Materia</h4>
      <p class="dialog-desc">Completa la información de la nueva materia que vas a impartir.</p>
    </div>

    <form id="addClassForm" class="space-y-4">
      <div class="form-group">
        <label class="label" for="ac-name">Nombre de la Materia</label>
        <input class="input" id="ac-name" placeholder="Ej: Cálculo Diferencial" required>
      </div>

      <div class="form-group">
        <label class="label" for="ac-career">Carrera</label>
        <select class="input" id="ac-career" required>
          <!-- opciones dinámicas -->
        </select>
      </div>

      <div class="form-group">
        <label class="label" for="ac-semester">Semestre</label>
        <input class="input" id="ac-semester" placeholder="Ej: 1er Semestre" required>
      </div>

      <div class="form-group">
        <label class="label" for="ac-schedule">Horario</label>
        <input class="input" id="ac-schedule" placeholder="Ej: Lunes 08:00-10:00" required>
      </div>

      <div class="form-group">
        <label class="label" for="ac-classroom">Aula/Salón</label>
        <input class="input" id="ac-classroom" placeholder="Ej: Aula 201 o Virtual" required>
      </div>

      <div class="form-group">
        <label class="label" for="ac-modality">Modalidad</label>
        <select class="input" id="ac-modality" required>
          <option value="Presencial">Presencial</option>
          <option value="En línea">En línea</option>
        </select>
      </div>

      <div class="dialog-actions">
        <button type="button" class="btn" id="cancelAddClass">Cancelar</button>
        <button type="submit" class="btn btn-primary">Agregar Materia</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  // ===== Mock data (equivalente a mockTeacherData) =====
  const data = {
    careers: ['Ingeniería en Sistemas', 'Ingeniería Industrial', 'Administración de Empresas'],
    classes: [
      { id:'1', name:'Cálculo Diferencial', career:'Ingeniería en Sistemas', semester:'1er Semestre', students:34, schedule:'Lunes 08:00-10:00', classroom:'Aula 201', modality:'Presencial' },
      { id:'2', name:'Cálculo Integral', career:'Ingeniería en Sistemas', semester:'2do Semestre', students:24, schedule:'Martes 10:30-12:30', classroom:'Aula 201', modality:'Presencial' },
      { id:'3', name:'Matemáticas Aplicadas', career:'Ingeniería Industrial', semester:'3er Semestre', students:32, schedule:'Miércoles 14:00-16:00', classroom:'Virtual', modality:'En línea' },
      { id:'4', name:'Estadística', career:'Administración de Empresas', semester:'4to Semestre', students:35, schedule:'Jueves 08:00-10:00', classroom:'Aula 105', modality:'Presencial' }
    ],
    upcomingClasses: [
      { id:'1', name:'Cálculo Diferencial', time:'08:00 - 10:00', students:28, classroom:'Aula 201', modality:'Presencial' },
      { id:'2', name:'Cálculo Integral', time:'10:30 - 12:30', students:24, classroom:'Aula 201', modality:'Presencial' }
    ],
    totalStudents: 119,
    totalClasses: 4
  };

  // ===== Helpers de UI =====
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  // Fecha “hoy” en español
  $('#todayDate').textContent = new Date().toLocaleDateString('es-ES', {
    weekday:'long', year:'numeric', month:'long', day:'numeric'
  });

  // Estadísticas
  $('#stat-classes').textContent = String(data.classes.length);
  $('#stat-students').textContent = String(data.totalStudents);
  $('#stat-careers').textContent = String(data.careers.length);
  $('#stat-today').textContent = String(data.upcomingClasses.length);

  // ===== Render Overview (Clases de hoy) =====
  const todayList = $('#todayList');
  data.upcomingClasses.forEach((cl) => {
    const isPresencial = cl.modality === 'Presencial';
    const item = document.createElement('div');
    item.className = 'flex items-center justify-between';
    item.style.padding = '16px';
    item.style.border = '2px solid var(--teacher-border-light)';
    item.style.borderRadius = '12px';
    item.style.transition = 'background-color .2s';
    item.addEventListener('mouseenter',()=> item.style.background = 'color-mix(in srgb, var(--teacher-secondary) 30%, transparent)');
    item.addEventListener('mouseleave',()=> item.style.background = 'transparent');

    item.innerHTML = `
      <div class="space-y-1">
        <h4 class="font-medium text-teacher-primary">${cl.name}</h4>
        <div class="flex items-center space-x-4" style="color: var(--muted-foreground); font-size: 14px;">
          <span class="flex items-center" style="gap:6px;">
            <svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>${cl.time}</span>
          </span>
          <span class="flex items-center" style="gap:6px;">
            ${isPresencial
              ? `<svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><path d="M3 21V8l9-6 9 6v13" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg><span>${cl.classroom}</span>`
              : `<svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 21h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span>En línea</span>`
            }
          </span>
          <span class="flex items-center" style="gap:6px;">
            <svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>${cl.students} estudiantes</span>
          </span>
        </div>
      </div>
      <button class="btn" type="button">Acceder</button>
    `;
    todayList.appendChild(item);
  });

  // ===== Tabs =====
  const tabsRoot = $('#teacherTabs');
  const triggers = $$('.tabs-trigger', tabsRoot);
  const contents = $$('.tabs-content', tabsRoot);
  function setTab(tab) {
    triggers.forEach(t => t.setAttribute('data-active', t.getAttribute('data-tab') === tab ? 'true' : 'false'));
    contents.forEach(c => c.setAttribute('data-active', c.id === 'tab-' + tab ? 'true' : 'false'));
  }
  triggers.forEach(t => t.addEventListener('click', () => setTab(t.getAttribute('data-tab'))));

  // ===== Render Mis Materias =====
  const classesGrid = $('#classesGrid');
  function badge(modality){
    const cls = modality === 'Presencial' ? 'default' : 'secondary';
    return `<span class="badge ${cls}">${modality}</span>`;
  }
  function classCard(c){
    const isPresencial = c.modality === 'Presencial';
    const headBg = 'linear-gradient(90deg, color-mix(in srgb, var(--teacher-secondary) 30%, transparent), color-mix(in srgb, var(--teacher-accent) 20%, transparent))';
    const el = document.createElement('div');
    el.className = 'card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border';
    el.innerHTML = `
      <div class="card-header" style="background:${headBg};">
        <h4 class="text-lg text-teacher-primary">${c.name}</h4>
        <p class="card-description" style="margin-top:4px;">${c.career} • ${c.semester}</p>
      </div>
      <div class="card-content">
        <div class="space-y-2">
          <div class="flex items-center" style="gap:8px; font-size:14px; color: var(--muted-foreground);">
            <!-- Calendar -->
            <svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 3v4M8 3v4M3 11h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>${c.schedule}</span>
          </div>
          <div class="flex items-center" style="gap:8px; font-size:14px; color: var(--muted-foreground);">
            ${isPresencial
              ? `<svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><path d="M3 21V8l9-6 9 6v13" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>`
              : `<svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 21h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`}
            <span>${c.classroom}</span>
          </div>
          <div class="flex items-center" style="gap:8px; font-size:14px; color: var(--muted-foreground);">
            <!-- Users -->
            <svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>${c.students} estudiantes inscritos</span>
          </div>
          <div>${badge(c.modality)}</div>
          <button class="btn" style="width:100%;" type="button">Ver Detalles</button>
        </div>
      </div>
    `;
    return el;
  }
  function renderClasses(){
    classesGrid.innerHTML = '';
    data.classes.forEach(c => classesGrid.appendChild(classCard(c)));
  }
  renderClasses();

  // ===== Render Careers =====
  const careersGrid = $('#careersGrid');
  function careerCard(name){
    const count = data.classes.filter(x => x.career === name).length;
    const students = data.classes.filter(x => x.career === name).reduce((s,x)=>s+x.students,0);
    const card = document.createElement('div');
    card.className = 'card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border';
    card.innerHTML = `
      <div class="card-header" style="padding-bottom:12px;">
        <h4 class="text-lg flex items-center" style="gap:8px;">
          <!-- GraduationCap -->
          <svg class="icon icon-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="color: var(--teacher-primary);">
            <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="text-teacher-primary">${name}</span>
        </h4>
      </div>
      <div class="card-content">
        <div class="space-y-2" style="font-size:14px; color: var(--muted-foreground);">
          <p>Materias: ${count}</p>
          <p>Estudiantes: ${students}</p>
        </div>
      </div>
    `;
    return card;
  }
  function renderCareers(){
    careersGrid.innerHTML = '';
    data.careers.forEach(c => careersGrid.appendChild(careerCard(c)));
  }
  renderCareers();

  // ===== Render Schedule (tabla) =====
  const tbody = $('#scheduleTable tbody');
  function renderSchedule(){
    tbody.innerHTML = '';
    data.classes.forEach(c=>{
      const tr = document.createElement('tr');
      const isPresencial = c.modality === 'Presencial';
      tr.innerHTML = `
        <td class="font-medium">${c.name}</td>
        <td>${c.career}</td>
        <td>${c.schedule}</td>
        <td>
          <span class="flex items-center" style="gap:6px;">
            ${isPresencial
              ? `<svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><path d="M3 21V8l9-6 9 6v13" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>`
              : `<svg class="icon icon-4" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 21h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`
            }
            <span>${c.classroom}</span>
          </span>
        </td>
        <td>${c.students}</td>
        <td>${badge(c.modality)}</td>
      `;
      tbody.appendChild(tr);
    });
  }
  renderSchedule();

  // ===== Diálogo Nueva Materia =====
  const dialog = $('#dialog');
  const backdrop = $('#dialogBackdrop');
  const openBtn = $('#openAddClass');
  const cancelBtn = $('#cancelAddClass');
  const form = $('#addClassForm');
  const selectCareer = $('#ac-career');

  function openDialog() {
    dialog.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    dialog.setAttribute('aria-hidden', 'false');
  }
  function closeDialog() {
    dialog.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    dialog.setAttribute('aria-hidden', 'true');
  }

  // Opciones de carrera
  function fillCareers(){
    selectCareer.innerHTML = '<option value="" disabled selected>Selecciona una carrera</option>';
    data.careers.forEach(c=>{
      const opt = document.createElement('option');
      opt.value = c; opt.textContent = c;
      selectCareer.appendChild(opt);
    });
  }
  fillCareers();

  openBtn.addEventListener('click', openDialog);
  cancelBtn.addEventListener('click', closeDialog);
  backdrop.addEventListener('click', closeDialog);
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') closeDialog(); });

  form.addEventListener('submit', function(e){
    e.preventDefault();
    const newClass = {
      id: String(data.classes.length + 1),
      name: $('#ac-name').value.trim(),
      career: $('#ac-career').value,
      semester: $('#ac-semester').value.trim(),
      schedule: $('#ac-schedule').value.trim(),
      classroom: $('#ac-classroom').value.trim(),
      modality: $('#ac-modality').value,
      students: Math.floor(Math.random()*30) + 20
    };
    if (!newClass.name || !newClass.career || !newClass.semester || !newClass.schedule || !newClass.classroom) return;

    data.classes.push(newClass);
    // Update stats / UI
    $('#stat-classes').textContent = String(data.classes.length);
    renderClasses();
    renderCareers();
    renderSchedule();
    closeDialog();
    form.reset();
  });

})();
</script>

<?php include 'footer.php'; ?>
