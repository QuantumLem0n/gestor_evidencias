<?php
/**
 * Vista de la tabla (DataTables + Responsive)
 * - Usa solo menu_pagina + menu_rol (+ iconos)
 * - 1ª columna es el control responsive ("+")
 * - Soporta filtro por rol con ?role=ID (muestra solo páginas visibles vinculadas a ese rol)
 */
include 'conexion.php';

$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$hasRole = ($role !== '' && is_numeric($role));

$sql = "SELECT
    mp.id_mp,
    mp.nombre_pagina,
    mp.archivo,
    mp.ocultar,
    ic.descripcion AS icono_desc,
    ic.imagen      AS icono_svg,
    COALESCE(r.roles_cnt, 0) AS roles_cnt
  FROM menu_pagina mp
  INNER JOIN iconos ic
    ON ic.id_icono = mp.id_icono
  LEFT JOIN (
    SELECT id_pagina, COUNT(DISTINCT id_rol) AS roles_cnt
    FROM menu_rol
    WHERE COALESCE(ocultar,0) = 0
    GROUP BY id_pagina
  ) r ON r.id_pagina = mp.id_mp
  WHERE 1
";

if ($hasRole) {
  // Solo páginas relacionadas a ese rol, y visibles
  $sql .= " AND mp.id_mp IN (
              SELECT id_pagina FROM menu_rol
              WHERE id_rol = ? AND COALESCE(ocultar,0)=0
            )
            AND COALESCE(mp.ocultar,0)=0 ";
}

$sql .= " ORDER BY mp.id_mp ASC ";

if ($hasRole) {
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, 'i', $role);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
} else {
  $result = $conn->query($sql);
}
?>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-conf" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <!-- Columna de control para Responsive -->
        <th data-priority="1"></th>

        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Página</th>
        <th class="dt-orderable" data-priority="3">Archivo</th>
        <th class="dt-orderable" data-priority="4">Icono</th>
        <th class="dt-orderable" data-priority="3">Estado</th>
        <th class="dt-orderable" data-priority="4">Roles</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
          $id       = (int)$row['id_mp'];
          $nombre   = $row['nombre_pagina'];
          $archivo  = $row['archivo'];
          $ocultar  = (int)$row['ocultar'];     // 0 activo, 1 oculto
          $iconDesc = $row['icono_desc'];
          $iconSVG  = $row['icono_svg'];        // paths sin <svg> contenedor
          $rolesCnt = (int)$row['roles_cnt'];

          $estadoBadge = $ocultar === 0
            ? '<span class="badge default" title="Visible">Activo</span>'
            : '<span class="badge secondary" title="Oculto">Oculto</span>';

          // Toggle Mostrar/Ocultar
          if ($ocultar === 0) {
            $btnToggle = '
              <button type="button" class="btn" title="Ocultar"
                      onclick="toggleEstado('.$id.',1)">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                  <path d="M3 3l18 18" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                  <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" stroke="#ef4444" stroke-width="1.8" fill="none"/>
                  <circle cx="12" cy="12" r="3" stroke="#ef4444" stroke-width="1.8" fill="none"/>
                </svg>
              </button>';
          } else {
            $btnToggle = '
              <button type="button" class="btn" title="Mostrar"
                      onclick="toggleEstado('.$id.',0)">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                  <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z" stroke="#10b981" stroke-width="1.8" fill="none"/>
                  <circle cx="12" cy="12" r="3.2" stroke="#10b981" stroke-width="1.8" fill="none"/>
                </svg>
              </button>';
          }

          // Editar (activo)
          $btnEdit = '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditar('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>';

          // Eliminar (activo)
          $safeName = htmlspecialchars($nombre, ENT_QUOTES);
          $btnDelete = '
            <button class="btn" type="button" title="Eliminar"
                    onclick="openEliminar('.$id.', \''.$safeName.'\')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';
        ?>
        <tr>
          <!-- control -->
          <td></td>

          <td><?= $id ?></td>
          <td class="font-medium"><?= htmlspecialchars($nombre) ?></td>

          <td>
            <span class="badge" title="<?= htmlspecialchars($archivo) ?>" style="font-family:ui-monospace,monospace;">
              <?= htmlspecialchars($archivo) ?>
            </span>
          </td>

          <td>
            <div title="<?= htmlspecialchars($iconDesc) ?>" style="display:flex;align-items:center;gap:8px;">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:20px;height:20px;">
                <?= $iconSVG ?>
              </svg>
              <span></span>
            </div>
          </td>

          <td><?= $estadoBadge ?></td>

          <td>
            <span class="badge"><?= $rolesCnt ?> rol(es)</span>
          </td>

          <td style="display:flex; gap:8px;">
            <?= $btnToggle ?>
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      <!-- Cuando no haya filas, DataTables mostrará su propio "No data available" y no fallará -->
    </tbody>
  </table>
</div>

