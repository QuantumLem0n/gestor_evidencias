<?php
/**
 * Gestión de Tipos de Evidencia
 * - Requiere sesión y permisos.
 * - Usa header/left-menu/footer.
 * - Carga tabla por AJAX desde vista-tipos-evidencia.php
 * - Búsqueda en cliente (input externo persistente).
 */

include 'validacion.php';
include 'conexion.php';
include 'validacion-permiso.php';
include 'header.php';
include 'left-menu.php';
?>
<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Tipos de evidencia</h1>
      <p class="text-muted-foreground">Administra los tipos y sus descripciones.</p>
    </div>

    <button class="btn btn-primary" id="openModalAgregarTipo" type="button" title="Agregar nuevo tipo">
      + Agregar Tipo
    </button>
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <!-- Búsqueda externa -->
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por nombre o descripción…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>

      <!-- (Hueco por si luego agregas filtros) -->
      <!-- Filtros por instrumento -->
      <div id="instFilters" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        <label style="display:flex; align-items:center; gap:6px;">
          <input type="checkbox" id="filterSNI"> <span>SNI</span>
        </label>
        <label style="display:flex; align-items:center; gap:6px;">
          <input type="checkbox" id="filterPRODEP"> <span>PRODEP</span>
        </label>
        <label style="display:flex; align-items:center; gap:6px;">
          <input type="checkbox" id="filterESDEPED"> <span>ESDEPED</span>
        </label>
      </div>
 
      <div></div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaGestionTipos" class="card">
    <div class="card-content">
      <p class="text-muted-foreground">Cargando…</p>
    </div>
  </div>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<!-- Modal Eliminar -->
<div class="dialog-backdrop" id="dlgBackdropTE"></div>
<div class="dialog" id="dlgEliminarTE" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarTETitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarTETitle">Eliminar tipo de evidencia</h3>
      <p class="dialog-desc">Esta acción no se puede deshacer.</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarTEText">¿Estás seguro de eliminar este tipo?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarTE()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminarTE()">Eliminar</button>
    </div>
  </div>
</div>

<?php
// Modales
if (file_exists('modal-agregar-tipo-evidencia.php')) include 'modal-agregar-tipo-evidencia.php';
if (file_exists('modal-editar-tipo-evidencia.php')) include 'modal-editar-tipo-evidencia.php';
?>
<script>
/* ========= Utilidades ========= */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};

/* ========= DataTable / Carga ========= */
const SEARCH_KEY = 'gestionTiposEvidenciaSearch';
const FILTERS_KEY = 'gestionTiposEvidenciaFilters'; // NUEVO (persistir filtros)
let dtTE = null;

// Filtro DataTables (instalado una sola vez)
if (!window.__teFilterInstalled) {
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
    // Aplica SOLO a esta tabla
    if (!dtTE || settings.nTable !== dtTE.table().node()) return true;

    const node = dtTE.row(dataIndex).node();
    if (!node) return true;

    const wantSNI = $('#filterSNI').prop('checked');
    const wantPRODEP = $('#filterPRODEP').prop('checked');
    const wantESDEPED = $('#filterESDEPED').prop('checked');

    const rowSNI = node.getAttribute('data-sni') === '1';
    const rowPRODEP = node.getAttribute('data-prodep') === '1';
    const rowESDEPED = node.getAttribute('data-esdeped') === '1';

    // Regla: si un filtro está marcado, la fila debe cumplirlo.
    if (wantSNI && !rowSNI) return false;
    if (wantPRODEP && !rowPRODEP) return false;
    if (wantESDEPED && !rowESDEPED) return false;

    return true;
  });
  window.__teFilterInstalled = true;
}

// Carga inicial
loadTableTE();

function loadTableTE(){
  const url = 'vista-tipos-evidencia.php?r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaGestionTipos');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTTE(); // inicializa/rehace DataTable
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTTE(){
  const $table = $('#tabla-tipos-evidencia');
  if (!$table.length) return;

  if (dtTE) { dtTE.destroy(); dtTE = null; }

  dtTE = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [
      { className: 'control', orderable: false, targets: 0 },
      { orderable: false, targets: 5 } // Acciones (última)
    ],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'asc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ tipos",
      infoFiltered: "(filtrado de _MAX_ tipos)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // === Búsqueda externa (ya existente) ===
  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtTE.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtTE.search(saved).draw();
    $search.on('input', function(){
      const val = this.value;
      dtTE.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtTE.search('').draw();
    });
  }

  // === Filtros por instrumento (NUEVO) ===
  restoreInstrumentFilters();    // setea checkboxes según localStorage
  bindInstrumentFiltersEvents(); // escucha cambios y redibuja
  dtTE.draw(); // aplica filtros actuales
}

// ----- NUEVO: persistencia de filtros -----
function getFiltersState(){
  return {
    sni: !!document.getElementById('filterSNI')?.checked,
    prodep: !!document.getElementById('filterPRODEP')?.checked,
    esdeped: !!document.getElementById('filterESDEPED')?.checked
  };
}
function setFiltersState(st){
  const sni = document.getElementById('filterSNI');
  const prodep = document.getElementById('filterPRODEP');
  const esdeped = document.getElementById('filterESDEPED');
  if (sni && typeof st.sni === 'boolean') sni.checked = st.sni;
  if (prodep && typeof st.prodep === 'boolean') prodep.checked = st.prodep;
  if (esdeped && typeof st.esdeped === 'boolean') esdeped.checked = st.esdeped;
}
function restoreInstrumentFilters(){
  try {
    const raw = localStorage.getItem(FILTERS_KEY);
    if (raw) setFiltersState(JSON.parse(raw));
  } catch(e){}
}
function saveInstrumentFilters(){
  try { localStorage.setItem(FILTERS_KEY, JSON.stringify(getFiltersState())); } catch(e){}
}
function bindInstrumentFiltersEvents(){
  ['filterSNI','filterPRODEP','filterESDEPED'].forEach(id=>{
    const el = document.getElementById(id);
    if (el) {
      el.onchange = function(){
        saveInstrumentFilters();
        if (dtTE) dtTE.draw();
      };
    }
  });
}

/* ========= Handlers ========= */

// Ir a atributos/lista del tipo
window.openAtributosTipo = function(id){
  window.location.href = 'atributos-tipo.php?id_tipo=' + encodeURIComponent(id);
};

// Editar
window.openEditarTipo = function(id){
  if (typeof window.openEditarTipoModal === 'function') {
    window.openEditarTipoModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para el tipo '+id+'.'});
  }
};

// Eliminar
let __deleteTipoId = null;
window.openEliminarTE = function(id, name){
  __deleteTipoId = id;
  const dlg = document.getElementById('dlgEliminarTE');
  const backdrop = document.getElementById('dlgBackdropTE');
  const txt = document.getElementById('dlgEliminarTEText');

  if (txt) txt.innerHTML = '¿Estás seguro de eliminar <strong>' + (name || ('tipo #' + id)) + '</strong>?';
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminarTE = function(){
  __deleteTipoId = null;
  const dlg = document.getElementById('dlgEliminarTE');
  const backdrop = document.getElementById('dlgBackdropTE');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminarTE = function(){
  if (!__deleteTipoId) return;
  fetch('tipo-evidencia-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_tipo_evidencia: __deleteTipoId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminarTE();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Eliminado', text:'El tipo se eliminó correctamente.'});
      loadTableTE();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo eliminar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarTE();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo eliminar el tipo.'});
  });
};

// Cerrar modal eliminar tocando backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdropTE');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarTE);
})();
</script>


<?php include 'footer.php'; ?>
