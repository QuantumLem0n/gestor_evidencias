<?php
// modal-editar-usuario.php
// Modal para editar usuario. Se llena vía AJAX desde usuario-get.php (por id).
require_once 'conexion.php';

// Roles
$roles = [];
$qR = "SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC";
if ($resR = mysqli_query($conn, $qR)) {
  while ($row = mysqli_fetch_assoc($resR)) { $roles[] = $row; }
}
?>

<div class="dialog-backdrop" id="dlgEditUserBackdrop"></div>
<div class="dialog" id="dlgEditUser" role="dialog" aria-modal="true" aria-labelledby="dlgEditUserTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditUserTitle">Editar usuario</h3>
      <p class="dialog-desc">Actualiza los datos del usuario. Puedes activarlo o desactivarlo.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formEditUser" class="space-y-4" onsubmit="return false;">
        <input type="hidden" id="eu_id" name="id_usuario" />

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="eu_nombre">Nombre</label>
            <input class="input" id="eu_nombre" name="nombre" type="text" maxlength="80" required />
          </div>
          <div class="form-group">
            <label class="label" for="eu_apellidop">Apellido paterno</label>
            <input class="input" id="eu_apellidop" name="apellidop" type="text" maxlength="80" required />
          </div>
          <div class="form-group">
            <label class="label" for="eu_apellidom">Apellido materno</label>
            <input class="input" id="eu_apellidom" name="apellidom" type="text" maxlength="80" required />
          </div>
          <div class="form-group">
            <label class="label" for="eu_correo">Correo</label>
            <input class="input" id="eu_correo" name="correo" type="email" maxlength="120" required />
          </div>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="eu_rol">Rol</label>
            <select class="input select-white" id="eu_rol" name="rol" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id_rol'] ?>"><?= htmlspecialchars($r['id_rol'].' - '.$r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label" for="eu_activo">Estado</label>
            <select class="input select-white" id="eu_activo" name="activo" required>
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="label">Cambiar contraseña (opcional)</label>
          <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div style="display:flex; gap:8px; align-items:center;">
              <input class="input" id="eu_password" name="password" type="password" minlength="6" maxlength="100" placeholder="Nueva contraseña (opcional)" />
              <button class="btn" type="button" id="btnTogglePassEdit1" title="Mostrar/Ocultar">👁</button>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
              <input class="input" id="eu_confirm" name="confirm_password" type="password" minlength="6" maxlength="100" placeholder="Confirmar nueva contraseña" />
              <button class="btn" type="button" id="btnTogglePassEdit2" title="Mostrar/Ocultar">👁</button>
            </div>
          </div>
          <p class="text-muted" style="margin:4px 0 0;">Déjalas vacías si no deseas cambiar la contraseña.</p>
        </div>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelEditUser">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitEditUser" form="formEditUser">Guardar cambios</button>
    </div>
  </div>
</div>

<script>
// Apertura/cierre + carga
(function(){
  const modal     = document.getElementById('dlgEditUser');
  const backdrop  = document.getElementById('dlgEditUserBackdrop');
  const btnCancel = document.getElementById('btnCancelEditUser');

  function open() {
    modal.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    const form = document.getElementById('formEditUser');
    if (form) form.reset();
  }

  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  // Exponer para abrir con el id
  window.openEditarUsuarioModal = function(id){
    // Limpia
    document.getElementById('formEditUser').reset();
    // Traer datos
    fetch('usuario-get.php?id='+encodeURIComponent(id))
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok' && data.user) {
          const u = data.user;
          document.getElementById('eu_id').value         = u.id_usuario;
          document.getElementById('eu_nombre').value     = u.nombre || '';
          document.getElementById('eu_apellidop').value  = u.apellidop || '';
          document.getElementById('eu_apellidom').value  = u.apellidom || '';
          document.getElementById('eu_correo').value     = u.correo || '';
          document.getElementById('eu_rol').value        = u.rol || '';
          document.getElementById('eu_activo').value     = (u.activo != null ? String(u.activo) : '1');
          open();
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudieron cargar los datos.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo cargar el usuario.'});
      });
  };

  window.closeEditUserModal = close;
})();

// Toggle de password (solo visibilidad del input)
(function(){
  function toggle(id){
    const el = document.getElementById(id);
    if (!el) return;
    el.type = (el.type === 'password') ? 'text' : 'password';
  }
  const b1 = document.getElementById('btnTogglePassEdit1');
  const b2 = document.getElementById('btnTogglePassEdit2');
  if (b1) b1.addEventListener('click', ()=>toggle('eu_password'));
  if (b2) b2.addEventListener('click', ()=>toggle('eu_confirm'));
})();

// Submit edición
(function(){
  const form      = document.getElementById('formEditUser');
  const btnSubmit = document.getElementById('btnSubmitEditUser');

  form.addEventListener('submit', function(e){
    e.preventDefault();

    const id       = document.getElementById('eu_id').value;
    const nombre   = document.getElementById('eu_nombre').value.trim();
    const apP      = document.getElementById('eu_apellidop').value.trim();
    const apM      = document.getElementById('eu_apellidom').value.trim();
    const correo   = document.getElementById('eu_correo').value.trim();
    const rol      = document.getElementById('eu_rol').value;
    const activo   = document.getElementById('eu_activo').value;

    const pass  = document.getElementById('eu_password').value;
    const conf  = document.getElementById('eu_confirm').value;
    if ((pass || conf) && pass !== conf) {
      if (window.Swal) Swal.fire({icon:'warning', title:'Contraseñas', text:'Las contraseñas no coinciden.'});
      return;
    }

    if (!id || !nombre || !apP || !apM || !correo || !rol) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('usuario-update.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok') {
          if (typeof window.closeEditUserModal === 'function') window.closeEditUserModal();
          if (typeof loadTableU === 'function') loadTableU();
          if (window.Swal) Swal.fire({icon:'success', title:'Actualizado', text:'Usuario actualizado.'});
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudo actualizar.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al actualizar usuario.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
