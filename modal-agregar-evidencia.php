<?php
// Modal para agregar evidencia (docentes y admin).
require_once 'conexion.php';

// Tipos de evidencia
$tipos = [];
$qT = "SELECT id_tipo_evidencia, nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo ASC";
if ($resT = mysqli_query($conn, $qT)) {
  while ($row = mysqli_fetch_assoc($resT)) $tipos[] = $row;
}
?>
<div class="dialog-backdrop" id="dlgAddEviBackdrop"></div>
<div class="dialog" id="dlgAddEvi" role="dialog" aria-modal="true" aria-labelledby="dlgAddEviTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:760px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgAddEviTitle">Agregar evidencia</h3>
      <p class="dialog-desc">Selecciona el tipo, escribe un título y sube el archivo (PDF o imagen, máx. 10 MB).</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formAddEvi" class="space-y-4" onsubmit="return false;" enctype="multipart/form-data">
        <div class="form-group">
          <label class="label" for="ae_titulo">Título de la evidencia</label>
          <input class="input" id="ae_titulo" name="titulo" type="text" maxlength="255" placeholder="Ej. Libro XYZ / Certificado de conferencia..." required />
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="ae_tipo">Tipo de evidencia</label>
            <select class="input select-white" id="ae_tipo" name="id_tipo_evidencia" required>
              <option value="" selected disabled>Selecciona un tipo…</option>
              <?php foreach ($tipos as $t): ?>
                <option value="<?= (int)$t['id_tipo_evidencia'] ?>"><?= htmlspecialchars($t['nombre_tipo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label" for="ae_archivo">Archivo</label>
            <input class="input" id="ae_archivo" name="archivo" type="file" accept="application/pdf,image/*" required />
            <p class="text-muted" style="margin:4px 0 0;">PDF / JPG / PNG / WEBP &nbsp;•&nbsp; Máx. 10 MB</p>
          </div>
        </div>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelAddEvi">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitAddEvi" form="formAddEvi">Agregar</button>
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
  const modal     = document.getElementById('dlgAddEvi');
  const backdrop  = document.getElementById('dlgAddEviBackdrop');
  const openBtn   = document.getElementById('openModalAgregarEvidencia');
  const btnCancel = document.getElementById('btnCancelAddEvi');
  const form      = document.getElementById('formAddEvi');

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
  }

  if (openBtn)   openBtn.addEventListener('click', open);
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openAddEviModal  = open;
  window.closeAddEviModal = close;

  const btnSubmit = document.getElementById('btnSubmitAddEvi');
  form.addEventListener('submit', function(e){
    e.preventDefault();

    const titulo = document.getElementById('ae_titulo').value.trim();
    const tipo   = document.getElementById('ae_tipo').value;
    const file   = document.getElementById('ae_archivo').files[0];

    if (!titulo || !tipo || !file) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('evidencia-insert.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(d=>{
        if (d && d.status === 'ok') {
          if (typeof window.closeAddEviModal === 'function') window.closeAddEviModal();
          if (typeof loadTableE === 'function') loadTableE();
          if (window.Swal) Swal.fire({icon:'success', title:'Creada', text:'La evidencia se creó correctamente.'});
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudo crear la evidencia.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al crear la evidencia.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
