<?php
// modal-agregar-pagina.php
// Modal (estilo propio del proyecto con .dialog) para crear una nueva página del menú con PREVISUALIZACIÓN DE ICONO.
require_once 'conexion.php';

// Iconos disponibles (necesitamos también el SVG para previsualizar)
$iconos = [];
$qI = "SELECT id_icono, descripcion, imagen FROM iconos ORDER BY descripcion ASC";
if ($resI = mysqli_query($conn, $qI)) {
  while ($row = mysqli_fetch_assoc($resI)) { $iconos[] = $row; }
}

// Roles disponibles
$roles = [];
$qR = "SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC";
if ($resR = mysqli_query($conn, $qR)) {
  while ($row = mysqli_fetch_assoc($resR)) { $roles[] = $row; }
}
?>

<!-- Backdrop + Modal -->
<div class="dialog-backdrop" id="dlgAddPageBackdrop"></div>
<div class="dialog" id="dlgAddPage" role="dialog" aria-modal="true" aria-labelledby="dlgAddPageTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgAddPageTitle">Agregar nueva página</h3>
      <p class="dialog-desc">Define el nombre, el archivo destino, el icono y los roles con acceso.</p>
    </div>

    <!-- ZONA SCROLLABLE -->
    <div class="dialog-body dialog-body-scroll">
      <form id="formAddPage" class="space-y-4" onsubmit="return false;">
        <!-- Nombre de página -->
        <div class="form-group">
          <label class="label" for="ap_nombre">Nombre de la página</label>
          <input class="input" id="ap_nombre" name="nombre_pagina" type="text" maxlength="50" placeholder="Ej. Reportes" required />
          <p class="text-muted" style="margin:4px 0 0;">Texto visible en el menú.</p>
        </div>

        <!-- Archivo -->
        <div class="form-group">
          <label class="label" for="ap_archivo">Archivo (ruta .php)</label>
          <input class="input" id="ap_archivo" name="archivo" type="text" maxlength="100" placeholder="ej. reportes.php" required />
          <p class="text-muted" style="margin:4px 0 0;">Nombre de archivo único (sin espacios).</p>
        </div>

        <!-- Icono (con PREVIEW) -->
        <div class="form-group">
          <label class="label" for="ap_icono">Icono</label>

          <!-- Preview -->
          <div id="ap_icon_preview"
               class="icon-preview"
               style="display:flex; align-items:center; gap:10px; padding:10px; border:1px dashed var(--border); border-radius:12px; margin-bottom:8px;">
            <div class="chip"
                 style="width:36px; height:36px; border-radius:10px; display:grid; place-items:center; background:var(--secondary); color:var(--foreground);">
              <!-- SVG dibujado aquí -->
              <svg id="ap_icon_preview_svg" class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:20px;height:20px;">
                <!-- Por defecto un placeholder (estrella) -->
                <path d="M12 3l2.7 5.5 6.1.9-4.4 4.2 1 6.2-5.4-2.9-5.4 2.9 1-6.2L3.2 9.4l6.1-.9L12 3z"
                      stroke="currentColor" stroke-width="1.6" fill="none"/>
              </svg>
            </div>
            <div style="min-width:0;">
              <div class="text-sm" id="ap_icon_preview_name" style="font-weight:600;">(Sin icono seleccionado)</div>
              <div class="text-muted" style="font-size:12px;">Así se verá en el menú lateral.</div>
            </div>
          </div>

          <!-- Select -->
          <select class="input select-white" id="ap_icono" name="id_icono" required>
            <option value="" selected disabled>Selecciona un icono…</option>
            <?php foreach ($iconos as $ico): ?>
              <?php
                $id   = (int)$ico['id_icono'];
                $desc = $ico['descripcion'];
                $svg  = $ico['imagen']; // paths/figuras
              ?>
              <option
                value="<?= $id ?>"
                data-desc="<?= htmlspecialchars($desc, ENT_QUOTES) ?>"
                data-svg='<?= htmlspecialchars($svg, ENT_QUOTES) ?>'>
                <?= htmlspecialchars($desc) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <p class="text-muted" style="margin:4px 0 0;">Se usará en el menú lateral.</p>
        </div>

        <!-- Roles (el 1 siempre se agrega y queda fijo) -->
        <div class="form-group">
          <label class="label">Roles con acceso</label>
          <p class="text-muted" style="margin:4px 0 8px;">Marca los roles que podrán ver esta página. El <strong>Rol 1</strong> (Super Usuario) siempre tendrá acceso.</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($roles as $r):
              $rid   = (int)$r['id_rol'];
              $rname = htmlspecialchars($r['nombre']);
              $isAdmin = ($rid === 1);
            ?>
              <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox"
                       class="input"
                       style="width:auto;"
                       name="roles[]"
                       value="<?= $rid ?>"
                       <?= $isAdmin ? 'checked disabled' : '' ?> />
                <span><?= $rname ?> (ID: <?= $rid ?>)</span>
                <?php if ($isAdmin): ?>
                  <!-- Garantiza que role 1 viaje aunque esté disabled -->
                  <input type="hidden" name="roles[]" value="1">
                <?php endif; ?>
              </label>
            <?php endforeach; ?>
          </div>
          <p id="ap_roles_error" class="text-muted" style="display:none; color:#dc2626;">Selecciona al menos un rol.</p>
        </div>
      </form>
    </div>

    <!-- ACCIONES (sticky) -->
    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelAddPage">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitAddPage" form="formAddPage">Agregar</button>
    </div>
  </div>
