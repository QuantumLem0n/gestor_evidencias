<?php
// modal-editar-instrumento.php
require_once 'conexion.php';
?>
<div class="dialog-backdrop" id="dlgEditInstBackdrop"></div>
<div class="dialog" id="dlgEditInst" role="dialog" aria-modal="true" aria-labelledby="dlgEditInstTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditInstTitle">Editar instrumento</h3>
      <p class="dialog-desc">Solo puedes cambiar la forma de calificar. La abreviatura y el nombre no se pueden editar.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formEditInst" class="space-y-4" onsubmit="return false;">
        <input type="hidden" id="inst_id" name="id_instrumento" />

        <div class="form-group" style="display:grid; grid-template-columns:1fr 2fr; gap:12px;">
          <div>
            <label class="label">Abreviatura</label>
            <input class="input" id="inst_abrev" type="text" readonly disabled />
          </div>
          <div>
            <label class="label">Nombre completo</label>
            <input class="input" id="inst_nombre" type="text" readonly disabled />
          </div>
        </div>

        <fieldset class="form-group" style="border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:12px;">
          <legend class="label" style="padding:0 6px;">Tipo de calificación</legend>

          <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
            <input type="radio" name="tipo_calificacion" id="inst_tipo_ap" value="APROBACION">
            <span>Aprobación (0 = No aprobado, 1 = Aprobado)</span>
          </label>

          <label style="display:flex; align-items:center; gap:8px;">
            <input type="radio" name="tipo_calificacion" id="inst_tipo_num" value="NUMERICA">
            <span>Numérica</span>
          </label>

          <div id="inst_rango_wrap" style="display:none; margin-top:10px; gap:12px; align-items:flex-end;">
            <div style="display:flex; gap:12px;">
              <div>
                <label class="label" for="inst_min">Calificación mínima</label>
                <input class="input" id="inst_min" name="min_calificacion" type="number" step="0.1" min="-9999" max="9999" placeholder="p.ej. 0.0">
              </div>
              <div>
                <label class="label" for="inst_max">Calificación máxima</label>
                <input class="input" id="inst_max" name="max_calificacion" type="number" step="0.1" min="-9999" max="9999" placeholder="p.ej. 10.0">
              </div>
            </div>
            <p class="text-muted" style="margin-top:6px;">Asegúrate de que <strong>mínimo ≤ máximo</strong>.</p>
          </div>
        </fieldset>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelEditInst">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitEditInst" form="formEditInst">Guardar cambios</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
</style>

<script>
// Mostrar/ocultar rango según tipo
function toggleRango(show){
  document.getElementById('inst_rango_wrap').style.display = show ? 'block' : 'none';
}

// Abrir/cerrar + precarga
(function(){
  const modal     = document.getElementById('dlgEditInst');
  const backdrop  = document.getElementById('dlgEditInstBackdrop');
  const btnCancel = document.getElementById('btnCancelEditInst');

  function open() {
    modal.setAttribute('data-open', 'true');
    backdrop.setAttribute('data-open', 'true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open', 'false');
    backdrop.setAttribute('data-open', 'false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    const form = document.getElementById('formEditInst');
    if (form) form.reset();
    toggleRango(false);
  }

  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  // Radios muestran/ocultan rango
  document.addEventListener('change', function(e){
    if (e.target && e.target.name === 'tipo_calificacion') {
      toggleRango(e.target.value === 'NUMERICA');
    }
  });

  window.openEditarInstrumentoModal = function(id){
    document.getElementById('formEditInst').reset();
    toggleRango(false);
    fetch('instrumento-get.php?id='+encodeURIComponent(id))
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok' && data.instrumento) {
          const t = data.instrumento;
          document.getElementById('inst_id').value   = t.id_instrumento;
          document.getElementById('inst_abrev').value= t.abreviatura || '';
          document.getElementById('inst_nombre').value= t.nombre_completo || '';

          if (t.tipo_calificacion === 'NUMERICA') {
            document.getElementById('inst_tipo_num').checked = true;
            toggleRango(true);
            document.getElementById('inst_min').value = (t.min_calificacion ?? '');
            document.getElementById('inst_max').value = (t.max_calificacion ?? '');
          } else {
            document.getElementById('inst_tipo_ap').checked = true;
            toggleRango(false);
          }

          open();
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudieron cargar los datos.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo cargar el instrumento.'});
      });
  };

  window.closeEditarInstrumentoModal = close;
})();

// Submit edición
(function(){
  const form      = document.getElementById('formEditInst');
  const btnSubmit = document.getElementById('btnSubmitEditInst');

  form.addEventListener('submit', function(e){
    e.preventDefault();

    const id = document.getElementById('inst_id').value;
    const tipo = document.querySelector('input[name="tipo_calificacion"]:checked')?.value || 'APROBACION';

    let min = document.getElementById('inst_min').value;
    let max = document.getElementById('inst_max').value;

    if (tipo === 'NUMERICA') {
      if (min === '' || max === '') {
        if (window.Swal) Swal.fire({icon:'warning', title:'Rango requerido', text:'Ingresa calificación mínima y máxima.'});
        return;
      }
      min = parseFloat(min);
      max = parseFloat(max);
      if (isNaN(min) || isNaN(max) || min > max) {
        if (window.Swal) Swal.fire({icon:'warning', title:'Rango inválido', text:'Verifica que mínimo y máximo sean numéricos y que mínimo ≤ máximo.'});
        return;
      }
    }

    btnSubmit.disabled = true;
    const fd = new FormData(form);
    // Si tipo es APROBACION, aunque viaje min/max, backend los ignora.

    fetch('instrumento-update.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(data=>{
        if (data && data.status === 'ok') {
          if (typeof window.closeEditarInstrumentoModal === 'function') window.closeEditarInstrumentoModal();
          if (typeof loadTableINS === 'function') loadTableINS();
          if (window.Swal) Swal.fire({icon:'success', title:'Actualizado', text:'Instrumento actualizado.'});
        } else {
          const msg = (data && data.message) ? data.message : 'No se pudo actualizar.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'Error al actualizar el instrumento.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
