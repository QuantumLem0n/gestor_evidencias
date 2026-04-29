<?php
// modal-agregar-atributo-tipo.php
require_once 'conexion.php';

// Cargar tipos de atributo (para el <select>)
$tipos = [];
$qT = "SELECT id_tipo_atributo, nombre_tipo, slug, grupo_storage, validador_regex FROM tipos_atributo ORDER BY id_tipo_atributo ASC";
if ($resT = mysqli_query($conn, $qT)) {
  while ($row = mysqli_fetch_assoc($resT)) { $tipos[] = $row; }
}

// Determinar id_tipo para sugerir "orden" default (max+1)
$id_tipo_ctx = isset($_GET['id_tipo']) ? (int)$_GET['id_tipo'] : 0;
$nextOrden = 1;
if ($id_tipo_ctx > 0) {
  $qOrd = "SELECT COALESCE(MAX(orden),0)+1 AS nexto FROM atributos_tipo_evidencia WHERE id_tipo_evidencia = ".$id_tipo_ctx;
  if ($rOrd = mysqli_query($conn, $qOrd)) {
    if ($row = mysqli_fetch_assoc($rOrd)) $nextOrden = (int)$row['nexto'];
  }
}
?>
<div class="dialog-backdrop" id="dlgAddATBackdrop"></div>
<div class="dialog" id="dlgAddAT" role="dialog" aria-modal="true" aria-labelledby="dlgAddATTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:900px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgAddATTitle">Agregar atributo</h3>
      <p class="dialog-desc">Configura el nombre, tipo, slug, orden y validaciones.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formAddAT" class="space-y-4" onsubmit="return false;">
        <input type="hidden" name="id_tipo_evidencia" id="aat_id_tipo" value="<?= (int)$id_tipo_ctx ?>"/>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="aat_nombre">Nombre del atributo</label>
            <input class="input" id="aat_nombre" name="nombre_atributo" type="text" maxlength="120" placeholder="Ej. Autores" required />
          </div>
          <div class="form-group">
            <label class="label" for="aat_slug">Slug</label>
            <input class="input" id="aat_slug" name="slug" type="text" maxlength="120" placeholder="Ej. autores" required />
            <p class="text-muted" style="margin:4px 0 0;">Minúsculas, sin espacios (usa guiones bajos). Debe ser único dentro del tipo.</p>
          </div>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="aat_tipo">Tipo de atributo</label>
            <select class="input select-white" id="aat_tipo" name="id_tipo_atributo" required>
              <option value="">Selecciona…</option>
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
            <label class="label" for="aat_orden">Orden</label>
            <input class="input" id="aat_orden" name="orden" type="number" min="1" step="1" value="<?= $nextOrden ?>" required />
          </div>
        </div>

        <div class="form-group">
          <label class="label" for="aat_desc">Descripción (ayuda)</label>
          <textarea class="input" id="aat_desc" name="descripcion" rows="3" maxlength="1000" placeholder="Texto de ayuda (opcional)"></textarea>
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
          <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="input" style="width:auto;" id="aat_req" name="requerido" value="1" />
            <span>Requerido</span>
          </label>
          <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="input" style="width:auto;" id="aat_uni" name="unico_por_evidencia" value="1" />
            <span>Único por evidencia</span>
          </label>
          <label class="flex items-center" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="input" style="width:auto;" id="aat_multi" name="multiple" value="1" />
            <span>Múltiple</span>
          </label>
        </div>

        <!-- Campos extra según grupo_storage -->
        <div id="extra_texto" style="display:none;">
          <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
            <div class="form-group">
              <label class="label" for="aat_minlen">Longitud mínima</label>
              <input class="input" id="aat_minlen" name="min_longitud" type="number" min="0" step="1" />
            </div>
            <div class="form-group">
              <label class="label" for="aat_maxlen">Longitud máxima</label>
              <input class="input" id="aat_maxlen" name="max_longitud" type="number" min="1" step="1" />
            </div>
          </div>
        </div>

        <div id="extra_numerico" style="display:none;">
          <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
            <div class="form-group">
              <label class="label" for="aat_minv">Valor mínimo</label>
              <input class="input" id="aat_minv" name="min_valor" type="number" step="any" />
            </div>
            <div class="form-group">
              <label class="label" for="aat_maxv">Valor máximo</label>
              <input class="input" id="aat_maxv" name="max_valor" type="number" step="any" />
            </div>
          </div>
        </div>

        <div id="extra_json" style="display:none;">
          <div class="form-group" style="margin-top:8px;">
            <label class="label" for="aat_opts">Opciones (JSON)</label>
            <textarea class="input" id="aat_opts" name="opciones_json" rows="4" placeholder='Ej.: ["Nacional","Internacional"]'></textarea>
            <p class="text-muted" style="margin:4px 0 0;">Debe ser JSON válido. Para multiselección, marca "Múltiple".</p>
          </div>
        </div>

      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelAddAT">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitAddAT" form="formAddAT">Agregar</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
</style>

<script>
(function(){
  const modal     = document.getElementById('dlgAddAT');
  const backdrop  = document.getElementById('dlgAddATBackdrop');
  const openBtn   = document.getElementById('openModalAgregarAtributo');
  const btnCancel = document.getElementById('btnCancelAddAT');
  const form      = document.getElementById('formAddAT');

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
  if (openBtn)   openBtn.addEventListener('click', open);
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openAddATModal  = open;
  window.closeAddATModal = close;

  // Sugerir slug
  const $nombre = document.getElementById('aat_nombre');
  const $slug   = document.getElementById('aat_slug');
  if ($nombre && $slug) {
    $nombre.addEventListener('input', ()=>{
      if (!$slug.value) {
        $slug.value = $nombre.value
          .trim()
          .toLowerCase()
          .replace(/\s+/g, '_')
          .replace(/[^\w\-]+/g, '');
      }
    });
  }

  // Mostrar campos extra según grupo_storage
  const $tipo = document.getElementById('aat_tipo');
  function showExtra(grupo){
    document.getElementById('extra_texto').style.display   = (grupo === 'texto_corto' || grupo === 'texto_largo') ? '' : 'none';
    document.getElementById('extra_numerico').style.display= (grupo === 'entero' || grupo === 'decimal') ? '' : 'none';
    document.getElementById('extra_json').style.display    = (grupo === 'json') ? '' : 'none';
  }
  if ($tipo) {
    $tipo.addEventListener('change', function(){
      const opt = this.options[this.selectedIndex];
      const grupo = opt ? opt.getAttribute('data-grupo') : null;
      showExtra(grupo);
    });
  }

  // Envío
  const btnSubmit = document.getElementById('btnSubmitAddAT');
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const idTipo = document.getElementById('aat_id_tipo')?.value || document.getElementById('aat_id_tipo')?.getAttribute('value') || '';
    const nombre = document.getElementById('aat_nombre').value.trim();
    const slug   = document.getElementById('aat_slug').value.trim();
    const tipo   = document.getElementById('aat_tipo').value;
    const orden  = document.getElementById('aat_orden').value;

    if (!nombre || !slug || !tipo || !orden) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);
    fd.set('id_tipo_evidencia', <?= (int)$id_tipo_ctx ?>); // refuerza id_tipo
    fetch('atributo-tipo-insert.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(d=>{
        if (d && d.status === 'ok') {
          if (typeof window.closeAddATModal === 'function') window.closeAddATModal();
          if (typeof loadTableAT === 'function') loadTableAT();
          if (window.Swal) Swal.fire({icon:'success', title:'Creado', text:'Atributo creado correctamente.'});
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudo crear el atributo.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al crear el atributo.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
