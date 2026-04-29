<?php
/**
 * Ventana de Evaluación de Evidencias
 * - Muestra SOLO evidencias con 100% de atributos capturados.
 * - Filtros: Estado (Pendiente/Completa/Todas), Instrumento, Búsqueda.
 * - Abre modal para ver atributos y evaluar por instrumento.
 */
include 'validacion.php';
include 'conexion.php';
include 'validacion-permiso.php';
include 'header.php';
include 'left-menu.php';

// Instrumentos para filtro
$ins = [];
$qIns = "SELECT id_instrumento, abreviatura FROM instrumentos WHERE activo = 1 ORDER BY id_instrumento";
if ($rs = $conn->query($qIns)) { while ($r = $rs->fetch_assoc()) $ins[] = $r; $rs->free(); }
?>
<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Evaluación de evidencias</h1>
      <p class="text-muted-foreground">Solo aparecen evidencias con 100% de atributos completados.</p>
    </div>
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <!-- Búsqueda -->
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por título, docente o tipo…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>

      <!-- Filtros -->
      <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        <!-- Estado -->
        <div class="btn-group" role="group" aria-label="Estado">
          <button class="btn" data-est="todas"    id="btnEstTodas">Todas</button>
          <button class="btn" data-est="pendiente" id="btnEstPend">Pendientes</button>
          <button class="btn" data-est="completa"  id="btnEstComp">Completas</button>
        </div>

        <!-- Instrumento -->
        <select id="filterInstrumento" class="input" style="min-width: 180px;">
          <option value="">Instrumento: Todos</option>
          <?php foreach ($ins as $i): ?>
            <option value="<?= (int)$i['id_instrumento'] ?>"><?= htmlspecialchars($i['abreviatura']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="wrapTablaEval" class="card">
    <div class="card-content">
      <p class="text-muted-foreground">Cargando…</p>
    </div>
  </div>
</section>

<!-- Modal Evaluar -->
<?php if (file_exists('modal-evaluar-evidencia.php')) include 'modal-evaluar-evidencia.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<style>
.badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; background:#eef2ff; color:#3730a3; }
.badge-ok { background:#dcfce7; color:#065f46; }
.badge-warn { background:#fee2e2; color:#991b1b; }
.progress { width: 120px; height: 10px; border-radius: 999px; background: var(--muted, #eee); overflow: hidden; display: inline-block; vertical-align: middle; }
.progress-bar { height: 100%; background: #10b981; }
</style>

<script>
const EVAL_SEARCH_KEY = 'evalSearch';
const EVAL_STATE_KEY  = 'evalState';
const EVAL_INST_KEY   = 'evalInst';
let dtEVAL = null;

function loadTablaEval(){
  const estado = localStorage.getItem(EVAL_STATE_KEY) || 'todas';
  const inst   = localStorage.getItem(EVAL_INST_KEY) || '';

  const url = 'vista-evaluacion.php?r=' + Date.now()
            + '&estado=' + encodeURIComponent(estado)
            + '&instrumento=' + encodeURIComponent(inst);

  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('wrapTablaEval');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTEVAL();
    }
  };
  xhr.open('GET', url, true); xhr.send();
}

function initDTEVAL(){
  const $t = $('#tabla-evaluacion');
  if (!$t.length) return;
  if (dtEVAL) { dtEVAL.destroy(); dtEVAL = null; }

  dtEVAL = $t.DataTable({
    responsive: { details: { type:'column', target: 0 } },
    columnDefs: [
      { className:'control', orderable:false, targets:0 },
      { orderable:false, targets: 9 } // Acciones
    ],
    paging:true, pageLength:10, lengthMenu:[10,25,50,100],
    stateSave:true, order:[[1,'desc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language:{
      info:"Mostrando _START_ a _END_ de _TOTAL_ evidencias",
      infoFiltered:"(filtrado de _MAX_ evidencias)",
      emptyTable:"No hay información",
      zeroRecords:"No se encontró coincidencia",
      lengthMenu:"Mostrar _MENU_",
      paginate:{ first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // Búsqueda externa
  const $s = $('#search'), $c = $('#clearSearch');
  if ($s.length) {
    const saved = dtEVAL.state()?.search?.search || localStorage.getItem(EVAL_SEARCH_KEY) || '';
    $s.val(saved); if (saved) dtEVAL.search(saved).draw();
    $s.on('input', function(){
      const v = this.value || '';
      dtEVAL.search(v).draw(); localStorage.setItem(EVAL_SEARCH_KEY, v);
    });
  }
  if ($c.length) {
    $c.on('click', function(){
      if ($s.length) { $s.val(''); localStorage.setItem(EVAL_SEARCH_KEY,''); }
      dtEVAL.search('').draw();
    });
  }
}

// Filtros (persistencia)
(function(){
  // Estado
  const initState = localStorage.getItem(EVAL_STATE_KEY) || 'todas';
  ['btnEstTodas','btnEstPend','btnEstComp'].forEach(id=>{
    const el = document.getElementById(id);
    if (el) el.classList.remove('btn-primary');
  });
  if (initState==='pendiente') document.getElementById('btnEstPend')?.classList.add('btn-primary');
  else if (initState==='completa') document.getElementById('btnEstComp')?.classList.add('btn-primary');
  else document.getElementById('btnEstTodas')?.classList.add('btn-primary');

  document.querySelectorAll('.btn-group .btn').forEach(b=>{
    b.addEventListener('click', ()=>{
      document.querySelectorAll('.btn-group .btn').forEach(x=>x.classList.remove('btn-primary'));
      b.classList.add('btn-primary');
      localStorage.setItem(EVAL_STATE_KEY, b.getAttribute('data-est') || 'todas');
      loadTablaEval();
    });
  });

  // Instrumento
  const sel = document.getElementById('filterInstrumento');
  if (sel) {
    const instSaved = localStorage.getItem(EVAL_INST_KEY) || '';
    sel.value = instSaved;
    sel.addEventListener('change', ()=>{
      localStorage.setItem(EVAL_INST_KEY, sel.value || '');
      loadTablaEval();
    });
  }
})();

// Abrir modal evaluar
window.openEvaluarEvidencia = function(id){
  if (typeof window.evalOpenModal === 'function') window.evalOpenModal(id);
};

// Cargar al entrar
loadTablaEval();
</script>

<?php include 'footer.php'; ?>
