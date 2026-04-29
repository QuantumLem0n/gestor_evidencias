<?php
// modal-editar-pagina.php
require_once __DIR__ . '/conexion.php';

// === Iconos (incluye SVG para preview) ===
$iconos = [];
$qI = "SELECT id_icono, descripcion, imagen FROM iconos ORDER BY descripcion ASC";
if ($ri = mysqli_query($conn, $qI)) {
  while ($row = mysqli_fetch_assoc($ri)) { $iconos[] = $row; }
}

// === Roles ===
$roles = [];
$qR = "SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC";
if ($rr = mysqli_query($conn, $qR)) {
  while ($row = mysqli_fetch_assoc($rr)) { $roles[] = $row; }
}
?>

<!-- Backdrop + Modal Editar -->
<div class="dialog-backdrop" id="dlgBackdropEdit"></div>
<div class="dialog" id="dlgEditar" role="dialog" aria-modal="true" aria-labelledby="dlgEditarTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditarTitle">Editar página</h3>
      <p class="dialog-desc">Actualiza la información y los roles con acceso.</p>
    </div>

    <!-- Cuerpo scrolleable -->
    <div class="dialog-body dialog-body-scroll">
      <form id="formEditarPagina">
        <input type="hidden" id="ep_id" name="id_mp" value="">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Nombre -->
          <div class="form-group">
            <label class="label" for="ep_nombre">Nombre de la página</label>
            <input class="input" id="ep_nombre" name="nombre_pagina" type="text" maxlength="50" required>
          </div>

          <!-- Archivo -->
          <div class="form-group">
            <label class="label" for="ep_archivo">Archivo (ruta o .php)</label>
            <input class="input" id="ep_archivo" name="archivo" type="text" maxlength="100" required>
            <p class="text-muted" style="margin:4px 0 0;">Debe ser único (sin espacios).</p>
          </div>

          <!-- Icono con preview -->
          <div class="form-group">
            <label class="label" for="ep_icono">Icono</label>
            <select class="input select-white" id="ep_icono" name="id_icono" required>
              <option value="" disabled>Selecciona un icono…</option>
              <?php foreach ($iconos as $ico):
                $id   = (int)$ico['id_icono'];
                $desc = $ico['descripcion'] ?? '';
                $svg  = $ico['imagen'] ?? '';
                // Guardamos el SVG como base64 para poder ponerlo en data-*
                $svgB64 = base64_encode($svg);
              ?>
                <option value="<?= $id ?>" data-desc="<?= htmlspecialchars($desc) ?>" data-svg="<?= htmlspecialchars($svgB64) ?>">
                  <?= htmlspecialchars($desc) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <!-- Vista previa -->
            <div id="ep_icono_preview" class="icon-preview" style="
              display:flex; align-items:center; gap:10px; margin-top:8px;
              padding:10px; border:1px dashed var(--border); border-radius:10px;
              background: var(--input-background);
            ">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:22px;height:22px;"></svg>
              <span class="text-muted">Selecciona un icono…</span>
            </div>
            <p class="text-muted" style="margin:6px 0 0;">Se usará en el menú lateral.</p>
          </div>

          <!-- Roles -->
          <div class="form-group">
            <label class="label">Roles con acceso</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="ep_roles_box">
              <?php foreach ($roles as $r):
                $rid    = (int)$r['id_rol'];
                $rname  = htmlspecialchars($r['nombre']);
                $isAdmin = ($rid === 1);
              ?>
                <label class="flex items-center" style="display:flex;align-items:center;gap:10px;">
                  <input type="checkbox" class="input" name="roles[]" value="<?= $rid ?>"
                         <?= $isAdmin ? 'checked disabled' : '' ?>>
                  <span><?= $rname ?> (ID: <?= $rid ?>)</span>
                  <?php if ($isAdmin): ?>
                    <!-- Garantiza que role 1 viaje -->
                    <input type="hidden" name="roles[]" value="1">
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
            <p class="text-muted" style="margin:6px 0 0;">El Rol 1 siempre tendrá acceso.</p>
          </div>
        </div>
      </form>
    </div>

    <!-- Acciones sticky -->
    <div class="dialog-actions dialog-actions-sticky">
      <button class="btn" type="button" id="btnCancelEdit">Cancelar</button>
      <button class="btn btn-primary" type="button" id="btnSaveEdit">Guardar</button>
    </div>
  </div>
</div>

