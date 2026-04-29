<?php
// modal-editar-evidencia-detalle.php
?>
<div class="dialog-backdrop" id="dlgEditAtribBackdrop"></div>
<div class="dialog" id="dlgEditAtrib" role="dialog" aria-modal="true" aria-labelledby="dlgEditAtribTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:920px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEditAtribTitle">Editar atributos</h3>
      <p class="dialog-desc">Completa o actualiza la información de los atributos de esta evidencia.</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formEditAtrib" class="space-y-4" onsubmit="return false;">
        <input type="hidden" name="id_evidencia" id="ea_id_evidencia" />
        <div id="ea_fields" class="space-y-6"></div>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="ea_btnCancel">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="ea_btnSave" form="formEditAtrib">Guardar</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.attr-group { border:1px solid var(--border); border-radius:12px; padding:12px; }
.attr-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
.attr-rep { display:flex; flex-direction:column; gap:10px; }
.attr-row { display:flex; gap:10px; align-items:center; }
.attr-row .input, .attr-row textarea, .attr-row select { flex:1; }
.attr-actions { display:flex; gap:8px; }
.text-hint { font-size:12px; color: var(--muted-foreground, #666); }
</style>

<script>
(function(){
  const dlg = document.getElementById('dlgEditAtrib');
  const backdrop = document.getElementById('dlgEditAtribBackdrop');
  const form = document.getElementById('formEditAtrib');
  const fields = document.getElementById('ea_fields');
  const btnCancel = document.getElementById('ea_btnCancel');
  const btnSave = document.getElementById('ea_btnSave');

  function open(){ dlg.setAttribute('data-open','true'); backdrop.setAttribute('data-open','true'); document.documentElement.style.overflow='hidden'; }
  function close(){ dlg.setAttribute('data-open','false'); backdrop.setAttribute('data-open','false'); document.documentElement.style.overflow=''; form.reset(); fields.innerHTML=''; }

  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  function renderAttr(a){
    const aid = a.id_ate;
    const gs  = a.grupo_storage;
    const multi = Number(a.multiple) === 1;
    const unico = Number(a.unico_por_evidencia) === 1;
    const req = Number(a.requerido) === 1;

    const wrap = document.createElement('div');
    wrap.className = 'attr-group';
    wrap.dataset.ate = aid;

    const head = document.createElement('div');
    head.className = 'attr-head';
    head.innerHTML = `
      <div>
        <div class="font-medium">${escapeHtml(a.nombre_atributo)}</div>
        <div class="text-hint">${escapeHtml(a.descripcion || '')}</div>
      </div>
      <div class="attr-actions"></div>
    `;
    wrap.appendChild(head);

    const rep = document.createElement('div');
    rep.className = 'attr-rep';
    rep.dataset.group = gs;
    rep.dataset.ate = aid;
    wrap.appendChild(rep);

    // acciones (agregar para multiple)
    const actions = head.querySelector('.attr-actions');
    if (multi) {
      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'btn';
      addBtn.textContent = '+ Agregar';
      addBtn.addEventListener('click', ()=> addRow(rep, gs, aid, a));
      actions.appendChild(addBtn);
    }
    // Render filas existentes (valores)
    if (Array.isArray(a.valores) && a.valores.length) {
      a.valores.forEach(v => addRow(rep, gs, aid, a, v));
    } else {
      addRow(rep, gs, aid, a, null);
    }

    // Hint de restricciones
    const hint = [];
    if (req) hint.push('Requerido');
    if (unico) hint.push('Único');
    if (a.min_longitud) hint.push('Min. longitud: '+a.min_longitud);
    if (a.max_longitud) hint.push('Max. longitud: '+a.max_longitud);
    if (a.min_valor !== null && a.min_valor !== undefined) hint.push('Min. valor: '+a.min_valor);
    if (a.max_valor !== null && a.max_valor !== undefined) hint.push('Max. valor: '+a.max_valor);
    if (a.validador_regex) hint.push('Validación especial');

    if (hint.length) {
      const p = document.createElement('div');
      p.className = 'text-hint';
      p.style.marginTop = '8px';
      p.textContent = hint.join(' • ');
      wrap.appendChild(p);
    }

    fields.appendChild(wrap);
  }

  function addRow(rep, gs, aid, a, v){
    const row = document.createElement('div');
    row.className = 'attr-row';
    const idx = (rep.children.length + 1);

    // indice hidden
    const idxInput = document.createElement('input');
    idxInput.type = 'hidden';
    idxInput.name = `indice[${aid}][]`;
    idxInput.value = v ? (v.indice || idx) : idx;
    row.appendChild(idxInput);

    // input según grupo
    if (gs === 'texto_corto') {
      const i = document.createElement('input');
      i.className = 'input';
      i.type = 'text';
      if (a.max_longitud) i.maxLength = Number(a.max_longitud);
      i.name = `valor_texto[${aid}][]`;
      i.value = v ? (v.valor_texto || '') : '';
      row.appendChild(i);
    } else if (gs === 'texto_largo') {
      const t = document.createElement('textarea');
      t.className = 'input';
      t.rows = 3;
      t.name = `valor_largo[${aid}][]`;
      t.value = v ? (v.valor_largo || '') : '';
      row.appendChild(t);
    } else if (gs === 'entero') {
      const n = document.createElement('input');
      n.className = 'input';
      n.type = 'number';
      n.step = '1';
      if (a.min_valor !== null) n.min = a.min_valor;
      if (a.max_valor !== null) n.max = a.max_valor;
      n.name = `valor_int[${aid}][]`;
      n.value = v && v.valor_int !== null ? v.valor_int : '';
      row.appendChild(n);
    } else if (gs === 'decimal') {
      const d = document.createElement('input');
      d.className = 'input';
      d.type = 'number';
      d.step = 'any';
      if (a.min_valor !== null) d.min = a.min_valor;
      if (a.max_valor !== null) d.max = a.max_valor;
      d.name = `valor_decimal[${aid}][]`;
      d.value = v && v.valor_decimal !== null ? v.valor_decimal : '';
      row.appendChild(d);
    } else if (gs === 'fecha') {
      const f = document.createElement('input');
      f.className = 'input';
      f.type = 'date';
      f.name = `valor_fecha[${aid}][]`;
      f.value = v && v.valor_fecha ? v.valor_fecha : '';
      row.appendChild(f);
    } else if (gs === 'booleano') {
      const s = document.createElement('select');
      s.className = 'input select-white';
      s.name = `valor_bool[${aid}][]`;
      s.innerHTML = `<option value="">—</option>
                     <option value="1">Sí</option>
                     <option value="0">No</option>`;
      const val = v ? (v.valor_bool === null ? '' : String(Number(v.valor_bool))) : '';
      s.value = val;
      row.appendChild(s);
    } else if (gs === 'archivo') {
      const file = document.createElement('input');
      file.className = 'input';
      file.type = 'file';
      file.accept = ".pdf,image/*";
      file.name = `valor_archivo[${aid}][]`;
      row.appendChild(file);

      if (v && v.valor_archivo) {
        const aTag = document.createElement('a');
        aTag.className = 'btn';
        aTag.style.marginLeft = '6px';
        aTag.href = 'uploads/files/'+encodeURIComponent(v.valor_archivo);
        aTag.setAttribute('download', v.valor_archivo);
        aTag.textContent = 'Descargar actual';
        row.appendChild(aTag);
      }
    } else if (gs === 'json') {
      const jt = document.createElement('textarea');
      jt.className = 'input';
      jt.rows = 4;
      jt.name = `valor_json[${aid}][]`;
      jt.value = v ? (v.valor_json || '') : '';
      row.appendChild(jt);
    } else {
      const u = document.createElement('input');
      u.className = 'input';
      u.type = 'text';
      u.name = `valor_texto[${aid}][]`;
      u.value = v ? (v.valor_texto || '') : '';
      row.appendChild(u);
    }

    // Botón eliminar fila (si es multiple)
    if (Number(a.multiple) === 1) {
      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'btn';
      del.title = 'Quitar';
      del.innerHTML = '—';
      del.addEventListener('click', ()=>row.remove());
      row.appendChild(del);
    }

    rep.appendChild(row);
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  // Abrir modal y cargar datos
  window.openEditAtributosModal = function(evidId){
    document.getElementById('ea_id_evidencia').value = evidId;
    fields.innerHTML = '';
    fetch('evidencia-detalle-get.php?id='+encodeURIComponent(evidId))
      .then(r=>r.json())
      .then(data=>{
        if (!data || data.status !== 'ok') throw new Error(data?.message || 'No se pudo cargar.');
        const attrs = data.atributos || [];
        if (!attrs.length) {
          fields.innerHTML = '<div class="alert">Este tipo no tiene atributos definidos.</div>';
        } else {
          attrs.forEach(renderAttr);
        }
        open();
      })
      .catch(err=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text: err.message || 'No se pudo cargar.'});
      });
  };

  // Guardar
  form.addEventListener('submit', function(e){
    e.preventDefault();
    btnSave.disabled = true;

    const fd = new FormData(form);
    fetch('evidencia-detalle-save.php', { method:'POST', body: fd })
      .then(r=>r.json())
      .then(j=>{
        if (j && j.status === 'ok') {
          if (window.Swal) Swal.fire({icon:'success', title:'Guardado', text:'Datos actualizados.'});
          if (typeof loadDetalle === 'function') loadDetalle();
          close();
        } else {
          const msg = (j && j.message) ? j.message : 'No se pudo guardar.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(()=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo guardar.'});
      })
      .finally(()=>{ btnSave.disabled = false; });
  });
})();
</script>
