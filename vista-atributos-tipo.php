<?php
/**
 * Vista de la tabla de Atributos por Tipo (DataTables + Responsive)
 * Recibe: ?id_tipo=...
 */
include 'conexion.php';

$id_tipo = isset($_GET['id_tipo']) ? (int)$_GET['id_tipo'] : 0;
if ($id_tipo <= 0) {
  echo '<div class="card-content"><p>Falta el parámetro <strong>id_tipo</strong>.</p></div>';
  exit;
}

$sql = "SELECT
          ate.id_ate,
          ate.id_tipo_evidencia,
          ate.id_tipo_atributo,
          ate.nombre_atributo,
          ate.slug,
          ate.descripcion,
          ate.orden,
          ate.requerido,
          ate.unico_por_evidencia,
          ate.multiple,
          ate.min_longitud,
          ate.max_longitud,
          ate.min_valor,
          ate.max_valor,
          ate.opciones_json,
          ta.nombre_tipo AS nombre_tipo_at,
          ta.slug        AS slug_at,
          ta.grupo_storage
        FROM atributos_tipo_evidencia ate
        INNER JOIN tipos_atributo ta ON ta.id_tipo_atributo = ate.id_tipo_atributo
        WHERE ate.id_tipo_evidencia = ?
        ORDER BY ate.orden ASC, ate.id_ate ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_tipo);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="card-content" style="overflow:hidden;">
  <table id="tabla-atributos-tipo" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <th data-priority="1"></th>
        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Atributo</th>
        <th class="dt-orderable" data-priority="2">Slug</th>
        <th class="dt-orderable" data-priority="3">Tipo</th>
        <th class="dt-orderable" data-priority="2">Orden</th>
        <th class="dt-orderable" data-priority="3">Flags</th>
        <th class="dt-orderable" data-priority="4">Restricciones</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
          $id   = (int)$row['id_ate'];
          $nom  = $row['nombre_atributo'] ?? '';
          $slug = $row['slug'] ?? '';
          $tipoAt = ($row['nombre_tipo_at'] ?? '') . ' (' . ($row['slug_at'] ?? '') . ')';
          $orden  = (int)$row['orden'];

          $req    = (int)$row['requerido'] === 1 ? 'Req' : '—';
          $unico  = (int)$row['unico_por_evidencia'] === 1 ? 'Único' : '—';
          $mult   = (int)$row['multiple'] === 1 ? 'Múltiple' : '—';

          // Resumen de restricciones
          $rest = [];
          if (!is_null($row['min_longitud']) || !is_null($row['max_longitud'])) {
            $rest[] = 'Len: ' . (is_null($row['min_longitud']) ? '—' : (int)$row['min_longitud']) . '–' . (is_null($row['max_longitud']) ? '—' : (int)$row['max_longitud']);
          }
          if (!is_null($row['min_valor']) || !is_null($row['max_valor'])) {
            $rest[] = 'Rango: ' . (is_null($row['min_valor']) ? '—' : $row['min_valor']) . '–' . (is_null($row['max_valor']) ? '—' : $row['max_valor']);
          }
          if ($row['opciones_json']) {
            // muestra tamaño del arreglo o un preview corto
            $preview = mb_strimwidth($row['opciones_json'], 0, 50, '…');
            $rest[] = 'Opciones: ' . htmlspecialchars($preview);
          }
          $restStr = $rest ? implode(' | ', $rest) : '—';

          $safeName = htmlspecialchars($nom, ENT_QUOTES);

          // Editar
          $btnEdit = '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditarAtributo('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>';

          // Eliminar
          $btnDelete = '
            <button class="btn" type="button" title="Eliminar"
                    onclick="openEliminarAT('.$id.', \''.$safeName.'\')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';
        ?>
        <tr>
          <td></td>
          <td><?= $id ?></td>
          <td class="font-medium"><?= htmlspecialchars($nom ?: '—') ?></td>
          <td><code><?= htmlspecialchars($slug ?: '—') ?></code></td>
          <td><span class="badge"><?= htmlspecialchars($tipoAt) ?></span></td>
          <td><span class="badge"><?= $orden ?></span></td>
          <td>
            <span class="badge"><?= $req ?></span>
            <span class="badge"><?= $unico ?></span>
            <span class="badge"><?= $mult ?></span>
          </td>
          <td><?= $restStr ?></td>
          <td style="display:flex; gap:8px;">
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
