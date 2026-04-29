<?php
// modal-editar-tipo-evidencia.php
require_once 'conexion.php';

// Traer instrumentos activos para las opciones del modal
$ins = [];
$qIns = "SELECT id_instrumento, abreviatura, nombre_completo
         FROM instrumentos
         WHERE activo = 1
         ORDER BY id_instrumento";
if ($rs = $conn->query($qIns)) {
  while ($r = $rs->fetch_assoc()) { $ins[] = $r; }
  $rs->free();
}
?>
<div class="dialog-backdrop" id="dlgEditTipoBackdrop"></div>
<div class="dialog" id="dlgEditTipo" role="dialog" aria-modal="true" aria-labelledby="dlgEditTipoTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditTipoTitle">Editar tipo de evidencia</h3>
      <p class="dialog-desc">Actualiza el nombre, la descripción y sus instrumentos asociados.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formEditTipo" class="space-y-4" onsubmit="return false;">
        <input type="hidden" id="et_id" name="id_tipo_evidencia" />

        <div class="form-group">
          <label class="label" for="et_nombre">Nombre del tipo</label>
          <input class="input" id="et_nombre" name="nombre_tipo" type="text" maxlength="100" required />
        </div>

        <div class="form-group">
          <label class="label" for="et_desc">Descripción</label>
          <textarea class="input" id="et_desc" name="descripcion" rows="4" maxlength="1000"></textarea>
        </div>

        <!-- Instrumentos dinámicos -->
        <fieldset class="form-group" style="border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:12px;">
          <legend class="label" style="padding:0 6px;">Instrumento(s) de evaluación</legend>
          <div id="inst_list_edit" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
            <?php if (!empty($ins)): ?>
              <?php foreach ($ins as $i): ?>
                <?php
                  $id  = (int)$i['id_instrumento'];
                  $ab  = htmlspecialchars($i['abreviatura'] ?? '');
                  $nom = htmlspecialchars($i['nombre_completo'] ?? '');
                ?>
                <label style="display:flex; align-items:center; gap:8px;">
                  <input type="checkbox" name="instrumentos[]" value="<?= $id ?>">
                  <span><?= $ab ?></span>
                  <small class="text-muted" title="<?= $nom ?>">(<?= $nom ?>)</small>
                </label>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-muted">No hay instrumentos activos.</p>
            <?php endif; ?>
          </div>
        </fieldset>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelEditTipo">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitEditTipo" form="formEditTipo">Guardar cambios</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
</style>

<script>
// Abrir/cerrar + precarga
(function(){
  const modal     = document.getElementById('dlgEditTipo');
  const backdrop  = document.getElementById('dlgEditTipoBackdrop');
  const btnCancel = document.getElementById('btnCancelEditTipo');

  function open() {
    modal.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    const form = document.getElementById('formEditTipo');
    if (form) form.reset();
    // desmarcar todos los instrumentos:
    document.querySelectorAll('#inst_list_edit input[type="checkbox"]').forEach(cb => cb.checked = false);
  }

  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openEditarTipoModal = function(id){
    document.getElementById('formEditTipo').reset();
    document.querySelectorAll('#inst_list_edit input[type="checkbox"]').forEach(cb => cb.checked = false);

    fetch('tipo-evidencia-get.php?id='+encodeURIComponent(id))
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok' && data.tipo) {
          const t = data.tipo;
          document.getElementById('et_id').value     = t.id_tipo_evidencia;
          document.getElementById('et_nombre').value = t.nombre_tipo || '';
          document.getElementById('et_desc').value   = t.descripcion || '';

          // marcar instrumentos relacionados
          if (Array.isArray(data.instrumentos)) {
            const set = new Set(data.instrumentos.map(Number));
            document.querySelectorAll('#inst_list_edit input[type="checkbox"]').forEach(cb => {
              const val = parseInt(cb.value, 10);
              if (set.has(val)) cb.checked = true;
            });
          }

          open();
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudieron cargar los datos.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo cargar el tipo.'});
      });
  };

  window.closeEditTipoModal = close;
})();

// Submit edición
(function(){
  const form      = document.getElementById('formEditTipo');
  const btnSubmit = document.getElementById('btnSubmitEditTipo');

  form.addEventListener('submit', function(e){
    e.preventDefault();

    const id     = document.getElementById('et_id').value;
    const nombre = document.getElementById('et_nombre').value.trim();
    if (!id || !nombre) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('tipo-evidencia-update.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok') {
          if (typeof window.closeEditTipoModal === 'function') window.closeEditTipoModal();
          if (typeof loadTableTE === 'function') loadTableTE();
          if (window.Swal) Swal.fire({icon:'success', title:'Actualizado', text:'Tipo actualizado.'});
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudo actualizar.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al actualizar el tipo.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
