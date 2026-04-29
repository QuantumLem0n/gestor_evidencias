<?php
// Modal para editar evidencia (solo docentes sobre sus evidencias)
require_once 'conexion.php';

// Tipos de evidencia
$tipos = [];
$qT = "SELECT id_tipo_evidencia, nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo ASC";
if ($resT = mysqli_query($conn, $qT)) {
  while ($row = mysqli_fetch_assoc($resT)) $tipos[] = $row;
}
?>
<div class="dialog-backdrop" id="dlgEditEviBackdrop"></div>
<div class="dialog" id="dlgEditEvi" role="dialog" aria-modal="true" aria-labelledby="dlgEditEviTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:760px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditEviTitle">Editar evidencia</h3>
      <p class="dialog-desc">Puedes cambiar el título, el tipo y (opcional) reemplazar el archivo.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formEditEvi" class="space-y-4" onsubmit="return false;" enctype="multipart/form-data">
        <input type="hidden" id="ee_id" name="id_evidencia" />
        <input type="hidden" id="ee_archivo_actual" name="archivo_actual" />

        <div class="form-group">
          <label class="label" for="ee_titulo">Título</label>
          <input class="input" id="ee_titulo" name="titulo" type="text" maxlength="255" required />
        </div>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="ee_tipo">Tipo de evidencia</label>
            <select class="input select-white" id="ee_tipo" name="id_tipo_evidencia" required>
              <?php foreach ($tipos as $t): ?>
                <option value="<?= (int)$t['id_tipo_evidencia'] ?>"><?= htmlspecialchars($t['nombre_tipo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="label" for="ee_archivo">Reemplazar archivo (opcional)</label>
            <input class="input" id="ee_archivo" name="archivo" type="file" accept="application/pdf,image/*" />
            <p class="text-muted" style="margin:4px 0 0;">Si eliges un archivo, se reemplazará el actual. Máx. 10 MB.</p>
          </div>
        </div>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelEditEvi">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitEditEvi" form="formEditEvi">Guardar cambios</button>
    </div>
  </div>
</div>

<script>
(function(){
  const modal     = document.getElementById('dlgEditEvi');
  const backdrop  = document.getElementById('dlgEditEviBackdrop');
  const btnCancel = document.getElementById('btnCancelEditEvi');
  const form      = document.getElementById('formEditEvi');

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

  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  // Exponer
  window.openEditarEvidenciaModal = function(id){
    form.reset();
    fetch('evidencia-get.php?id='+encodeURIComponent(id))
      .then(r=>r.json())
      .then(d=>{
        if (d && d.status === 'ok' && d.evidencia) {
          const e = d.evidencia;
          document.getElementById('ee_id').value             = e.id_evidencia;
          document.getElementById('ee_titulo').value         = e.titulo || '';
          document.getElementById('ee_tipo').value           = e.id_tipo_evidencia || '';
          document.getElementById('ee_archivo_actual').value = e.archivo || '';
          open();
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudo cargar la evidencia.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al cargar datos.'});
      });
  };

  window.closeEditEviModal = close;

  const btnSubmit = document.getElementById('btnSubmitEditEvi');
  form.addEventListener('submit', function(e){
    e.preventDefault();

    const id    = document.getElementById('ee_id').value;
    const titulo= document.getElementById('ee_titulo').value.trim();
    const tipo  = document.getElementById('ee_tipo').value;
    if (!id || !titulo || !tipo) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);

    fetch('evidencia-update.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(d=>{
        if (d && d.status === 'ok') {
          if (typeof window.closeEditEviModal === 'function') window.closeEditEviModal();
          if (typeof loadTableE === 'function') loadTableE();
          if (window.Swal) Swal.fire({icon:'success', title:'Actualizada', text:'Evidencia actualizada.'});
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudo actualizar.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al actualizar evidencia.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
