<?php
/**
 * Gestión de Atributos por Tipo de Evidencia
 * Ruta esperada: atributos-tipo.php?id_tipo=1
 * - Requiere sesión.
 * - Solo roles 1 o 2 pueden acceder directamente (admin / editor, por ejemplo).
 * - No aparece en el menú; por eso NO usamos validacion-permiso.php aquí.
 * - Carga tabla por AJAX desde vista-atributos-tipo.php?id_tipo=...
 */

include 'validacion.php';
include 'conexion.php';
/**
 * Forzar que en el menú lateral quede marcada como "activa"
 * la página de Tipos de Evidencia (tipos-evidencia.php),
 * aunque estemos en atributos-tipo.php.
 *
 * Requiere el pequeño ajuste en left-menu.php (ver más abajo).
 */
$LEFT_ACTIVE_OVERRIDE = 'tipos-evidencia.php';

include 'header.php';
include 'left-menu.php';
// ===== Control de acceso (solo roles 1 o 2) =====
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;
if (!in_array($user_role, [1, 2], true)) {
  // Redirige a Tipos de evidencia si no tiene permiso directo
  header('Location: tipos-evidencia.php');
  exit();
}

$id_tipo = isset($_GET['id_tipo']) ? (int)$_GET['id_tipo'] : 0;
if ($id_tipo <= 0) {
  die('<div class="card"><div class="card-content"><p>Falta el parámetro <strong>id_tipo</strong>.</p></div></div>');
}

// Cargar info del tipo
$tipo = null;
$stmt = $conn->prepare("SELECT id_tipo_evidencia, nombre_tipo, descripcion FROM tipos_de_evidencia WHERE id_tipo_evidencia = ?");
$stmt->bind_param('i', $id_tipo);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  $tipo = $res->fetch_assoc();
} else {
  die('<div class="card"><div class="card-content"><p>Tipo de evidencia no encontrado.</p></div></div>');
}
$stmt->close();


?>

<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">
        Atributos — <?= htmlspecialchars($tipo['nombre_tipo']) ?> (ID: <?= (int)$tipo['id_tipo_evidencia'] ?>)
      </h1>
      <p class="text-muted-foreground" style="max-width:900px;">
        <?= htmlspecialchars($tipo['descripcion'] ?: 'Define los campos (atributos) que describen este tipo de evidencia.') ?>
      </p>
    </div>

    <div style="display:flex; gap:8px;">
      <!-- Botón Regresar -->
      <a class="btn" href="tipos-evidencia.php" title="Regresar a Tipos de Evidencia" aria-label="Regresar a Tipos de Evidencia">
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
          <path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span style="margin-left:6px;">Regresar</span>
      </a>

      <!-- Agregar atributo -->
      <button class="btn btn-primary" id="openModalAgregarAtributo" type="button" title="Agregar nuevo atributo">
        + Agregar Atributo
      </button>
    </div>
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <!-- Búsqueda externa -->
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por nombre, slug o descripción…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>
      <div></div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaAtributosTipo" class="card">
    <div class="card-content"><p class="text-muted-foreground">Cargando…</p></div>
  </div>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<!-- Modal Eliminar -->
<div class="dialog-backdrop" id="dlgBackdropAT"></div>
<div class="dialog" id="dlgEliminarAT" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarATTitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarATTitle">Eliminar atributo</h3>
      <p class="dialog-desc">Esta acción no se puede deshacer.</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarATText">¿Estás seguro de eliminar este atributo?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarAT()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminarAT()">Eliminar</button>
    </div>
  </div>
</div>

<?php
// Incluir modales de alta/edición
if (file_exists('modal-agregar-atributo-tipo.php')) include 'modal-agregar-atributo-tipo.php';
if (file_exists('modal-editar-atributo-tipo.php')) include 'modal-editar-atributo-tipo.php';
?>

<script>
/* ========= Utilidades ========= */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};

const ID_TIPO = <?= (int)$id_tipo ?>;

/* ========= DataTable / Carga ========= */
const SEARCH_KEY = 'atributosTipoSearch_' + ID_TIPO;
let dtAT = null;

loadTableAT();

function loadTableAT(){
  const url = 'vista-atributos-tipo.php?id_tipo=' + encodeURIComponent(ID_TIPO) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaAtributosTipo');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTAT();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTAT(){
  const $table = $('#tabla-atributos-tipo');
  if (!$table.length) return;

  if (dtAT) { dtAT.destroy(); dtAT = null; }

  dtAT = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[5, 'asc']], // por orden
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ atributos",
      infoFiltered: "(filtrado de _MAX_ atributos)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtAT.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtAT.search(saved).draw();
    $search.on('input', function(){
      const val = this.value;
      dtAT.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtAT.search('').draw();
    });
  }
}

/* ========= Handlers ========= */
window.openEditarAtributo = function(id){
  if (typeof window.openEditarAtributoModal === 'function') {
    window.openEditarAtributoModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para el atributo '+id+'.'});
  }
};

// Eliminar
let __deleteAtributoId = null;
let __deleteAtributoName = '';
window.openEliminarAT = function(id, name){
  __deleteAtributoId = id;
  __deleteAtributoName = name || '';
  const dlg = document.getElementById('dlgEliminarAT');
  const backdrop = document.getElementById('dlgBackdropAT');
  const txt = document.getElementById('dlgEliminarATText');

  if (txt) txt.innerHTML = '¿Eliminar el atributo <strong>' + (__deleteAtributoName || ('#' + id)) + '</strong>?';
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminarAT = function(){
  __deleteAtributoId = null;
  const dlg = document.getElementById('dlgEliminarAT');
  const backdrop = document.getElementById('dlgBackdropAT');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminarAT = function(){
  if (!__deleteAtributoId) return;
  fetch('atributo-tipo-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_ate: __deleteAtributoId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminarAT();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Eliminado', text:'Atributo eliminado correctamente.'});
      loadTableAT();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo eliminar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarAT();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo eliminar el atributo.'});
  });
};

// Cerrar modal eliminar tocando backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdropAT');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarAT);
})();
</script>

<?php include 'footer.php'; ?>
