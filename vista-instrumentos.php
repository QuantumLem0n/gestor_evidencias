<?php
/**
 * Vista de la tabla de Instrumentos (DataTables + Responsive)
 * - Muestra abreviatura, nombre, tipo de calificación y rango (si aplica).
 * - Solo botón Editar.
 */
include 'conexion.php';

$sql = "SELECT id_instrumento, abreviatura, nombre_completo, tipo_calificacion, min_calificacion, max_calificacion, activo
        FROM instrumentos
        ORDER BY id_instrumento ASC";
$rs  = $conn->query($sql);
?>
<style>
  .pill{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:12px;line-height:1;border:1px solid rgba(0,0,0,.08)}
  .p-ap{background:#ecfeff;color:#155e75}
  .p-num{background:#f5f3ff;color:#4c1d95}
</style>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-instrumentos" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <th data-priority="1"></th>
        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Abrev.</th>
        <th class="dt-orderable">Nombre completo</th>
        <th class="dt-orderable">Tipo de calificación</th>
        <th class="dt-orderable">Rango</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($rs && $rs->num_rows > 0): ?>
        <?php while ($row = $rs->fetch_assoc()):
          $id   = (int)$row['id_instrumento'];
          $ab   = htmlspecialchars($row['abreviatura'] ?? '');
          $nom  = htmlspecialchars($row['nombre_completo'] ?? '');
          $tipo = $row['tipo_calificacion'] ?? 'APROBACION';
          $min  = $row['min_calificacion'];
          $max  = $row['max_calificacion'];

          $tipoPill = ($tipo === 'NUMERICA')
            ? '<span class="pill p-num">Numérica</span>'
            : '<span class="pill p-ap">Aprobación (0/1)</span>';

          $rango = ($tipo === 'NUMERICA')
            ? (($min !== null && $max !== null) ? (0 + $min).' – '.(0 + $max) : '—')
            : '—';

          $btnEdit = '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditarInstrumento('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>';
        ?>
        <tr data-tipo="<?= htmlspecialchars($tipo) ?>">
          <td></td>
          <td><?= $id ?></td>
          <td class="font-medium"><?= $ab ?></td>
          <td><?= $nom ?></td>
          <td><?= $tipoPill ?></td>
          <td><?= $rango ?></td>
          <td style="display:flex; gap:8px;"><?= $btnEdit ?></td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
