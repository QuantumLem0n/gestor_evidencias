<?php
/**
 * Vista de detalle de atributos/valores para una evidencia
 * Muestra lista ordenada por 'orden' de atributos del tipo + valores (puede haber múltiples)
 * Mensajes:
 *  - Si no hay valores:
 *     * rol 4 (dueño): invitar a completar
 *     * otros roles: indicar que no hay info completa
 */
include 'conexion.php';
include 'validacion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  echo '<div class="card-content"><p>Falta parámetro.</p></div>'; exit;
}

// Trae evidencia + tipo + dueño
$sqlE = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia,
                t.nombre_tipo
         FROM evidencias e
         LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
         WHERE e.id_evidencia = ?";
$stmt = $conn->prepare($sqlE);
$stmt->bind_param('i', $id);
$stmt->execute();
$re = $stmt->get_result();
$evi = $re ? $re->fetch_assoc() : null;
$stmt->close();

if (!$evi) {
  echo '<div class="card-content"><p>Evidencia no encontrada.</p></div>'; exit;
}
$isOwner = ((int)$evi['id_docente'] === $user_id);

// Trae atributos definidos para ese tipo
$sqlA = "SELECT a.id_ate, a.nombre_atributo, a.slug, a.descripcion, a.orden,
                a.requerido, a.unico_por_evidencia, a.multiple,
                a.min_longitud, a.max_longitud, a.min_valor, a.max_valor, a.opciones_json,
                ta.id_tipo_atributo, ta.nombre_tipo AS tipo_nombre, ta.slug AS tipo_slug, ta.grupo_storage, ta.validador_regex
         FROM atributos_tipo_evidencia a
         INNER JOIN tipos_atributo ta ON ta.id_tipo_atributo = a.id_tipo_atributo
         WHERE a.id_tipo_evidencia = ?
         ORDER BY a.orden ASC, a.id_ate ASC";
$stmt = $conn->prepare($sqlA);
$stmt->bind_param('i', $evi['id_tipo_evidencia']);
$stmt->execute();
$ra = $stmt->get_result();
$attrs = $ra ? $ra->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Trae valores capturados para esta evidencia
$sqlV = "SELECT v.id_eva, v.id_ate, v.indice, v.valor_texto, v.valor_largo, v.valor_int, v.valor_decimal,
                v.valor_fecha, v.valor_bool, v.valor_archivo, v.valor_json
         FROM evidencia_valores_atributo v
         WHERE v.id_evidencia = ?
         ORDER BY v.id_ate ASC, v.indice ASC, v.id_eva ASC";
$stmt = $conn->prepare($sqlV);
$stmt->bind_param('i', $id);
$stmt->execute();
$rv = $stmt->get_result();
$values = [];
if ($rv) {
  while ($r = $rv->fetch_assoc()) {
    $values[(int)$r['id_ate']][] = $r;
  }
}
$stmt->close();

// Render
$hasAnyValue = false;
foreach ($values as $list) { if (!empty($list)) { $hasAnyValue = true; break; } }

?>
<div class="card-content">
  <?php if (!$hasAnyValue): ?>
    <?php if ($user_role === 4 && $isOwner): ?>
      <div class="alert" role="status" style="margin-bottom:12px;">
        Aún no has capturado la información detallada de esta evidencia. Usa <strong>“Editar atributos”</strong> para completarla.
      </div>
    <?php else: ?>
      <div class="alert" role="status" style="margin-bottom:12px;">
        Esta evidencia todavía no cuenta con información detallada.
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="space-y-4">
    <?php if (empty($attrs)): ?>
      <p class="text-muted-foreground">Este tipo de evidencia no tiene atributos definidos.</p>
    <?php else: ?>
      <?php foreach ($attrs as $a):
        $aid   = (int)$a['id_ate'];
        $gs    = $a['grupo_storage'];
        $list  = $values[$aid] ?? [];
      ?>
        <div class="attr-row" style="display:flex; gap:16px; align-items:flex-start; border-bottom:1px dashed var(--border); padding-bottom:12px;">
          <div style="min-width:220px;">
            <div class="text-sm text-muted-foreground">Atributo</div>
            <div class="font-medium"><?= htmlspecialchars($a['nombre_atributo']) ?></div>
            <div class="text-xs text-muted-foreground"><?= htmlspecialchars($a['descripcion'] ?? '') ?></div>
          </div>

          <div style="flex:1;">
            <?php if (empty($list)): ?>
              <span class="badge secondary">Sin datos</span>
            <?php else: ?>
              <?php
              // Render por grupo_storage
              if ($gs === 'archivo') {
                echo '<div style="display:flex; flex-wrap:wrap; gap:8px;">';
                foreach ($list as $v) {
                  $fn = $v['valor_archivo'];
                  if ($fn) {
                    $href = 'uploads/files/'.rawurlencode($fn);
                    echo '<a class="btn" href="'.$href.'" download="'.htmlspecialchars($fn).'">Descargar</a>';
                  }
                }
                echo '</div>';
              } elseif ($gs === 'texto_corto') {
                $chips = array_filter(array_map(fn($v)=>trim((string)$v['valor_texto']), $list));
                echo $chips ? '<div style="display:flex; flex-wrap:wrap; gap:6px;">'.implode('', array_map(fn($t)=>'<span class="badge">'.$t.'</span>', $chips)).'</div>' : '<span class="badge secondary">Sin datos</span>';
              } elseif ($gs === 'texto_largo') {
                foreach ($list as $v) {
                  $txt = trim((string)$v['valor_largo']);
                  if ($txt !== '') echo '<p>'.nl2br(htmlspecialchars($txt)).'</p>';
                }
              } elseif ($gs === 'entero' || $gs === 'decimal') {
                $vals = [];
                foreach ($list as $v) {
                  $vals[] = ($gs==='entero') ? (string)$v['valor_int'] : (string)$v['valor_decimal'];
                }
                $vals = array_filter($vals, fn($x)=>$x!=='');
                echo $vals ? '<div style="display:flex; flex-wrap:wrap; gap:6px;">'.implode('', array_map(fn($t)=>'<span class="badge">'.$t.'</span>', $vals)).'</div>' : '<span class="badge secondary">Sin datos</span>';
              } elseif ($gs === 'fecha') {
                $vals = array_filter(array_map(fn($v)=>$v['valor_fecha'], $list));
                echo $vals ? '<div style="display:flex; flex-wrap:wrap; gap:6px;">'.implode('', array_map(fn($t)=>'<span class="badge">'.$t.'</span>', $vals)).'</div>' : '<span class="badge secondary">Sin datos</span>';
              } elseif ($gs === 'booleano') {
                $vals = array_map(fn($v)=> (int)$v['valor_bool'] === 1 ? 'Sí' : 'No', $list);
                echo $vals ? '<div style="display:flex; flex-wrap:wrap; gap:6px;">'.implode('', array_map(fn($t)=>'<span class="badge">'.$t.'</span>', $vals)).'</div>' : '<span class="badge secondary">Sin datos</span>';
              } elseif ($gs === 'json') {
                foreach ($list as $v) {
                  $j = $v['valor_json'];
                  if ($j) {
                    echo '<pre class="code" style="max-width:100%; overflow:auto;">'.htmlspecialchars($j).'</pre>';
                  }
                }
              } else {
                echo '<span class="badge secondary">Sin datos</span>';
              }
              ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
