<?php
/**
 * Vista de tabla (tbody) para Calificaciones recibidas
 * - Filtros GET:
 *   - instrumento: id (0 = todos)
 *   - estado: 'todas' | 'aprobadas' | 'noaprobadas' | 'sin'
 * - Renderiza badges por instrumento:
 *   - badge-ok      => Aprobada
 *   - badge-warn    => No aprobada
 *   - secondary     => Sin calificar
 */

include 'conexion.php';
include 'validacion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$instFilter = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;
$estado     = isset($_GET['estado']) ? trim($_GET['estado']) : 'todas';

$NUMERIC_PASS_PCT = 0.60; // 60%

// 1) Trae evidencias base
$sqlE = "SELECT
           e.id_evidencia, e.titulo, e.fecha_subida, e.archivo, e.id_docente,
           u.nombre, u.apellidop, u.apellidom,
           t.id_tipo_evidencia, t.nombre_tipo,
           COALESCE(ta.total, 0)  AS total_attrs,
           COALESCE(vv.filled, 0) AS filled_attrs
         FROM evidencias e
         LEFT JOIN usuarios u ON u.id_usuario = e.id_docente
         LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
         LEFT JOIN (
           SELECT id_tipo_evidencia, COUNT(*) AS total
           FROM atributos_tipo_evidencia
           GROUP BY id_tipo_evidencia
         ) ta ON ta.id_tipo_evidencia = e.id_tipo_evidencia
         LEFT JOIN (
           SELECT id_evidencia, COUNT(DISTINCT id_ate) AS filled
           FROM evidencia_valores_atributo
           GROUP BY id_evidencia
         ) vv ON vv.id_evidencia = e.id_evidencia
         WHERE e.ocultar = 0";

$params = []; $types = '';

if ($user_role === 4) {
  $sqlE .= " AND e.id_docente = ? ";
  $types .= 'i'; $params[] = $user_id;
}

$sqlE .= " ORDER BY e.id_evidencia DESC";

$stmtE = $conn->prepare($sqlE);
if ($params) $stmtE->bind_param($types, ...$params);
$stmtE->execute();
$resE = $stmtE->get_result();

$evidencias = [];
$ids = [];
if ($resE && $resE->num_rows > 0) {
  while ($r = $resE->fetch_assoc()) {
    $evidencias[] = $r;
    $ids[] = (int)$r['id_evidencia'];
  }
}
$stmtE->close();

