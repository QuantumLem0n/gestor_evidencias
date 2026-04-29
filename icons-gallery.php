<?php
// iconos-galeria.php
require_once __DIR__ . '/conexion.php';

// Traer todos los iconos
$iconos = [];
$q = "SELECT id_icono, descripcion, imagen FROM iconos ORDER BY descripcion ASC";
if ($res = mysqli_query($conn, $q)) {
  while ($row = mysqli_fetch_assoc($res)) { $iconos[] = $row; }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Galería de Iconos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root { --icon-size: 28px; --gap: 14px; --card-bg: #fff; --card-bd: #e5e7eb; }
  body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, Inter, Arial, sans-serif; margin: 0; background:#f8fafc; color:#0f172a; }
  header { position: sticky; top:0; background:#f8fafc; padding: 12px 16px; border-bottom:1px solid #e5e7eb; display:flex; gap:12px; align-items:center; flex-wrap:wrap;}
  .grid { padding:16px; display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: var(--gap); }
  .card { background: var(--card-bg); border:1px solid var(--card-bd); border-radius:14px; padding:12px; display:flex; gap:10px; align-items:center; }
  .icon { width: var(--icon-size); height: var(--icon-size); display:inline-flex; align-items:center; justify-content:center; color: var(--icon-color, #0f172a); }
  .meta { display:flex; flex-direction:column; gap:3px; }
  .meta b { font-size:14px; }
  .meta small { color:#64748b; }
  .toolbar { display:flex; gap:10px; align-items:center; margin-left:auto; }
  input[type="search"]{ padding:8px 10px; border:1px solid #cbd5e1; border-radius:10px; min-width:220px; }
  select, input[type="color"] { padding:6px 8px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; }
  .copy { margin-left:auto; border:1px solid #cbd5e1; background:#fff; padding:6px 10px; border-radius:10px; cursor:pointer; }
  footer { padding:16px; text-align:center; color:#64748b; }
  .muted { color:#64748b; }
  .small { font-size:12px; }
  .nowrap { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px; }
</style>
</head>
<body>

<header>
  <strong>Galería de Iconos</strong>
  <div class="toolbar">
    <input id="buscador" type="search" placeholder="Buscar por descripción o ID…">
    <label class="small muted">Tamaño:
      <select id="tam">
        <option value="20">20px</option>
        <option value="24" selected>24px</option>
        <option value="28">28px</option>
        <option value="32">32px</option>
        <option value="40">40px</option>
      </select>
    </label>
    <label class="small muted">Color:
      <input id="color" type="color" value="#0f172a">
    </label>
  </div>
</header>

<section class="grid" id="grid">
  <?php foreach ($iconos as $ico): ?>
    <article class="card" data-id="<?php echo (int)$ico['id_icono']; ?>" data-desc="<?php echo htmlspecialchars($ico['descripcion']); ?>">
      <div class="icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none">
          <?php // imprimimos el contenido SVG almacenado (paths, rects, circles, etc.)
            echo $ico['imagen'];
          ?>
        </svg>
      </div>
      <div class="meta">
        <b class="nowrap">#<?php echo (int)$ico['id_icono']; ?> · <?php echo htmlspecialchars($ico['descripcion']); ?></b>
        <small class="muted nowrap">&lt;svg viewBox="0 0 24 24" fill="none" stroke="currentColor"&gt;…&lt;/svg&gt;</small>
      </div>
      <button class="copy" title="Copiar SVG" data-svg="<?php echo htmlspecialchars('<svg viewBox="0 0 24 24" fill="none" stroke=\'currentColor\'>'.$ico['imagen'].'</svg>'); ?>">Copiar SVG</button>
    </article>
  <?php endforeach; ?>
</section>

<footer>
  <span class="small">Tip: los iconos heredan el color de <code>currentColor</code>; cambia el color arriba o en CSS.</span>
</footer>

<script>
  const grid = document.getElementById('grid');
  const buscador = document.getElementById('buscador');
  const tam = document.getElementById('tam');
  const color = document.getElementById('color');

  // Filtro por texto
  buscador.addEventListener('input', () => {
    const q = buscador.value.toLowerCase().trim();
    for (const card of grid.querySelectorAll('.card')) {
      const hay = (card.dataset.desc.toLowerCase().includes(q) || card.dataset.id.includes(q));
      card.style.display = hay ? '' : 'none';
    }
  });

  // Tamaño
  const applySize = () => {
    const size = tam.value + 'px';
    document.documentElement.style.setProperty('--icon-size', size);
    for (const svg of grid.querySelectorAll('svg')) {
      svg.setAttribute('width', tam.value);
      svg.setAttribute('height', tam.value);
    }
  };
  tam.addEventListener('change', applySize);
  applySize();

  // Color (usa currentColor)
  color.addEventListener('input', () => {
    document.documentElement.style.setProperty('--icon-color', color.value);
  });
  document.documentElement.style.setProperty('--icon-color', color.value);

  // Copiar SVG al portapapeles
  grid.addEventListener('click', async (e) => {
    const btn = e.target.closest('.copy');
    if (!btn) return;
    const markup = btn.getAttribute('data-svg');
    try { 
      await navigator.clipboard.writeText(markup);
      btn.textContent = '¡Copiado!';
      setTimeout(() => btn.textContent = 'Copiar SVG', 1200);
    } catch (_) {
      alert('No se pudo copiar. Copia manualmente.');
    }
  });
</script>
</body>
</html>
