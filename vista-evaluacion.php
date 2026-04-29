<?php
/**
 * Vista HTML (tbody) para la ventana de Evaluación
 * Muestra solo evidencias con 100% de atributos y que tengan al menos 1 instrumento relacionado.
 * Filtros: estado (todas|pendiente|completa) e instrumento (id).
 */
include 'conexion.php';
include 'validacion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$estado = isset($_GET['estado']) ? trim($_GET['estado']) : 'todas'; // pendiente|completa|todas
$inst   = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;

// Query base
$sql = "SELECT
          e.id_evidencia, e.titulo, e.fecha_subida, e.archivo, e.id_docente,
          u.nombre, u.apellidop, u.apellidom,
          t.id_tipo_evidencia, t.nombre_tipo,
          COALESCE(ta.total,0)   AS total_attrs,
          COALESCE(vv.filled,0)  AS filled_attrs,
          COALESCE(insts.total_inst,0) AS total_inst,
          COALESCE(ceg.graded,0) AS graded_inst
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
        LEFT JOIN (
          SELECT te.id_tipo_evidencia, COUNT(DISTINCT ite.id_instrumento) AS total_inst
          FROM tipos_de_evidencia te
          LEFT JOIN instrumento_tipo_evidencia ite ON ite.id_tipo_evidencia = te.id_tipo_evidencia
          LEFT JOIN instrumentos i ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
          GROUP BY te.id_tipo_evidencia
        ) insts ON insts.id_tipo_evidencia = t.id_tipo_evidencia
        LEFT JOIN (
          SELECT id_evidencia, COUNT(DISTINCT id_instrumento) AS graded
          FROM calificacion_evidencia
          GROUP BY id_evidencia
        ) ceg ON ceg.id_evidencia = e.id_evidencia
        WHERE e.ocultar = 0";

$params = []; $types = '';

// Rol 4 (docente): solo sus evidencias
if ($user_role === 4) {
  $sql   .= " AND e.id_docente = ? ";
  $types .= 'i'; $params[] = $user_id;
}

// Filtro por instrumento: evidencias cuyo TIPO tenga relación con ese instrumento
if ($inst > 0) {
  $sql .= " AND EXISTS (
    SELECT 1
    FROM instrumento_tipo_evidencia ite2
    JOIN instrumentos i2 ON i2.id_instrumento = ite2.id_instrumento AND i2.activo=1
    WHERE ite2.id_tipo_evidencia = e.id_tipo_evidencia AND ite2.id_instrumento = ?
  )";
  $types .= 'i'; $params[] = $inst;
}

$sql .= " ORDER BY e.id_evidencia DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
?>
<div class="card-content" style="overflow:hidden;">
  <table id="tabla-evaluacion" class="table display nowrap" style="width:100%;">
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
        <th class="dt-orderable" data-priority="3">Estado</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($res && $res->num_rows > 0):
        while ($row = $res->fetch_assoc()):
          $id    = (int)$row['id_evidencia'];
          $tit   = $row['titulo'] ?? '';
          $tipoN = $row['nombre_tipo'] ?? '—';
          $docN  = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
          $file  = $row['archivo'] ?? '';
          $date  = $row['fecha_subida'] ? date('Y-m-d H:i', strtotime($row['fecha_subida'])) : '—';

          $totalAttrs  = (int)$row['total_attrs'];
          $filledAttrs = (int)$row['filled_attrs'];
          $totalInst   = (int)$row['total_inst'];
          $gradedInst  = (int)$row['graded_inst'];

          // Solo 100% y con al menos 1 instrumento
          if ($totalAttrs <= 0 || $filledAttrs < $totalAttrs || $totalInst <= 0) continue;

          $pct = 100;
          $href = $file ? ('uploads/files/'.rawurlencode($file)) : '';

          $pend = max(0, $totalInst - $gradedInst);
          $estadoCalc = ($pend === 0) ? 'completa' : 'pendiente';

          // Aplicar filtro de estado
          if ($estado === 'pendiente' && $estadoCalc !== 'pendiente') continue;
          if ($estado === 'completa' && $estadoCalc !== 'completa')   continue;

          $badgeEstado = $estadoCalc === 'completa'
            ? '<span class="badge badge-ok">Completa</span>'
            : '<span class="badge badge-warn">Pendiente ('.$pend.'/'.$totalInst.')</span>';

          $btnDownload = $file ? '
            <a class="btn" href="'.$href.'" download="'.htmlspecialchars($file).'" title="Descargar archivo">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M12 3v12M7 10l5 5 5-5M5 19h14" stroke="#111827" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>' : '—';

          $btnVerEval = '
            <button class="btn" type="button" title="Ver / Evaluar"
                    onclick="openEvaluarEvidencia('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="#0ea5e9" stroke-width="1.8"/>
                <circle cx="12" cy="12" r="3" stroke="#0ea5e9" stroke-width="1.8"/>
              </svg>
            </button>';

      ?>
      <tr data-estado="<?= $estadoCalc ?>" data-pend="<?= $pend ?>" data-total-inst="<?= $totalInst ?>">
        <td></td>
        <td><?= $id ?></td>
        <td class="font-medium"><?= htmlspecialchars($tit ?: '—') ?></td>
        <td><span class="badge"><?= htmlspecialchars($tipoN) ?></span></td>
        <td><?= htmlspecialchars($docN ?: '—') ?></td>
        <td><?= $btnDownload ?></td>
        <td><span class="badge" title="<?= htmlspecialchars($row['fecha_subida'] ?? '') ?>"><?= htmlspecialchars($date) ?></span></td>
        <td title="<?= $filledAttrs ?>/<?= $totalAttrs ?> atributos">
          <div class="progress"><div class="progress-bar" style="width: 100%;"></div></div>
          <span style="margin-left:8px; font-size:12px; color:var(--muted-foreground,#555);">100%</span>
        </td>
        <td><?= $badgeEstado ?></td>
        <td style="display:flex; gap:8px;"><?= $btnVerEval ?></td>
      </tr>
      <?php
        endwhile;
      endif;
      ?>
    </tbody>
  </table>
</div>