?>
<style>
.progress { width:120px; height:10px; border-radius:999px; background:var(--muted,#eee); overflow:hidden; display:inline-block; vertical-align:middle; }
.progress-bar { height:100%; background:#10b981; }
.badge.clickable { cursor:pointer; user-select:none; }
.badge.badge-ok { background:#d1fae5; color:#065f46; }
.badge.badge-warn { background:#fef3c7; color:#92400e; }
</style>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-calif-recibidas" class="table display nowrap" style="width:100%;">
    <thead>
    <tr>
      <th data-priority="1"></th>
      <th class="dt-orderable" data-priority="2">ID</th>
      <th class="dt-orderable" data-priority="1">Título</th>
      <th class="dt-orderable" data-priority="3">Tipo</th>
      <th class="dt-orderable" data-priority="3">Docente</th>
      <th class="dt-orderable" data-priority="4">Archivo</th>
      <th class="dt-orderable" data-priority="4">Fecha</th>
      <th class="dt-orderable" data-priority="3">Avance</th>
      <th class="dt-orderable" data-priority="2">Instrumentos</th>
      <th data-priority="2">Acciones</th>
    </tr>
    </thead>
    <tbody>
<?php
if (!empty($ids)) {
  // 2) Estado por instrumento de todas las evidencias mostradas
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types2 = str_repeat('i', count($ids));
  $params2 = $ids;

  $sqlI = "SELECT
             e.id_evidencia,
             i.id_instrumento, i.abreviatura, i.nombre_completo,
             i.tipo_calificacion, COALESCE(i.min_calificacion,0) AS min_cal, COALESCE(i.max_calificacion,10) AS max_cal,
             ce.resultado, ce.comentario, ce.calificado_en
           FROM evidencias e
           JOIN instrumento_tipo_evidencia ite ON ite.id_tipo_evidencia = e.id_tipo_evidencia
           JOIN instrumentos i ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
           LEFT JOIN calificacion_evidencia ce
                  ON ce.id_evidencia = e.id_evidencia
                 AND ce.id_instrumento = i.id_instrumento
           WHERE e.id_evidencia IN ($placeholders)";

  if ($instFilter > 0) {
    $sqlI .= " AND i.id_instrumento = ? ";
    $types2 .= 'i'; $params2[] = $instFilter;
  }

  $stmtI = $conn->prepare($sqlI);
  $stmtI->bind_param($types2, ...$params2);
  $stmtI->execute();
  $resI = $stmtI->get_result();

  $map = []; // id_evidencia => [ instrumentos... ]
  if ($resI && $resI->num_rows > 0) {
    while ($r = $resI->fetch_assoc()) {
      $evi = (int)$r['id_evidencia'];
      $map[$evi][] = $r;
    }
  }
  $stmtI->close();

  // 3) Render por evidencia
  foreach ($evidencias as $row) {
    $id    = (int)$row['id_evidencia'];
    $tit   = $row['titulo'] ?? '';
    $tipoN = $row['nombre_tipo'] ?? '—';
    $docN  = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
    $file  = $row['archivo'] ?? '';
    $date  = $row['fecha_subida'] ? date('Y-m-d H:i', strtotime($row['fecha_subida'])) : '—';

    $totalAttrs  = (int)$row['total_attrs'];
    $filledAttrs = (int)$row['filled_attrs'];
    $pct = ($totalAttrs > 0) ? round($filledAttrs * 100 / $totalAttrs) : 0;
    if ($pct < 0) $pct = 0; if ($pct > 100) $pct = 100;

    $href = $file ? ('uploads/files/'.rawurlencode($file)) : '';

    $btnDownload = $file ? '
      <a class="btn" href="'.$href.'" download="'.htmlspecialchars($file).'" title="Descargar archivo">
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
          <path d="M12 3v12M7 10l5 5 5-5M5 19h14" stroke="#111827" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>' : '—';

    $instList = $map[$id] ?? [];

    // ====== Filtro por estado (ARREGLADO: normaliza APROBACIÓN como float >= 1) ======
    $includeRow = true;
    if ($estado !== 'todas') {
      $includeRow = false;
      foreach ($instList as $inst) {
        $isNum = ($inst['tipo_calificacion'] === 'NUMERICA');
        $resRaw = $inst['resultado'];
        $hasRes = ($resRaw !== null && $resRaw !== '');
        if (!$hasRes) {
          if ($estado === 'sin') { $includeRow = true; break; }
          continue;
        }
        $resNum = (float)$resRaw;

        if ($isNum) {
          $min = (float)$inst['min_cal'];
          $max = (float)$inst['max_cal'];
          $umbral = $min + $NUMERIC_PASS_PCT * ($max - $min);
          $ok = ($resNum >= $umbral);
        } else {
          // APROBACIÓN: cualquier 1, 1.0, 1.00 => aprobado
          $ok = ($resNum >= 1.0);
        }

        if ($estado === 'aprobadas' && $ok) { $includeRow = true; break; }
        if ($estado === 'noaprobadas' && !$ok) { $includeRow = true; break; }
      }
    }

    if ($instFilter > 0 && empty($instList)) $includeRow = false;
    if (!$includeRow) continue;

    // ====== Render badges por instrumento (ARREGLADO) ======
    $badges = [];
    foreach ($instList as $inst) {
      $iid   = (int)$inst['id_instrumento'];
      $abbr  = $inst['abreviatura'];
      $isNum = ($inst['tipo_calificacion'] === 'NUMERICA');

      $resRaw = $inst['resultado'];
      $hasRes = ($resRaw !== null && $resRaw !== '');
      $cls   = 'secondary';
      $label = 'Sin calificar';

      if ($hasRes) {
        $resNum = (float)$resRaw;
        if ($isNum) {
          $min = (float)$inst['min_cal']; $max = (float)$inst['max_cal'];
          $umbral = $min + $NUMERIC_PASS_PCT * ($max - $min);
          $ok = ($resNum >= $umbral);
          $cls = $ok ? 'badge-ok' : 'badge-warn';
          // mostramos el número como valor
          $label = is_numeric($resNum) ? rtrim(rtrim(number_format($resNum, 2, '.', ''), '0'), '.') : (string)$resRaw;
        } else {
          // APROBACIÓN normalizada
          $ok = ($resNum >= 1.0);
          $cls = $ok ? 'badge-ok' : 'badge-warn';
          $label = $ok ? 'Aprobada' : 'No aprobada';
        }
      }

      $click = $hasRes ? 'onclick="openVerCalificacion('.$id.','.$iid.')"'
                       : '';
      $badges[] = '<span class="badge clickable '.$cls.'" '.$click.' title="Instrumento: '.htmlspecialchars($abbr).'">'.
                  htmlspecialchars($abbr).': '.htmlspecialchars($label).'</span>';
    }

    $btnDetalles = '
      <button class="btn" type="button" title="Ver detalles"
              onclick="window.location.href=\'evidencia-detalle.php?id='.$id.'\'">
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
          <path d="M4 6h16M4 12h16M4 18h16" stroke="#10b981" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
      </button>';

    echo '<tr>';
    echo '<td></td>';
    echo '<td>'. $id .'</td>';
    echo '<td class="font-medium">'. htmlspecialchars($tit ?: '—') .'</td>';
    echo '<td><span class="badge">'. htmlspecialchars($tipoN) .'</span></td>';
    echo '<td>'. htmlspecialchars($docN ?: '—') .'</td>';
    echo '<td>'. $btnDownload .'</td>';
    echo '<td><span class="badge" title="'.htmlspecialchars($row['fecha_subida'] ?? '').'">'. htmlspecialchars($date) .'</span></td>';

    echo '<td title="'.$filledAttrs.'/'.$totalAttrs.' atributos">';
    echo '  <div class="progress"><div class="progress-bar" style="width: '.$pct.'%;"></div></div>';
    echo '  <span style="margin-left:8px; font-size:12px; color:var(--muted-foreground,#555);">'.$pct.'%</span>';
    echo '</td>';

    echo '<td style="display:flex; gap:6px; flex-wrap:wrap;">'. implode('', $badges) .'</td>';

    echo '<td style="display:flex; gap:8px;">'. $btnDetalles .'</td>';
    echo '</tr>';
  }
}
?>
    </tbody>
  </table>
</div>
