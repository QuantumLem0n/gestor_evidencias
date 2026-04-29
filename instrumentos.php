<?php
/**
 * Gestión de Instrumentos (Evaluación)
 * - Solo edición de la forma de calificar (Aprobación vs Numérica + rango).
 * - NO se pueden agregar/eliminar ni cambiar abreviatura/nombre.
 * - Carga la tabla por AJAX desde vista-instrumentos.php
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
      <h1 class="text-2xl font-semibold">Evaluación → Instrumentos</h1>
      <p class="text-muted-foreground">Configura si cada instrumento se califica por aprobación (0/1) o de forma numérica (con rango).</p>
    </div>
    <!-- No hay botón de agregar -->
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="ins_search" class="input" placeholder="Buscar por abreviatura o nombre…" aria-label="Buscar" />
        <button id="ins_clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>
      <!-- Filtro rápido por tipo -->
      <div style="display:flex; gap:12px; align-items:center;">
        <label style="display:flex; align-items:center; gap:6px;">
          <input type="checkbox" id="flt_aprobacion"> <span>Aprobación (0/1)</span>
        </label>
        <label style="display:flex; align-items:center; gap:6px;">
          <input type="checkbox" id="flt_numerica"> <span>Numérica</span>
        </label>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaInstrumentos" class="card">
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

<?php
// Modal edición
if (file_exists('modal-editar-instrumento.php')) include 'modal-editar-instrumento.php';
?>

<script>
/* ========= Utilidades ========= */
window.lockPageScroll = function(lock){
  document.documentElement.style.overflow = lock ? 'hidden' : '';
};

/* ========= DataTable / Carga ========= */
const INS_SEARCH_KEY  = 'gestionInstrumentosSearch';
const INS_FILTERS_KEY = 'gestionInstrumentosFilters';

let dtINS = null;

// Filtro DataTables (instalado una sola vez)
if (!window.__insFilterInstalled) {
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
    if (!dtINS || settings.nTable !== dtINS.table().node()) return true;

    const node = dtINS.row(dataIndex).node();
    if (!node) return true;

    const wantAp = $('#flt_aprobacion').prop('checked');
    const wantNum = $('#flt_numerica').prop('checked');

    const tipo = node.getAttribute('data-tipo') || ''; // 'APROBACION' | 'NUMERICA'

    if (wantAp && tipo !== 'APROBACION') return false;
    if (wantNum && tipo !== 'NUMERICA') return false;
    // Si ninguno marcado, pasa todo.
    return true;
  });
  window.__insFilterInstalled = true;
}

// Carga inicial
loadTableINS();

function loadTableINS(){
  const url = 'vista-instrumentos.php?r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaInstrumentos');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTINS();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTINS(){
  const $table = $('#tabla-instrumentos');
  if (!$table.length) return;

  if (dtINS) { dtINS.destroy(); dtINS = null; }

  dtINS = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [
      { className: 'control', orderable: false, targets: 0 },
      { orderable: false, targets: 5 } // Acciones
    ],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50],
    stateSave: true,
    order: [[1, 'asc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ instrumentos",
      infoFiltered: "(filtrado de _MAX_ instrumentos)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // Búsqueda externa
  const $search = $('#ins_search');
  const $clear  = $('#ins_clearSearch');

  if ($search.length) {
    const saved = dtINS.state()?.search?.search || localStorage.getItem(INS_SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtINS.search(saved).draw();
    $search.on('input', function(){
      const val = this.value;
      dtINS.search(val).draw();
      localStorage.setItem(INS_SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      if ($search.length) { $search.val(''); localStorage.setItem(INS_SEARCH_KEY, ''); }
      dtINS.search('').draw();
    });
  }

  // Filtros por tipo
  restoreInsFilters();
  ['#flt_aprobacion','#flt_numerica'].forEach(sel=>{
    const el = document.querySelector(sel);
    if (el) el.onchange = function(){
      saveInsFilters();
      dtINS.draw();
    };
  });
  dtINS.draw();
}

function restoreInsFilters(){
  try{
    const raw = localStorage.getItem(INS_FILTERS_KEY);
    if (!raw) return;
    const st = JSON.parse(raw);
    const ap  = document.getElementById('flt_aprobacion');
    const num = document.getElementById('flt_numerica');
    if (ap && typeof st.aprobacion === 'boolean') ap.checked = st.aprobacion;
    if (num && typeof st.numerica === 'boolean')  num.checked = st.numerica;
  } catch(e){}
}
function saveInsFilters(){
  const st = {
    aprobacion: !!document.getElementById('flt_aprobacion')?.checked,
    numerica:   !!document.getElementById('flt_numerica')?.checked,
  };
  try{ localStorage.setItem(INS_FILTERS_KEY, JSON.stringify(st)); }catch(e){}
}

/* ========= Handlers ========= */
window.openEditarInstrumento = function(id){
  if (typeof window.openEditarInstrumentoModal === 'function') {
    window.openEditarInstrumentoModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para el instrumento '+id+'.'});
  }
};
</script>

<?php include 'footer.php'; ?>
