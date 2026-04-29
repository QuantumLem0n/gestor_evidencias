<?php
/**
 * Gestión de evidencias
 * - Requiere sesión (validacion.php) y permiso (validacion-permiso.php).
 * - Usa header/left-menu/footer.
 * - Carga tabla con AJAX desde vista-gestion-evidencias.php.
 * - Búsqueda en cliente + filtro por tipo de evidencia (servidor).
 * - Rol 4 (docentes): ven solo sus evidencias, pueden agregar y editar solo las suyas.
 */

include 'validacion.php';
include 'conexion.php';
include 'validacion-permiso.php';
include 'header.php';
include 'left-menu.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

// Tipos de evidencia (para filtro)
$tipos = [];
$qT = "SELECT id_tipo_evidencia, nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo ASC";
if ($rsT = mysqli_query($conn, $qT)) {
  while ($r = mysqli_fetch_assoc($rsT)) $tipos[] = $r;
}
?>
<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Gestión de evidencias</h1>
      <p class="text-muted-foreground">Administra las evidencias subidas por los docentes. Archivos PDF o imágenes (máx. 10 MB).</p>
    </div>

    <?php if (in_array($user_role, [1,4], true)): ?>
      <button class="btn btn-primary" id="openModalAgregarEvidencia" type="button" title="Agregar nueva evidencia">
        + Agregar Evidencia
      </button>
    <?php endif; ?>
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por título, docente o tipo…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>

      <!-- Filtro por tipo de evidencia (servidor) -->
      <div>
        <select id="filterTipo" class="input select-white" style="min-width:260px;">
          <option value="">Tipo: Todos</option>
          <?php foreach ($tipos as $t): ?>
            <option value="<?= (int)$t['id_tipo_evidencia'] ?>">
              <?= htmlspecialchars($t['nombre_tipo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaEvidencias" class="card">
    <div class="card-content">
      <p class="text-muted-foreground">Cargando…</p>
    </div>
  </div>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<!-- Modal Eliminar -->
<div class="dialog-backdrop" id="dlgBackdropEvi"></div>
<div class="dialog" id="dlgEliminarEvi" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarEviTitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarEviTitle">Eliminar evidencia</h3>
      <p class="dialog-desc">Se ocultará la evidencia (no se mostrará a usuarios).</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarEviText">¿Estás seguro de ocultar esta evidencia?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarEvi()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminarEvi()">Ocultar</button>
    </div>
  </div>
</div>

<?php
// Modales agregar / editar
if (file_exists('modal-agregar-evidencia.php')) include 'modal-agregar-evidencia.php';
if (file_exists('modal-editar-evidencia.php')) include 'modal-editar-evidencia.php';
?>

<script>
/* ==== Utilidades ==== */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};
const USER_ROLE = <?= (int)$user_role ?>;

/* ==== DataTable / Carga ==== */
const SEARCH_KEY = 'gestionEvidenciasSearch';
let dtE = null;
let currentTipo = '';

loadTableE();

document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('filterTipo');
  if (sel) {
    sel.addEventListener('change', function(){
      currentTipo = this.value || '';
      loadTableE();
    });
  }
});

function loadTableE(){
  const url = 'vista-gestion-evidencias.php?tipo=' + encodeURIComponent(currentTipo) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaEvidencias');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTE();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTE(){
  const $table = $('#tabla-evidencias');
  if (!$table.length) return;

  if (dtE) { dtE.destroy(); dtE = null; }

  dtE = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'desc']], // por ID desc
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ evidencias",
      infoFiltered: "(filtrado de _MAX_ evidencias)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtE.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtE.search(saved).draw();
    $search.on('input', function(){
      const val = this.value;
      dtE.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtE.search('').draw();
    });
  }
}

/* ==== Handlers globales ==== */

// Ver detalles
window.openVerDetalles = function(id){
  window.location.href = 'evidencia-detalle.php?id=' + encodeURIComponent(id);
};

// Editar (solo docentes o si decides permitir admin)
window.openEditarEvidencia = function(id){
  if (typeof window.openEditarEvidenciaModal === 'function') {
    window.openEditarEvidenciaModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para la evidencia '+id+'.'});
  }
};

// Eliminar (soft: ocultar=1)
let __deleteEviId = null;
let __deleteEviName = '';
window.openEliminarEvi = function(id, titulo){
  __deleteEviId = id;
  __deleteEviName = titulo || '';
  const dlg = document.getElementById('dlgEliminarEvi');
  const backdrop = document.getElementById('dlgBackdropEvi');
  const txt = document.getElementById('dlgEliminarEviText');

  if (txt) txt.innerHTML = '¿Ocultar la evidencia <strong>' + (__deleteEviName || ('#'+id)) + '</strong>?';
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminarEvi = function(){
  __deleteEviId = null;
  const dlg = document.getElementById('dlgEliminarEvi');
  const backdrop = document.getElementById('dlgBackdropEvi');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminarEvi = function(){
  if (!__deleteEviId) return;
  fetch('evidencia-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_evidencia: __deleteEviId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminarEvi();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Ocultado', text:'La evidencia se ocultó correctamente.'});
      loadTableE();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo ocultar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarEvi();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo ocultar la evidencia.'});
  });
};

// Cerrar modal eliminar tocando el backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdropEvi');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarEvi);
})();
</script>

<?php include 'footer.php'; ?>
