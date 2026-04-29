<?php
// modal-agregar-tipo-evidencia.php
require_once 'conexion.php';

// Traer instrumentos activos para mostrar como checkboxes
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
<div class="dialog-backdrop" id="dlgAddTipoBackdrop"></div>
<div class="dialog" id="dlgAddTipo" role="dialog" aria-modal="true" aria-labelledby="dlgAddTipoTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgAddTipoTitle">Agregar tipo de evidencia</h3>
      <p class="dialog-desc">Define el nombre del tipo, su descripción y los instrumentos donde aplica.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formAddTipo" class="space-y-4" onsubmit="return false;">
        <div class="form-group">
          <label class="label" for="te_nombre">Nombre del tipo</label>
          <input class="input" id="te_nombre" name="nombre_tipo" type="text" maxlength="100" placeholder="Ej. Libro, Conferencia, Artículo" required />
          <p class="text-muted" style="margin:4px 0 0;">Debe ser único.</p>
        </div>

        <div class="form-group">
          <label class="label" for="te_desc">Descripción</label>
          <textarea class="input" id="te_desc" name="descripcion" rows="4" maxlength="1000" placeholder="Descripción breve (opcional)"></textarea>
        </div>

        <!-- Instrumentos dinámicos -->
        <fieldset class="form-group" style="border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:12px;">
          <legend class="label" style="padding:0 6px;">Instrumento(s) de evaluación</legend>
          <div id="inst_list_add" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
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
      <button type="button" class="btn" id="btnCancelAddTipo">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitAddTipo" form="formAddTipo">Agregar</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
</style>

<script>
// Abrir/cerrar
(function(){
  const modal     = document.getElementById('dlgAddTipo');
  const backdrop  = document.getElementById('dlgAddTipoBackdrop');
  const openBtn   = document.getElementById('openModalAgregarTipo');
  const btnCancel = document.getElementById('btnCancelAddTipo');

  function open() {
    modal.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    const form = document.getElementById('formAddTipo');
    if (form) form.reset();
  }

  if (openBtn)   openBtn.addEventListener('click', open);
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openAddTipoModal  = open;
  window.closeAddTipoModal = close;
})();

// Envío
(function(){
  const form      = document.getElementById('formAddTipo');
  const btnSubmit = document.getElementById('btnSubmitAddTipo');

  form.addEventListener('submit', function(e){
    e.preventDefault();

    const nombre = document.getElementById('te_nombre').value.trim();
    if (!nombre) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('tipo-evidencia-insert.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok') {
          if (typeof window.closeAddTipoModal === 'function') window.closeAddTipoModal();
          if (typeof loadTableTE === 'function') loadTableTE();
          if (window.Swal) Swal.fire({icon:'success', title:'Creado', text:'El tipo fue creado correctamente.'});
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudo crear el tipo.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Ocurrió un error al crear el tipo.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
