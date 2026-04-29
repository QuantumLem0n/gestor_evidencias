<?php
// modal-editar-atributo-tipo.php
require_once 'conexion.php';

// Cargar tipos de atributo para el <select>
$tipos = [];
$qT = "SELECT id_tipo_atributo, nombre_tipo, slug, grupo_storage, validador_regex FROM tipos_atributo ORDER BY id_tipo_atributo ASC";
if ($resT = mysqli_query($conn, $qT)) {
  while ($row = mysqli_fetch_assoc($resT)) { $tipos[] = $row; }
}
?>
<div class="dialog-backdrop" id="dlgEditATBackdrop"></div>
<div class="dialog" id="dlgEditAT" role="dialog" aria-modal="true" aria-labelledby="dlgEditATTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:900px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditATTitle">Editar atributo</h3>
      <p class="dialog-desc">Modifica la definición y validaciones del atributo.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formEditAT" class="space-y-4" onsubmit="return false;">
        <input type="hidden" id="eat_id" name="id_ate" />

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="eat_nombre">Nombre del atributo</label>
            <input class="input" id="eat_nombre" name="nombre_atributo" type="text" maxlength="120" required />
          </div>
          <div class="form-group">
            <label class="label" for="eat_slug">Slug</label>
            <input class="input" id="eat_slug" name="slug" type="text" maxlength="120" required />
          </div>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="eat_tipo">Tipo de atributo</label>
            <select class="input select-white" id="eat_tipo" name="id_tipo_atributo" required>
              <?php foreach ($tipos as $t): ?>
                <option
                  value="<?= (int)$t['id_tipo_atributo'] ?>"
                  data-slug="<?= htmlspecialchars($t['slug'], ENT_QUOTES) ?>"
                  data-grupo="<?= htmlspecialchars($t['grupo_storage'], ENT_QUOTES) ?>"
                >
                  <?= htmlspecialchars($t['nombre_tipo'].' ('.$t['slug'].')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="label" for="eat_orden">Orden</label>
            <input class="input" id="eat_orden" name="orden" type="number" min="1" step="1" required />
          </div>
        </div>

        <div class="form-group">
          <label class="label" for="eat_desc">Descripción (ayuda)</label>
          <textarea class="input" id="eat_desc" name="descripcion" rows="3" maxlength="1000"></textarea>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
          <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="input" style="width:auto;" id="eat_req" name="requerido" value="1" />
            <span>Requerido</span>
          </label>
          <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="input" style="width:auto;" id="eat_uni" name="unico_por_evidencia" value="1" />
            <span>Único por evidencia</span>
          </label>
          <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="input" style="width:auto;" id="eat_multi" name="multiple" value="1" />
            <span>Múltiple</span>
          </label>
        </div>

        <!-- Extras -->
        <div id="eextra_texto" style="display:none;">
          <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
            <div class="form-group">
              <label class="label" for="eat_minlen">Longitud mínima</label>
              <input class="input" id="eat_minlen" name="min_longitud" type="number" min="0" step="1" />
            </div>
            <div class="form-group">
              <label class="label" for="eat_maxlen">Longitud máxima</label>
              <input class="input" id="eat_maxlen" name="max_longitud" type="number" min="1" step="1" />
            </div>
          </div>
        </div>

        <div id="eextra_numerico" style="display:none;">
          <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
            <div class="form-group">
              <label class="label" for="eat_minv">Valor mínimo</label>
              <input class="input" id="eat_minv" name="min_valor" type="number" step="any" />
            </div>
            <div class="form-group">
              <label class="label" for="eat_maxv">Valor máximo</label>
              <input class="input" id="eat_maxv" name="max_valor" type="number" step="any" />
            </div>
          </div>
        </div>

        <div id="eextra_json" style="display:none;">
          <div class="form-group" style="margin-top:8px;">
            <label class="label" for="eat_opts">Opciones (JSON)</label>
            <textarea class="input" id="eat_opts" name="opciones_json" rows="4"></textarea>
          </div>
        </div>

      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelEditAT">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitEditAT" form="formEditAT">Guardar cambios</button>
    </div>
  </div>
</div>

<script>
(function(){
  const modal     = document.getElementById('dlgEditAT');
  const backdrop  = document.getElementById('dlgEditATBackdrop');
  const btnCancel = document.getElementById('btnCancelEditAT');
  const form      = document.getElementById('formEditAT');

  function open() {
    modal.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    if (form) form.reset();
    showExtra(null);
  }
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  function showExtra(grupo){
    document.getElementById('eextra_texto').style.display    = (grupo === 'texto_corto' || grupo === 'texto_largo') ? '' : 'none';
    document.getElementById('eextra_numerico').style.display = (grupo === 'entero' || grupo === 'decimal') ? '' : 'none';
    document.getElementById('eextra_json').style.display     = (grupo === 'json') ? '' : 'none';
  }

  const $tipo = document.getElementById('eat_tipo');
  if ($tipo) {
    $tipo.addEventListener('change', function(){
      const opt = this.options[this.selectedIndex];
      const grupo = opt ? opt.getAttribute('data-grupo') : null;
      showExtra(grupo);
    });
  }

  window.openEditarAtributoModal = function(id){
    form.reset();
    fetch('atributo-tipo-get.php?id='+encodeURIComponent(id))
      .then(r=>r.json())
      .then(d=>{
        if (d && d.status === 'ok' && d.atributo) {
          const a = d.atributo;
          document.getElementById('eat_id').value          = a.id_ate;
          document.getElementById('eat_nombre').value      = a.nombre_atributo || '';
          document.getElementById('eat_slug').value        = a.slug || '';
          document.getElementById('eat_orden').value       = a.orden || 1;
          document.getElementById('eat_desc').value        = a.descripcion || '';
          document.getElementById('eat_req').checked       = String(a.requerido) === '1';
          document.getElementById('eat_uni').checked       = String(a.unico_por_evidencia) === '1';
          document.getElementById('eat_multi').checked     = String(a.multiple) === '1';

          // set tipo
          const sel = document.getElementById('eat_tipo');
          if (sel) { sel.value = a.id_tipo_atributo; }
          const opt = sel ? sel.options[sel.selectedIndex] : null;
          const grupo = opt ? opt.getAttribute('data-grupo') : null;
          showExtra(grupo);

          // extras
          document.getElementById('eat_minlen').value = a.min_longitud ?? '';
          document.getElementById('eat_maxlen').value = a.max_longitud ?? '';
          document.getElementById('eat_minv').value   = a.min_valor ?? '';
          document.getElementById('eat_maxv').value   = a.max_valor ?? '';
          document.getElementById('eat_opts').value   = a.opciones_json ?? '';

          open();
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudieron cargar los datos.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo cargar el atributo.'});
      });
  };

  window.closeEditATModal = close;

  // Submit
  const btnSubmit = document.getElementById('btnSubmitEditAT');
  form.addEventListener('submit', function(e){
    e.preventDefault();

    const id     = document.getElementById('eat_id').value;
    const nombre = document.getElementById('eat_nombre').value.trim();
    const slug   = document.getElementById('eat_slug').value.trim();
    const tipo   = document.getElementById('eat_tipo').value;
    const orden  = document.getElementById('eat_orden').value;

    if (!id || !nombre || !slug || !tipo || !orden) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('atributo-tipo-update.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(d=>{
        if (d && d.status === 'ok') {
          if (typeof window.closeEditATModal === 'function') window.closeEditATModal();
          if (typeof loadTableAT === 'function') loadTableAT();
          if (window.Swal) Swal.fire({icon:'success', title:'Actualizado', text:'Atributo actualizado.'});
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudo actualizar.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al actualizar atributo.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
