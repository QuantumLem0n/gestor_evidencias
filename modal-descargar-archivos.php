<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!-- ===== Modal Descargar Evidencias Aprobadas ===== -->
<div class="dialog-backdrop" id="dlgBackdropDesc"></div>
<div class="dialog" id="dlgDescargar" role="dialog" aria-modal="true" aria-labelledby="dlgDescTitle" data-open="false">
  <div class="dialog-card" style="max-width:720px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgDescTitle">Descargar evidencias</h3>
      <p class="dialog-desc" id="dlgDescSub">Selecciona qué evidencias deseas descargar.</p>
    </div>

    <div class="dialog-body">
      <div id="descAlert" class="text-muted-foreground" style="margin-bottom:8px;"></div>

      <div id="descControles" style="display:none; margin-bottom:8px; display:flex; gap:12px; align-items:center;">
        <span class="text-sm">Seleccionar:</span>
        <button class="btn" type="button" id="btnSelTodas">Todas</button>
        <button class="btn" type="button" id="btnSelNinguna">Ninguna</button>
        <span id="descCount" class="text-sm" style="margin-left:auto;">0 seleccionadas</span>
      </div>

      <div id="listaEvidenciasDesc" style="max-height:380px; overflow:auto; border:1px solid var(--border,#e5e7eb); border-radius:12px; padding:10px;">
        <p class="text-muted-foreground" id="descLoading">Cargando…</p>
      </div>
    </div>

    <div class="dialog-actions">
      <button class="btn" type="button" id="btnCancelarDesc">Cancelar</button>
      <button class="btn btn-success" type="button" id="btnDescargarSeleccion" disabled>Descargar</button>
    </div>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('dlgDescargar');
  const backdrop = document.getElementById('dlgBackdropDesc');
  const btnCancelar = document.getElementById('btnCancelarDesc');
  const btnDescargar = document.getElementById('btnDescargarSeleccion');
  const btnSelTodas = document.getElementById('btnSelTodas');
  const btnSelNinguna = document.getElementById('btnSelNinguna');
  const contLista = document.getElementById('listaEvidenciasDesc');
  const descAlert = document.getElementById('descAlert');
  const descControles = document.getElementById('descControles');
  const descCount = document.getElementById('descCount');
  const sub = document.getElementById('dlgDescSub');

  let currentInstId = 0;

  function lock(lock){ document.documentElement.style.overflow = lock ? 'hidden' : ''; }
  function open(){ modal.setAttribute('data-open','true'); backdrop.setAttribute('data-open','true'); lock(true); }
  function close(){ modal.setAttribute('data-open','false'); backdrop.setAttribute('data-open','false'); lock(false); }

  if (btnCancelar) btnCancelar.addEventListener('click', close);
  if (backdrop) backdrop.addEventListener('click', close);

  function updateCount(){
    const checks = contLista.querySelectorAll('input[type=checkbox][name="ev[]"]');
    let n = 0; checks.forEach(ch=>{ if (ch.checked) n++; });
    descCount.textContent = n + ' seleccionada' + (n===1?'':'s');
    btnDescargar.disabled = n === 0;
  }

  btnSelTodas?.addEventListener('click', ()=>{
    contLista.querySelectorAll('input[type=checkbox][name="ev[]"]').forEach(ch=>{ ch.checked = true; });
    updateCount();
  });
  btnSelNinguna?.addEventListener('click', ()=>{
    contLista.querySelectorAll('input[type=checkbox][name="ev[]"]').forEach(ch=>{ ch.checked = false; });
    updateCount();
  });

  btnDescargar?.addEventListener('click', ()=>{
    const ids = Array.from(contLista.querySelectorAll('input[type=checkbox][name="ev[]"]:checked'))
                  .map(ch=>ch.value).filter(Boolean);
    if (!ids.length) return;
    // descarga en nueva pestaña
    const url = 'descargas-api.php?action=zip&instrumento='+encodeURIComponent(currentInstId)+'&ids='+encodeURIComponent(ids.join(','));
    window.open(url, '_blank');
    close();
  });

  function renderList(items){
    contLista.innerHTML = '';
    if (!items || !items.length) {
      descControles.style.display = 'none';
      btnDescargar.disabled = true;
      contLista.innerHTML = '<p class="text-muted-foreground">No tienes evidencias aprobadas para ese instrumento.</p>';
      return;
    }
    const ul = document.createElement('ul');
    ul.style.listStyle = 'none';
    ul.style.margin = '0';
    ul.style.padding = '0';
    items.forEach(it=>{
      const li = document.createElement('li');
      li.style.padding = '6px 8px';
      li.style.borderBottom = '1px solid var(--muted,#eee)';
      const id = String(it.id);
      const titulo = (it.titulo || 'Sin título');
      const chk = document.createElement('input');
      chk.type = 'checkbox';
      chk.name = 'ev[]';
      chk.value = id;
      chk.checked = true;
      chk.addEventListener('change', updateCount);

      const label = document.createElement('label');
      label.style.display = 'inline-flex';
      label.style.alignItems = 'center';
      label.style.gap = '8px';
      label.appendChild(chk);
      const span = document.createElement('span');
      span.textContent = titulo;
      label.appendChild(span);

      li.appendChild(label);
      ul.appendChild(li);
    });
    contLista.appendChild(ul);
    descControles.style.display = 'flex';
    updateCount();
  }

  async function loadAprobadas(instId, instNameShown){
    contLista.innerHTML = '<p class="text-muted-foreground" id="descLoading">Cargando…</p>';
    btnDescargar.disabled = true;
    descControles.style.display = 'none';
    descAlert.textContent = '';
    try {
      const resp = await fetch('descargas-api.php?action=list&instrumento='+encodeURIComponent(instId), {cache:'no-store'});
      const ct = resp.headers.get('Content-Type') || '';
      if (!resp.ok) {
        const txt = await resp.text();
        throw new Error(txt || ('HTTP '+resp.status));
      }
      if (!ct.includes('application/json')) {
        const txt = await resp.text();
        throw new Error(txt || 'La respuesta no es JSON');
      }
      const j = await resp.json();
      if (j.status !== 'ok') {
        throw new Error(j.message || 'Error desconocido');
      }
      if (j.items && j.items.length) {
        sub.textContent = 'Las evidencias aprobadas para ' + (instNameShown || j.instrumento?.name || '') + ' son:';
      } else {
        sub.textContent = 'Selecciona qué evidencias deseas descargar.';
        descAlert.textContent = 'No tienes evidencias aprobadas para ese instrumento';
      }
      renderList(j.items || []);
    } catch (e) {
      contLista.innerHTML = '<p class="text-danger">Error al cargar la lista.</p>';
      // Muestra detalle en alerta ligera para depurar rápido
      descAlert.textContent = (e && e.message) ? String(e.message) : 'Error al cargar la lista.';
      btnDescargar.disabled = true;
      descControles.style.display = 'none';
      console.error('Descargas API error:', e);
    }
  }

  // Exponer función global para abrir el modal desde la página principal
  window.__openDescModal = function(instId, instName){
    currentInstId = instId;
    open();
    loadAprobadas(instId, instName || '');
  };
})();
</script>
