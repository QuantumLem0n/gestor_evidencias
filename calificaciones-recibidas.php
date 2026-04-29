<?php
/**
 * Calificaciones recibidas
 * - Roles:
 *   - 1,2,3: ven generales.
 *   - 4: ven solo sus evidencias.
 * - Filtros: instrumento, estado.
 * - Muestra modal con la calificación (solo lectura).
 */

include 'validacion.php';
include 'conexion.php';
include 'header.php';
include 'left-menu.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

// Instrumentos activos (para filtro y para los botones PDF/Descargar)
$instrumentos = [];
$qI = "SELECT id_instrumento, abreviatura, nombre_completo, tipo_calificacion, min_calificacion, max_calificacion
       FROM instrumentos WHERE activo = 1 ORDER BY id_instrumento";
if ($rsI = mysqli_query($conn, $qI)) {
  while ($r = mysqli_fetch_assoc($rsI)) $instrumentos[] = $r;
}
?>
<style>
  /* Botón verde vistoso */
  .btn-success{
    background:#10b981; color:#fff; border:1px solid #059669;
  }
  .btn-success:hover{ background:#0ea5a1; }
  .btn-success:disabled{ opacity:.6; cursor:not-allowed; }
  .btn-success:focus{ outline:2px solid #99f6e4; outline-offset:2px; }
</style>

<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Calificaciones recibidas</h1>
      <p class="text-muted-foreground">
        <?php if (in_array((int)$user_role,[1,2,3],true)): ?>
          Vista general por instrumento de todas las evidencias visibles.
        <?php else: ?>
          Tus evidencias y sus calificaciones por instrumento.
        <?php endif; ?>
      </p>
    </div>

    <div style="display:flex; gap:8px; align-items:center;">
      <select id="filterInstrumento" class="input select-white" style="min-width:250px;">
        <option value="0">Instrumento: Todos</option>
        <?php foreach ($instrumentos as $inst): ?>
          <option value="<?= (int)$inst['id_instrumento'] ?>">
            <?= htmlspecialchars($inst['abreviatura'].' — '.$inst['nombre_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="filterEstado" class="input select-white" style="min-width:210px;">
        <option value="todas">Estado: Todas</option>
        <option value="aprobadas">Aprobadas</option>
        <option value="noaprobadas">No aprobadas</option>
        <option value="sin">Sin calificar</option>
      </select>

      <!-- Botón PDF (abre en nueva pestaña) -->
      <button id="btnGenerarPDF" class="btn btn-primary" type="button" disabled title="Generar PDF por instrumento">
        Generar PDF
      </button>

      <!-- Botón Descargar (verde y visible) -->
      <button id="btnAbrirDescarga" class="btn btn-success" type="button" disabled title="Descargar evidencias aprobadas de este instrumento">
        Descargar
      </button>
    </div>
  </header>

  <!-- Barra de búsqueda -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por título, docente o tipo…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaCalifRecibidas" class="card">
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

<!-- Modal Ver Calificación (solo lectura) -->
<div class="dialog-backdrop" id="dlgBackdropCalif"></div>
<div class="dialog" id="dlgVerCalif" role="dialog" aria-modal="true" aria-labelledby="dlgVerCalifTitle" data-open="false">
  <div class="dialog-card" style="max-width:640px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgVerCalifTitle">Calificación recibida</h3>
      <p class="dialog-desc">Detalle de la evaluación para la evidencia e instrumento seleccionados.</p>
    </div>
    <div class="dialog-body">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <div class="text-sm text-muted-foreground">Instrumento</div>
            <div class="font-medium" id="califInst"></div>
          </div>
          <div>
            <div class="text-sm text-muted-foreground">Tipo</div>
            <div id="califTipo"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3" id="rowRango" style="display:none;">
          <div>
            <div class="text-sm text-muted-foreground">Rango</div>
            <div id="califRango">—</div>
          </div>
          <div>
            <div class="text-sm text-muted-foreground">Umbral</div>
            <div id="califUmbral">—</div>
          </div>
        </div>

        <div class="eval-block" style="border:1px solid var(--border,#e5e7eb); border-radius:12px; padding:12px;">
          <div class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div>
              <div class="text-sm text-muted-foreground">Resultado</div>
              <div class="text-xl font-semibold" id="califResultado">—</div>
            </div>
            <div>
              <span id="califBadge" class="badge">—</span>
            </div>
          </div>
          <div style="margin-top:8px;">
            <div class="text-sm text-muted-foreground">Comentario</div>
            <div id="califComentario" style="white-space:pre-wrap;">—</div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <div class="text-sm text-muted-foreground">Calificado en</div>
            <div id="califFecha">—</div>
          </div>
          <div>
            <div class="text-sm text-muted-foreground">Última actualización</div>
            <div id="califAct">—</div>
          </div>
        </div>
      </div>
    </div>
    <div class="dialog-actions">
      <a id="btnIrDetalles" class="btn" href="#" target="_self" rel="nofollow">Ir a detalles de la evidencia</a>
      <button class="btn btn-primary" type="button" id="btnCerrarCalif">Cerrar</button>
    </div>
  </div>
</div>

<script>
/* ==== Utilidades de modal (ver calificación) ==== */
(function(){
  const modal = document.getElementById('dlgVerCalif');
  const backdrop = document.getElementById('dlgBackdropCalif');
  const btnClose = document.getElementById('btnCerrarCalif');
  window.lockPageScroll = function(lock){
    const html = document.documentElement;
    html.style.overflow = lock ? 'hidden' : '';
  };
  function openModal(){ modal.setAttribute('data-open','true'); backdrop.setAttribute('data-open','true'); lockPageScroll(true); }
  function closeModal(){ modal.setAttribute('data-open','false'); backdrop.setAttribute('data-open','false'); lockPageScroll(false); }
  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (backdrop) backdrop.addEventListener('click', closeModal);
  window.__openCalifModal = openModal;
  window.__closeCalifModal = closeModal;
})();

/* ==== Constantes ==== */
const USER_ROLE = <?= (int)$user_role ?>;
const NUMERIC_PASS_PCT = 0.60; // 60%

/* ==== Carga de la vista ==== */
let currentInstrumento = 0;
let currentEstado = 'todas';
let dtCR = null;
const SEARCH_KEY = 'califRecibidasSearch';

function loadTablaCR(){
  const url = 'vista-calificaciones-recibidas.php?instrumento=' + encodeURIComponent(currentInstrumento) +
              '&estado=' + encodeURIComponent(currentEstado) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaCalifRecibidas');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTCR();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTCR(){
  const $table = $('#tabla-calif-recibidas');
  if (!$table.length) return;
  if (dtCR) { dtCR.destroy(); dtCR = null; }

  dtCR = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'desc']],
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
    const saved = dtCR.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtCR.search(saved).draw();
    $search.on('input', function(){
      const val = this.value;
      dtCR.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      const $s = $('#search');
      if ($s.length) { $s.val(''); localStorage.setItem(SEARCH_KEY, ''); }
      dtCR.search('').draw();
    });
  }
}

// Filtros
document.getElementById('filterInstrumento').addEventListener('change', function(){
  currentInstrumento = parseInt(this.value || '0', 10) || 0;
  const disable = currentInstrumento <= 0;
  document.getElementById('btnGenerarPDF').disabled = disable;
  document.getElementById('btnAbrirDescarga').disabled = disable;
  loadTablaCR();
});

document.getElementById('filterEstado').addEventListener('change', function(){
  currentEstado = this.value || 'todas';
  loadTablaCR();
});

// Botón PDF (abre en nueva pestaña)
document.getElementById('btnGenerarPDF').addEventListener('click', function(){
  if (currentInstrumento <= 0) {
    if (window.Swal) Swal.fire({icon:'info', title:'Selecciona un instrumento', text:'Elige un instrumento para generar el PDF.'});
    return;
  }
  const url = 'calificaciones-pdf.php?instrumento=' + encodeURIComponent(currentInstrumento);
  window.open(url, '_blank');
});

// Botón Descargar (abre modal de checklist)
document.getElementById('btnAbrirDescarga').addEventListener('click', function(){
  if (currentInstrumento <= 0) {
    if (window.Swal) Swal.fire({icon:'info', title:'Selecciona un instrumento', text:'Elige un instrumento para descargar evidencias.'});
    return;
  }
  const sel = document.getElementById('filterInstrumento');
  const instName = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : '';
  if (window.__openDescModal) {
    window.__openDescModal(currentInstrumento, instName);
  }
});

// Carga inicial
loadTablaCR();

/* ==== Abrir modal con una calificación específica ==== */
window.openVerCalificacion = function(eviId, instId){
  // Limpia UI
  document.getElementById('califInst').textContent = '—';
  document.getElementById('califTipo').textContent = '—';
  document.getElementById('califRango').textContent = '—';
  document.getElementById('califUmbral').textContent = '—';
  document.getElementById('califResultado').textContent = '—';
  document.getElementById('califComentario').textContent = '—';
  document.getElementById('califFecha').textContent = '—';
  document.getElementById('califAct').textContent = '—';
  document.getElementById('califBadge').textContent = '—';
  document.getElementById('califBadge').className = 'badge';
  document.getElementById('rowRango').style.display = 'none';
  document.getElementById('btnIrDetalles').setAttribute('href', 'evidencia-detalle.php?id=' + encodeURIComponent(eviId));

  fetch('evaluacion-get.php?id=' + encodeURIComponent(eviId))
    .then(r=>r.json())
    .then(j=>{
      if (!j || j.status !== 'ok') {
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No fue posible cargar la evaluación.'});
        return;
      }
      const row = (j.instrumentos || []).find(x => Number(x.id_instrumento) === Number(instId));
      if (!row) {
        if (window.Swal) Swal.fire({icon:'info', title:'Sin datos', text:'Esta evidencia no tiene configuración para ese instrumento.'});
        return;
      }

      const esNum = Number(row.es_numerico) === 1;
      const min   = Number(row.cal_min ?? 0);
      const max   = Number(row.cal_max ?? (esNum ? 10 : 1));
      const res   = row.resultado;
      const com   = (row.comentario || '').toString();

      document.getElementById('califInst').textContent = (row.abreviatura || 'INS') + ' — ' + (row.nombre_completo || '');
      document.getElementById('califTipo').textContent = esNum ? 'NUMÉRICA' : 'APROBACIÓN';
      document.getElementById('califComentario').textContent = com || '—';
      document.getElementById('califFecha').textContent = (row.calificado_en || '—');
      document.getElementById('califAct').textContent = (row.actualizado_en || '—');
      document.getElementById('califResultado').textContent = (res === null || typeof res === 'undefined') ? 'Sin calificar' : res;

      if (esNum) {
        const umbral = min + NUMERIC_PASS_PCT * (max - min);
        document.getElementById('rowRango').style.display = '';
        document.getElementById('califRango').textContent = `${min} – ${max}`;
        document.getElementById('califUmbral').textContent = umbral.toFixed(2);
        const aprobado = (typeof res === 'number') && (res >= umbral);
        const badge = document.getElementById('califBadge');
        badge.textContent = (res === null) ? 'SIN CALIFICAR' : (aprobado ? 'APROBADA' : 'NO APROBADA');
        badge.className = 'badge ' + ((res === null) ? 'secondary' : (aprobado ? 'badge-ok' : 'badge-warn'));
      } else {
        const aprobado = (res === 1 || res === '1');
        const badge = document.getElementById('califBadge');
        badge.textContent = (res === null) ? 'SIN CALIFICAR' : (aprobado ? 'APROBADA' : 'NO APROBADA');
        badge.className = 'badge ' + ((res === null) ? 'secondary' : (aprobado ? 'badge-ok' : 'badge-warn'));
      }

      __openCalifModal();
    })
    .catch(()=>{
      if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No fue posible cargar la evaluación.'});
    });
};
</script>

<?php
// Solo markup + JS del modal (la API está separada en descargas-api.php)
include 'modal-descargar-archivos.php';
include 'footer.php';