</div>

<style>
/* ===== Scroll en modales .dialog ===== */
.modal-card-scroll {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.dialog-body-scroll {
  overflow: auto;
  padding: 0 0 12px 0; /* ya traes paddings en tus grupos */
  /* Evita rebotes raros en iOS */
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
// ---- Bloquear scroll del fondo cuando el modal esté abierto ----
function lockPageScroll(lock) {
  const html = document.documentElement;
  if (lock) {
    html.style.overflow = 'hidden';
  } else {
    html.style.overflow = '';
  }
}

// ---- Apertura/cierre del modal ----
(function(){
  const modal    = document.getElementById('dlgAddPage');
  const backdrop = document.getElementById('dlgAddPageBackdrop');
  const openBtn  = document.getElementById('openModalAgregar'); // botón en gestion-menu.php
  const btnCancel= document.getElementById('btnCancelAddPage');

  function open() {
    modal.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    lockPageScroll(true);
    // Reset preview al abrir (si no hay icono seleccionado)
    const sel = document.getElementById('ap_icono');
    if (sel && !sel.value) renderAddIconPreview(null);
  }
  function close() {
    modal.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    lockPageScroll(false);
  }

  if (openBtn)   openBtn.addEventListener('click', open);
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openAddPageModal = open;
  window.closeAddPageModal = close;

  // Submitear con Enter dentro del form
  const form = document.getElementById('formAddPage');
  form.addEventListener('submit', (e)=>e.preventDefault());
})();

// ---- PREVISUALIZACIÓN DE ICONO (al seleccionar en el <select>) ----
(function(){
  const sel  = document.getElementById('ap_icono');
  const svgC = document.getElementById('ap_icon_preview_svg');
  const name = document.getElementById('ap_icon_preview_name');

  function safeSetInnerSVG(svgContainer, rawPaths){
    svgContainer.innerHTML = rawPaths || '';
  }

  window.renderAddIconPreview = function(optionEl){
    if (!svgC || !name) return;
    if (!optionEl) {
      svgC.innerHTML = '<path d="M12 3l2.7 5.5 6.1.9-4.4 4.2 1 6.2-5.4-2.9-5.4 2.9 1-6.2L3.2 9.4l6.1-.9L12 3z" stroke="currentColor" stroke-width="1.6" fill="none"/>';
      name.textContent = '(Sin icono seleccionado)';
      return;
    }
    const svg   = optionEl.getAttribute('data-svg')  || '';
    const label = optionEl.getAttribute('data-desc') || optionEl.textContent || 'Icono';
    safeSetInnerSVG(svgC, svg);
    name.textContent = label;
  };

  if (sel) {
    sel.addEventListener('change', function(){
      const opt = this.options[this.selectedIndex] || null;
      window.renderAddIconPreview(opt);
    });
  }
})();

// ---- Envío del formulario (fetch) ----
(function(){
  const form     = document.getElementById('formAddPage');
  const rolesErr = document.getElementById('ap_roles_error');
  const btnSubmit= document.getElementById('btnSubmitAddPage');

  form.addEventListener('submit', function(e){
    e.preventDefault();

    // Validación simple
    const nombre  = document.getElementById('ap_nombre').value.trim();
    const archivo = document.getElementById('ap_archivo').value.trim();
    const idIcono = document.getElementById('ap_icono').value;

    const rolesChecked = Array.from(form.querySelectorAll('input[name="roles[]"]:checked')).map(i=>i.value);
    if (rolesChecked.length === 0) {
      rolesErr.style.display = 'block';
      return;
    } else {
      rolesErr.style.display = 'none';
    }
    if (!nombre || !archivo || !idIcono) return;

    if (/\s/.test(archivo)) {
      alert('El nombre de archivo no debe contener espacios.');
      return;
    }

    btnSubmit.disabled = true;

    const fd = new FormData(form);
    fetch('menu-pagina-insert.php', { method:'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data && data.status === 'ok') {
          if (typeof window.closeAddPageModal === 'function') window.closeAddPageModal();
          form.reset();
          if (typeof loadTable === 'function') loadTable();
          if (window.Swal) {
            Swal.fire({icon:'success', title:'Creado', text:'La página se creó correctamente.'});
          } else {
            alert('Página creada correctamente.');
          }
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudo crear la página.';
          if (window.Swal) {
            Swal.fire({icon:'warning', title:'Aviso', text: msg});
          } else {
            alert(msg);
          }
        }
      })
      .catch(err => {
        if (window.Swal) {
          Swal.fire({icon:'error', title:'Error', text:'Ocurrió un error al crear la página.'});
        } else {
          alert('Error al crear la página.');
        }
        console.error(err);
      })
      .finally(() => { btnSubmit.disabled = false; });
  });
})();

// Usa la global si existe; si no, define fallback local
if (typeof window.lockPageScroll !== 'function') {
  window.lockPageScroll = function(lock){
    const html = document.documentElement;
    html.style.overflow = lock ? 'hidden' : '';
  };
}
</script>
