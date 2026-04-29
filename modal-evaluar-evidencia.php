<?php
// modal-evaluar-evidencia.php
require_once 'conexion.php';
?>
<div class="dialog-backdrop" id="dlgEvalBackdrop"></div>
<div class="dialog" id="dlgEval" role="dialog" aria-modal="true" aria-labelledby="dlgEvalTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:1000px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEvalTitle">Evaluar evidencia</h3>
      <p class="dialog-desc">Revisa los atributos capturados y asigna calificaciones por instrumento.</p>
    </div>

    <div class="dialog-body dialog-body-scroll" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
      <!-- Atributos -->
      <section class="card">
        <div class="card-content">
          <h4 style="margin-bottom:8px;">Atributos capturados</h4>
          <div id="evalAtributos" class="space-y-2" style="max-height: 60vh; overflow:auto;"></div>
        </div>
      </section>

      <!-- Instrumentos -->
      <section class="card">
        <div class="card-content">
          <h4 style="margin-bottom:8px;">Evaluación por instrumento</h4>
          <div id="evalInstruments" class="space-y-3"></div>
        </div>
      </section>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCloseEval">Cerrar</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
.eval-block { border:1px solid var(--border,#e5e7eb); border-radius:12px; padding:12px; }
.eval-title { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.eval-meta { font-size:12px; color:var(--muted-foreground,#666); }
</style>
<script>
(function(){
  const modal    = document.getElementById('dlgEval');
  const backdrop = document.getElementById('dlgEvalBackdrop');
  const btnClose = document.getElementById('btnCloseEval');

  function open() {
    modal.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    document.getElementById('evalAtributos').innerHTML = '';
    document.getElementById('evalInstruments').innerHTML = '';
    document.getElementById('dlgEvalTitle').textContent = 'Evaluar evidencia';
  }

  if (btnClose) btnClose.addEventListener('click', close);
  if (backdrop) backdrop.addEventListener('click', close);

  /* ========= Atributos: cargar y renderizar =========
     Usa primero 'valores_render' generado por el backend.
     Si no existe, hace fallback a escoger el primer valor no vacío
     entre valor_texto, valor_largo, valor_int, valor_decimal, etc.
     OJO: no hacemos filter(Boolean) para no perder ceros "0".
  */
  function loadAtributos(eviId){
    const wrap = document.getElementById('evalAtributos');
    wrap.innerHTML = '<p class="text-muted">Cargando atributos…</p>';
    fetch('evidencia-atributos-get.php?id=' + encodeURIComponent(eviId))
      .then(r=>r.json())
      .then(j=>{
        if (!j || j.status !== 'ok') { wrap.innerHTML = '<p class="text-muted">No se pudieron cargar los atributos.</p>'; return; }
        const attrs = j.atributos || [];
        if (!attrs.length) { wrap.innerHTML = '<p class="text-muted">Sin atributos.</p>'; return; }

        const frag = document.createDocumentFragment();
        attrs.forEach(a=>{
          const box = document.createElement('div');
          box.className = 'eval-block';

          // Preferimos los valores ya renderizados por el backend
          let valores = Array.isArray(a.valores_render) ? a.valores_render.slice() : [];

          // Fallback si no vino valores_render
          if (!valores.length && Array.isArray(a.valores)) {
            valores = a.valores.map(v => pickRawValue(v)).map(String);
          }

          // NO usar filter(Boolean) para no perder "0"
          const chips = (valores.length)
            ? valores.map(v => `<span class="badge" style="margin-right:6px;">${escapeHtml(v)}</span>`).join('')
            : '<span class="text-muted">Sin valor</span>';

          box.innerHTML = `
            <div class="eval-title">
              <strong>${escapeHtml(a.nombre_atributo || ('Atributo #'+a.id_ate))}</strong>
            </div>
            <div class="eval-meta">Tipo: ${escapeHtml(a.nombre_tipo || '')}</div>
            <div style="margin-top:6px;">${chips}</div>
          `;
          frag.appendChild(box);
        });

        wrap.innerHTML = ''; wrap.appendChild(frag);
      })
      .catch(()=>{ wrap.innerHTML = '<p class="text-muted">Error al cargar.</p>'; });
  }

  /* ========= Instrumentos: cargar y renderizar ========= */
  function loadInstruments(eviId){
    const wrap = document.getElementById('evalInstruments');
    wrap.innerHTML = '<p class="text-muted">Cargando instrumentos…</p>';
    fetch('evaluacion-get.php?id=' + encodeURIComponent(eviId))
      .then(r=>r.json())
      .then(j=>{
        if (!j || j.status !== 'ok') { wrap.innerHTML = '<p class="text-muted">No se pudieron cargar los instrumentos.</p>'; return; }
        const ins = j.instrumentos || [];
        if (!ins.length) { wrap.innerHTML = '<p class="text-muted">Esta evidencia no requiere instrumentos.</p>'; return; }
        const frag = document.createDocumentFragment();
        ins.forEach(it=>{
          const idInst = it.id_instrumento;
          const esNum  = !!it.es_numerico;
          const min    = (it.cal_min ?? 0);
          const max    = (it.cal_max ?? 10);
          const res    = (typeof it.resultado === 'number') ? it.resultado : null;
          const com    = it.comentario || '';
          const ab     = it.abreviatura || ('INS#'+idInst);
          const nom    = it.nombre_completo || '';

          const box = document.createElement('div');
          box.className = 'eval-block';
          box.innerHTML = `
            <div class="eval-title">
              <div><strong>${escapeHtml(ab)}</strong> <span class="eval-meta">${escapeHtml(nom)}</span></div>
              <div class="eval-meta">${esNum ? `Rango: ${min} – ${max}` : `Aprobación: 1=Sí, 0=No`}</div>
            </div>
            <div style="display:flex; gap:8px; align-items:center; margin-top:8px; flex-wrap:wrap;">
              ${
                esNum
                ? `<input type="number" class="input" id="res_${idInst}" placeholder="calificación"
                          min="${min}" max="${max}" step="0.1" style="width:120px;" ${res!==null?`value="${res}"`:''}>`
                : `<select id="res_${idInst}" class="input" style="width:120px;">
                     <option value="" ${res===null?'selected':''}>—</option>
                     <option value="1" ${res===1?'selected':''}>Aprobado (1)</option>
                     <option value="0" ${res===0?'selected':''}>No aprobado (0)</option>
                   </select>`
              }
              <input type="text" class="input" id="com_${idInst}" placeholder="Comentario (opcional)" style="flex:1;" ${com?`value="${escapeAttr(com)}"`:''}>
              <button class="btn btn-primary" type="button" onclick="saveEval(${eviId}, ${idInst}, ${esNum?1:0}, ${min}, ${max})">Guardar</button>
            </div>
            <div class="eval-meta" id="meta_${idInst}" style="margin-top:6px;">
              ${it.actualizado_en ? `Última actualización: ${escapeHtml(it.actualizado_en)}` : (it.calificado_en ? `Creado: ${escapeHtml(it.calificado_en)}` : 'Sin calificación')}
            </div>
          `;
          frag.appendChild(box);
        });
        wrap.innerHTML = ''; wrap.appendChild(frag);
      })
      .catch(()=>{ wrap.innerHTML = '<p class="text-muted">Error al cargar.</p>'; });
  }

  /* ========= Guardar evaluación (por instrumento) ========= */
  window.saveEval = function(eviId, instId, esNum, min, max){
    const $res = document.getElementById('res_'+instId);
    const $com = document.getElementById('com_'+instId);
    let valor = $res ? $res.value.trim() : '';
    let comentario = $com ? $com.value.trim() : '';

    if (valor === '') { if (window.Swal){ Swal.fire({icon:'warning',title:'Dato requerido',text:'Asigna un resultado.'}); } return; }
    if (esNum) {
      const num = parseFloat(valor);
      if (isNaN(num) || num < min || num > max) { if (window.Swal){ Swal.fire({icon:'warning',title:'Rango inválido',text:`Debe ser entre ${min} y ${max}.`}); } return; }
      valor = num;
    } else {
      if (valor !== '0' && valor !== '1') { if (window.Swal){ Swal.fire({icon:'warning',title:'Valor inválido',text:'Usa 1 (aprobado) o 0 (no aprobado).'}); } return; }
      valor = parseInt(valor,10);
    }

    const fd = new FormData();
    fd.append('id_evidencia',  eviId);
    fd.append('id_instrumento',instId);
    fd.append('resultado',     valor);
    fd.append('comentario',    comentario);

    fetch('evaluacion-save.php', {method:'POST', body:fd})
      .then(r=>r.json())
      .then(j=>{
        if (j && j.status === 'ok') {
          if (window.Swal) Swal.fire({icon:'success', title:'Guardado', text:'Calificación registrada.'});
          document.getElementById('meta_'+instId).textContent = 'Actualizado hace un momento';
          if (typeof loadTablaEval === 'function') loadTablaEval(); // refresca la tabla principal si existe
        } else {
          if (window.Swal) Swal.fire({icon:'error', title:'Error', text: (j && j.message) ? j.message : 'No se pudo guardar.'});
        }
      })
      .catch(()=>{ if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo guardar la calificación.'}); });
  };

  /* ========= Abrir modal público ========= */
  window.evalOpenModal = function(eviId){
    document.getElementById('dlgEvalTitle').textContent = 'Evaluar evidencia #' + eviId;
    open();
    loadAtributos(eviId);
    loadInstruments(eviId);
  };

  /* ========= Helpers ========= */
  function pickRawValue(v){
    // Devuelve la primera columna con dato no nulo/ni vacío (incluye 0/0.0/false)
    const keys = ['valor_texto','valor_largo','valor_int','valor_decimal','valor_fecha','valor_bool','valor_archivo','valor_json'];
    for (let k of keys) {
      if (Object.prototype.hasOwnProperty.call(v, k) && v[k] !== null && v[k] !== '') return v[k];
    }
    return '';
  }
  function escapeHtml(s){ return (''+(s??'')).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }
})();
</script>
