<?php
/**
 * Vista de la tabla de evidencias (DataTables + Responsive)
 * - Filtro por tipo de evidencia con ?tipo=ID
 * - Rol 4 (docentes): solo sus evidencias.
 * - Columna "Avance" con % de atributos capturados (valores_evidencia).
 */
include 'conexion.php';
include 'validacion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$hasTipo = ($tipo !== '' && is_numeric($tipo));

// Subqueries para total de atributos del tipo y atributos con valor por evidencia
$sql = "SELECT
          e.id_evidencia, e.titulo, e.fecha_subida, e.archivo,
          u.id_usuario AS id_docente, u.nombre, u.apellidop, u.apellidom,
          t.id_tipo_evidencia, t.nombre_tipo,
          COALESCE(ta.total, 0)  AS total_attrs,
          COALESCE(vv.filled, 0) AS filled_attrs
        FROM evidencias e
        LEFT JOIN usuarios u ON e.id_docente = u.id_usuario
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
        WHERE e.ocultar = 0 ";

$params = [];
$types  = '';

if ($user_role === 4) {
  // Docente: solo sus evidencias
  $sql .= " AND e.id_docente = ? ";
  $params[] = $user_id;
  $types   .= 'i';
}

if ($hasTipo) {
  $sql .= " AND e.id_tipo_evidencia = ? ";
  $params[] = (int)$tipo;
  $types   .= 'i';
}

$sql .= " ORDER BY e.id_evidencia DESC ";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo '<div class="card-content"><p>Error de preparación de consulta.</p></div>';
  exit;
}
if ($params) {
  // Si hay filtros, bindea
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<style>
/* Barra simple de progreso para la columna Avance */
.progress {
  width: 120px; height: 10px; border-radius: 999px;
  background: var(--muted, #eee);
  overflow: hidden; display: inline-block; vertical-align: middle;
}
.progress-bar {
  height: 100%;
  background: #10b981; /* verde */
}
</style>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-evidencias" class="table display nowrap" style="width:100%;">
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
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
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

          // Botón Descargar (en lugar del link del nombre de archivo)
          $btnDownload = $file ? '
            <a class="btn" href="'.$href.'" download="'.htmlspecialchars($file).'" title="Descargar archivo">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M12 3v12M7 10l5 5 5-5M5 19h14" stroke="#111827" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>' : '—';

          // Acciones
          $btnDetalles = '
            <button class="btn" type="button" title="Ver detalles"
                    onclick="openVerDetalles('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M4 6h16M4 12h16M4 18h16" stroke="#10b981" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';

          // Editar: solo docentes (la suya)
          $canEdit = ((int)$user_role === 4) && ((int)$row['id_docente'] === (int)$user_id);
          $btnEdit = $canEdit ? '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditarEvidencia('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>' : '';

          // Eliminar (soft): admin cualquiera; docente solo la suya
          $canDelete = ((int)$user_role === 1) || (((int)$user_role === 4) && ((int)$row['id_docente'] === (int)$user_id));
          $safeTit = htmlspecialchars($tit, ENT_QUOTES);
          $btnDelete = $canDelete ? '
            <button class="btn" type="button" title="Ocultar"
                    onclick="openEliminarEvi('.$id.', \''.$safeTit.'\')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>' : '';

        ?>
        <tr>
          <td></td>
          <td><?= $id ?></td>
          <td class="font-medium"><?= htmlspecialchars($tit ?: '—') ?></td>
          <td><span class="badge"><?= htmlspecialchars($tipoN) ?></span></td>
          <td><?= htmlspecialchars($docN ?: '—') ?></td>

          <!-- Botón descargar -->
          <td><?= $btnDownload ?></td>

          <td><span class="badge" title="<?= htmlspecialchars($row['fecha_subida'] ?? '') ?>"><?= htmlspecialchars($date) ?></span></td>

          <!-- Avance -->
          <td title="<?= $filledAttrs ?>/<?= $totalAttrs ?> atributos">
            <div class="progress" aria-label="Avance de captura">
              <div class="progress-bar" style="width: <?= $pct ?>%;"></div>
            </div>
            <span style="margin-left:8px; font-size:12px; color:var(--muted-foreground,#555);"><?= $pct ?>%</span>
          </td>

          <td style="display:flex; gap:8px;">
            <?= $btnDetalles ?>
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
