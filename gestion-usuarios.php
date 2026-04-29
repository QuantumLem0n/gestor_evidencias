<?php
/**
 * Gestión de usuarios (versión análoga a gestion-menu.php)
 * - Requiere sesión (validacion.php) y permiso (validacion-permiso.php).
 * - Usa header/left-menu/footer del proyecto.
 * - Carga la tabla con AJAX desde vista-gestion-usuarios.php.
 * - Búsqueda en cliente (input superior) + filtro por rol (servidor con ?role=ID).
 */

include 'validacion.php';
include 'conexion.php';
include 'validacion-permiso.php';
include 'header.php';
include 'left-menu.php';

// === Cargar roles para el filtro ===
$query_roles = "SELECT id_rol, nombre FROM roles ORDER BY id_rol";
$roles = [];
if ($rs = mysqli_query($conn, $query_roles)) {
  while ($r = mysqli_fetch_assoc($rs)) { $roles[] = $r; }
}
?>

<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Gestión de usuarios</h1>
      <p class="text-muted-foreground">Administra las cuentas de usuario, su rol y estado.</p>
    </div>

    <!-- Botón para abrir modal de "Agregar Usuario" (si lo usas) -->
    <button class="btn btn-primary" id="openModalAgregarUsuario" type="button" title="Agregar nuevo usuario">
      + Agregar Usuario
    </button>
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <!-- Búsqueda externa -->
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por nombre, correo…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>

      <!-- Filtro por Rol (servidor) -->
      <div>
        <select id="filterRole" class="input select-white" style="min-width:260px;">
          <option value="">Rol: Todos</option>
          <?php foreach ($roles as $rol): ?>
            <option value="<?= (int)$rol['id_rol'] ?>">
              <?= htmlspecialchars($rol['id_rol'].' - '.$rol['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaGestionUsuarios" class="card">
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

<!-- ===== Modal Eliminar (reutilizamos el patrón) ===== -->
<div class="dialog-backdrop" id="dlgBackdropU"></div>
<div class="dialog" id="dlgEliminarU" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarUTitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarUTitle">Eliminar usuario</h3>
      <p class="dialog-desc">Esta acción no se puede deshacer.</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarUText">¿Estás seguro de eliminar este usuario?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarU()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminarU()">Eliminar</button>
    </div>
  </div>
</div>

<?php
// Si usarás modales de alta/edición, inclúyelos aquí:
if (file_exists('modal-agregar-usuario.php')) include 'modal-agregar-usuario.php';
if (file_exists('modal-editar-usuario.php')) include 'modal-editar-usuario.php';
?>

<script>
/* =================== Utilidades comunes =================== */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};

/* =================== DataTable / Carga AJAX =================== */
const SEARCH_KEY = 'gestionUsuariosSearch';
let dtU = null;
let currentRoleU = '';

// Carga inicial
loadTableU();

document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('filterRole');
  if (sel) {
    sel.addEventListener('change', function(){
      currentRoleU = this.value || '';
      loadTableU();
    });
  }
});

function loadTableU(){
  const url = 'vista-gestion-usuarios.php?role=' + encodeURIComponent(currentRoleU) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaGestionUsuarios');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTU();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTU(){
  const $table = $('#tabla-usuarios');
  if (!$table.length) return;

  if (dtU) { dtU.destroy(); dtU = null; }

  dtU = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'asc']], // por ID
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
      infoFiltered: "(filtrado de _MAX_ usuarios)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtU.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtU.search(saved).draw();

    $search.on('input', function(){
      const val = this.value;
      dtU.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtU.search('').draw();
    });
  }
}

/* =================== Handlers globales =================== */

// Ver perfil
window.openVerPerfil = function(id){
  // Redirige a tu página de perfil (ajusta el nombre si ya tienes otra ruta)
  window.location.href = 'perfil-usuario.php?id=' + encodeURIComponent(id);
};

// Editar (abre modal editar si existe función global)
window.openEditarUsuario = function(id){
  if (typeof window.openEditarUsuarioModal === 'function') {
    window.openEditarUsuarioModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal o redirección para editar el usuario '+id+'.'});
  }
};

// Eliminar (diálogo)
let __deleteUserId = null;
window.openEliminarU = function(id, name){
  __deleteUserId = id;
  const dlg = document.getElementById('dlgEliminarU');
  const backdrop = document.getElementById('dlgBackdropU');
  const txt = document.getElementById('dlgEliminarUText');

  if (txt) txt.innerHTML = '¿Estás seguro de eliminar a <strong>' + (name || ('usuario #' + id)) + '</strong>?';
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminarU = function(){
  __deleteUserId = null;
  const dlg = document.getElementById('dlgEliminarU');
  const backdrop = document.getElementById('dlgBackdropU');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminarU = function(){
  if (!__deleteUserId) return;
  fetch('usuario-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_usuario: __deleteUserId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminarU();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Eliminado', text:'El usuario se eliminó correctamente.'});
      loadTableU();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo eliminar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarU();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo eliminar el usuario.'});
  });
};

// Cerrar modal de eliminar tocando el backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdropU');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarU);
})();
</script>

<?php include 'footer.php'; ?>
