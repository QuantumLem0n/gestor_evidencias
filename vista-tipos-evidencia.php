<?php
/**
 * Vista de la tabla de Tipos de Evidencia (DataTables + Responsive)
 * - Los instrumentos provienen de instrumento_tipo_evidencia + instrumentos.
 */
include 'conexion.php';

$sql = "SELECT
          te.id_tipo_evidencia,
          te.nombre_tipo,
          te.descripcion,
          GROUP_CONCAT(i.abreviatura ORDER BY i.id_instrumento SEPARATOR ',') AS abrev_list
        FROM tipos_de_evidencia te
        LEFT JOIN instrumento_tipo_evidencia ite
          ON ite.id_tipo_evidencia = te.id_tipo_evidencia
        LEFT JOIN instrumentos i
          ON i.id_instrumento = ite.id_instrumento
        GROUP BY te.id_tipo_evidencia, te.nombre_tipo, te.descripcion
        ORDER BY te.id_tipo_evidencia ASC";
$result = $conn->query($sql);
?>
<style>
  /* Etiquetas de instrumentos (ligeras y discretas) */
  .itags{display:flex;flex-wrap:wrap;gap:6px}
  .t-pill{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:12px;line-height:1;border:1px solid rgba(0,0,0,.08)}
  .t-sni{background:#ecfeff;color:#155e75}
  .t-prodep{background:#f5f3ff;color:#4c1d95}
  .t-esdeped{background:#fef9c3;color:#854d0e}
  .t-generic{background:#eef2ff;color:#3730a3}
</style>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-tipos-evidencia" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <th data-priority="1"></th>
        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Tipo</th>
        <th class="dt-orderable" data-priority="3">Descripción</th>
        <th class="dt-orderable" data-priority="2">Instrumento(s)</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
          $id      = (int)$row['id_tipo_evidencia'];
          $tipo    = $row['nombre_tipo'] ?? '';
          $desc    = $row['descripcion'] ?? '';
          $abrlRaw = $row['abrev_list'] ?? null;

          $safeTipo = htmlspecialchars($tipo);
          $safeDesc = htmlspecialchars($desc);
          $safeNameAttr = htmlspecialchars($tipo, ENT_QUOTES);

          // Construir etiquetas desde la lista de abreviaturas
          $abrevs = $abrlRaw ? array_filter(array_map('trim', explode(',', $abrlRaw))) : [];
          $mapClass = [
            'SNI'     => 't-sni',
            'PRODEP'  => 't-prodep',
            'ESDEPED' => 't-esdeped'
          ];
          $tags = [];
          foreach ($abrevs as $ab) {
            $cls = $mapClass[$ab] ?? 't-generic';
            $tags[] = '<span class="t-pill '.$cls.'" title="'.htmlspecialchars($ab).'">'.htmlspecialchars($ab).'</span>';
          }
          $instrumentosHtml = $tags ? '<div class="itags">'.implode('', $tags).'</div>' : '—';

          // Flags para filtros existentes (checkboxes SNI/PRODEP/ESDEPED)
          $hasSNI     = in_array('SNI', $abrevs, true) ? 1 : 0;
          $hasPRODEP  = in_array('PRODEP', $abrevs, true) ? 1 : 0;
          $hasESDEPED = in_array('ESDEPED', $abrevs, true) ? 1 : 0;

          // Botón Lista (enlistar características del tipo)
          $btnLista = '
            <button class="btn" type="button" title="Enlistar características"
                    onclick="openAtributosTipo('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M6 6h13M6 12h13M6 18h13" stroke="#10b981" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="3" cy="6" r="1.5" fill="#10b981"/>
                <circle cx="3" cy="12" r="1.5" fill="#10b981"/>
                <circle cx="3" cy="18" r="1.5" fill="#10b981"/>
              </svg>
            </button>';

          // Editar
          $btnEdit = '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditarTipo('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>';

          // Eliminar
          $btnDelete = '
            <button class="btn" type="button" title="Eliminar"
                    onclick="openEliminarTE('.$id.', \''.$safeNameAttr.'\')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';
        ?>
        <tr
          data-sni="<?= $hasSNI ?>"
          data-prodep="<?= $hasPRODEP ?>"
          data-esdeped="<?= $hasESDEPED ?>"
          data-instrumentos="<?= htmlspecialchars(implode('|', $abrevs)) ?>"
        >
          <td></td>
          <td><?= $id ?></td>
          <td class="font-medium"><?= $safeTipo ?></td>
          <td><?= $safeDesc ?: '—' ?></td>
          <td><?= $instrumentosHtml ?></td>
          <td style="display:flex; gap:8px;">
            <?= $btnLista ?>
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
