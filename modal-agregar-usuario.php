<?php
// modal-agregar-usuario.php
// Modal para crear usuario con validaciones, toggle de "mostrar contraseña" y carga de roles.
require_once 'conexion.php';

// Roles disponibles
$roles = [];
$qR = "SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC";
if ($resR = mysqli_query($conn, $qR)) {
  while ($row = mysqli_fetch_assoc($resR)) { $roles[] = $row; }
}
?>

<!-- Backdrop + Modal -->
<div class="dialog-backdrop" id="dlgAddUserBackdrop"></div>
<div class="dialog" id="dlgAddUser" role="dialog" aria-modal="true" aria-labelledby="dlgAddUserTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgAddUserTitle">Agregar nuevo usuario</h3>
      <p class="dialog-desc">Completa los datos del usuario y asigna un rol.</p>
    </div>

    <!-- ZONA SCROLLABLE -->
    <div class="dialog-body dialog-body-scroll">
      <form id="formAddUser" class="space-y-4" onsubmit="return false;">
        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="au_nombre">Nombre</label>
            <input class="input" id="au_nombre" name="nombre" type="text" maxlength="80" placeholder="Nombre(s)" required />
          </div>
          <div class="form-group">
            <label class="label" for="au_apellidop">Apellido paterno</label>
            <input class="input" id="au_apellidop" name="apellidop" type="text" maxlength="80" placeholder="Paterno" required />
          </div>
          <div class="form-group">
            <label class="label" for="au_apellidom">Apellido materno</label>
            <input class="input" id="au_apellidom" name="apellidom" type="text" maxlength="80" placeholder="Materno" required />
          </div>
          <div class="form-group">
            <label class="label" for="au_correo">Correo</label>
            <input class="input" id="au_correo" name="correo" type="email" maxlength="120" placeholder="correo@dominio.com" required />
          </div>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="au_password">Contraseña</label>
            <div style="display:flex; gap:8px; align-items:center;">
              <input class="input" id="au_password" name="password" type="password" minlength="6" maxlength="100" placeholder="Mínimo 6 caracteres" required />
              <button class="btn" type="button" id="btnTogglePass" title="Mostrar/Ocultar contraseña">👁</button>
            </div>
            <p class="text-muted" style="margin:4px 0 0;">Solo cambia visibilidad, no almacena texto plano.</p>
          </div>
          <div class="form-group">
            <label class="label" for="au_confirm">Confirmar contraseña</label>
            <div style="display:flex; gap:8px; align-items:center;">
              <input class="input" id="au_confirm" name="confirm_password" type="password" minlength="6" maxlength="100" placeholder="Repite la contraseña" required />
              <button class="btn" type="button" id="btnTogglePass2" title="Mostrar/Ocultar confirmación">👁</button>
            </div>
          </div>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="au_rol">Rol</label>
            <select class="input select-white" id="au_rol" name="rol" required>
              <option value="" selected disabled>Selecciona un rol…</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id_rol'] ?>">
                  <?= htmlspecialchars($r['id_rol'].' - '.$r['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="label" for="au_activo">Estado</label>
            <select class="input select-white" id="au_activo" name="activo" required>
              <option value="1" selected>Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <!-- ACCIONES (sticky) -->
    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelAddUser">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitAddUser" form="formAddUser">Agregar</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
</style>

<script>
// ---- Apertura/cierre del modal ----
(function(){
  const modal     = document.getElementById('dlgAddUser');
  const backdrop  = document.getElementById('dlgAddUserBackdrop');
  const openBtn   = document.getElementById('openModalAgregarUsuario'); // botón en gestion-usuarios.php
  const btnCancel = document.getElementById('btnCancelAddUser');

  function open() {
    modal.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    const form = document.getElementById('formAddUser');
    if (form) form.reset();
  }

  if (openBtn)   openBtn.addEventListener('click', open);
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openAddUserModal  = open;
  window.closeAddUserModal = close;
})();

// ---- Toggle mostrar / ocultar contraseña (solo visibilidad del input) ----
(function(){
  function toggle(id){
    const el = document.getElementById(id);
    if (!el) return;
    el.type = (el.type === 'password') ? 'text' : 'password';
  }
  const b1 = document.getElementById('btnTogglePass');
  const b2 = document.getElementById('btnTogglePass2');
  if (b1) b1.addEventListener('click', ()=>toggle('au_password'));
  if (b2) b2.addEventListener('click', ()=>toggle('au_confirm'));
})();

// ---- Envío del formulario (fetch JSON) ----
(function(){
  const form      = document.getElementById('formAddUser');
  const btnSubmit = document.getElementById('btnSubmitAddUser');

  form.addEventListener('submit', function(e){
    e.preventDefault();

    const nombre   = document.getElementById('au_nombre').value.trim();
    const apP      = document.getElementById('au_apellidop').value.trim();
    const apM      = document.getElementById('au_apellidom').value.trim();
    const correo   = document.getElementById('au_correo').value.trim();
    const pass     = document.getElementById('au_password').value;
    const pass2    = document.getElementById('au_confirm').value;
    const rol      = document.getElementById('au_rol').value;
    const activo   = document.getElementById('au_activo').value;

    if (!nombre || !apP || !apM || !correo || !pass || !pass2 || !rol) return;
    if (pass !== pass2) {
      if (window.Swal) Swal.fire({icon:'warning', title:'Contraseñas', text:'Las contraseñas no coinciden.'});
      return;
    }

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('usuario-insert.php', { method:'POST', body:fd })
      .then(r => r.json())
      .then(data => {
        if (data && data.status === 'ok') {
          if (typeof window.closeAddUserModal === 'function') window.closeAddUserModal();
          if (typeof loadTableU === 'function') loadTableU();
          if (window.Swal) Swal.fire({icon:'success', title:'Creado', text:'El usuario se creó correctamente.'});
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudo crear el usuario.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(err=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Ocurrió un error al crear el usuario.'});
        console.error(err);
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