<style>
/* Scroll friendly para móvil */
.modal-card-scroll {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.dialog-body-scroll {
  overflow: auto;
  padding: 0 0 12px 0;
  -webkit-overflow-scrolling: touch;
}
.dialog-actions-sticky {
  position: sticky;
  bottom: 0;
  background: var(--card);
  border-top: 1px solid var(--border);
  padding-top: 12px;
  margin-top: 0;
  box-shadow: 0 -6px 12px rgba(0,0,0,0.06);
}
</style>

<script>
// ========== Utilidad (fallback) para bloquear scroll del fondo ==========
if (typeof window.lockPageScroll !== 'function') {
  window.lockPageScroll = function(lock){
    document.documentElement.style.overflow = lock ? 'hidden' : '';
  };
}

// ========== Preview del icono en el modal de edición ==========
(function(){
  const sel  = document.getElementById('ep_icono');
  const box  = document.getElementById('ep_icono_preview');
  if (!sel || !box) return;

  const svgEl = box.querySelector('svg');
  const label = box.querySelector('span');

  function renderPreview(){
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return;
    const desc   = opt.dataset.desc || 'Icono';
    const svgB64 = opt.dataset.svg || '';
    let inner = '';
    try { inner = svgB64 ? atob(svgB64) : ''; } catch(e){ inner = ''; }
    if (svgEl) svgEl.innerHTML = inner;
    if (label) label.textContent = desc || 'Selecciona un icono…';
  }

  sel.addEventListener('change', renderPreview);
  // Lo exponemos para reutilizar al precargar:
  window._renderIconoEditPreview = renderPreview;
})();

// ========== Abrir / Cerrar modal ==========
(function(){
  const dlg      = document.getElementById('dlgEditar');
  const backdrop = document.getElementById('dlgBackdropEdit');
  const btnCancel= document.getElementById('btnCancelEdit');
  const btnSave  = document.getElementById('btnSaveEdit');
  const form     = document.getElementById('formEditarPagina');

  function openModal(){
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    window.lockPageScroll(true);
  }
  function closeModal(){
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    window.lockPageScroll(false);
  }

  if (btnCancel) btnCancel.addEventListener('click', closeModal);
  if (backdrop)  backdrop.addEventListener('click', closeModal);

  // ========== API GLOBAL invocada desde gestion-menu.php (openEditar(id)) ==========
  window.openEditarPagina = function(id){
    // Reset básico
    if (form) form.reset();
    // Desmarcar roles (excepto admin hidden que queda)
    if (form) {
      form.querySelectorAll('input[type="checkbox"][name="roles[]"]').forEach(ch => {
        ch.checked = ch.disabled ? true : false;
      });
    }

    // Cargar datos
    fetch('menu-pagina-get.php?id=' + encodeURIComponent(id))
      .then(r => r.json())
      .then(j => {
        if (!j || j.status !== 'ok') {
          const msg = (j && j.message) ? j.message : 'No se pudo cargar la página.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg}); else alert(msg);
          return;
        }

        const data = j.data || {};
        document.getElementById('ep_id').value      = data.id_mp || '';
        document.getElementById('ep_nombre').value  = data.nombre_pagina || '';
        document.getElementById('ep_archivo').value = data.archivo || '';

        // Icono
        const sel = document.getElementById('ep_icono');
        if (sel) {
          const v = String(data.id_icono || '');
          Array.from(sel.options).forEach(o => { o.selected = (String(o.value) === v); });
          if (typeof window._renderIconoEditPreview === 'function') window._renderIconoEditPreview();
        }

        // Roles
        const roles = Array.isArray(data.roles) ? data.roles.map(String) : [];
        if (form) {
          form.querySelectorAll('input[type="checkbox"][name="roles[]"]').forEach(ch => {
            const val = String(ch.value);
            if (ch.disabled && val === '1') { ch.checked = true; return; }
            ch.checked = roles.includes(val);
          });
        }

        openModal();
      })
      .catch(() => {
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error de red al cargar la página.'});
        else alert('Error de red al cargar la página.');
      });
  };

  // ========== Guardar cambios ==========
  if (btnSave) {
    btnSave.addEventListener('click', function(){
      // Validación simple
      const archivo = (document.getElementById('ep_archivo').value || '').trim();
      if (/\s/.test(archivo)) {
        if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text:'El nombre de archivo no debe contener espacios.'});
        else alert('El nombre de archivo no debe contener espacios.');
        return;
      }

      const fd = new FormData(form);
      fetch('menu-pagina-update.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(j => {
          if (j && j.status === 'ok') {
            closeModal();
            if (typeof window.loadTable === 'function') window.loadTable();
            if (window.Swal) Swal.fire({icon:'success', title:'Guardado', text:'Cambios aplicados correctamente.'});
          } else {
            const msg = (j && j.message) ? j.message : 'No se pudo guardar.';
            if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg}); else alert(msg);
          }
        })
        .catch(() => {
          if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error de red al guardar.'});
          else alert('Error de red al guardar.');
        });
    });
  }
})();
</script>
