<?php
/**
 * Vista de la tabla de usuarios (DataTables + Responsive)
 * - Usa SELECT de usuarios + roles.
 * - 1ª columna es el control responsive ("+")
 * - Soporta filtro por rol con ?role=ID (muestra usuarios con ese rol)
 */
include 'conexion.php';

$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$hasRole = ($role !== '' && is_numeric($role));

$sql = "SELECT 
          u.id_usuario,
          u.nombre,
          u.apellidop,
          u.apellidom,
          u.correo,
          u.rol,
          u.activo,
          r.nombre AS nombrerol,
          u.created_at AS creacion,
          u.updated_at AS actualizacion
        FROM usuarios u
        LEFT JOIN roles r ON r.id_rol = u.rol
        WHERE 1 ";

if ($hasRole) {
  $sql .= " AND u.rol = ? ";
}

$sql .= " ORDER BY u.id_usuario ASC ";

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
  <table id="tabla-usuarios" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <!-- Columna de control para Responsive -->
        <th data-priority="1"></th>

        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Nombre</th>
        <th class="dt-orderable" data-priority="3">Correo</th>
        <th class="dt-orderable" data-priority="3">Rol</th>
        <th class="dt-orderable" data-priority="4">Estado</th>
        <th class="dt-orderable" data-priority="5">Creación</th>
        <th class="dt-orderable" data-priority="5">Actualización</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
          $id        = (int)$row['id_usuario'];
          $nombre    = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
          $correo    = $row['correo'] ?? '';
          $rolId     = isset($row['rol']) ? (int)$row['rol'] : null;
          $rolNombre = $row['nombrerol'] ?? '—';
          $activo    = (int)($row['activo'] ?? 0);
          $crea      = $row['creacion'] ? date('Y-m-d H:i', strtotime($row['creacion'])) : '—';
          $actu      = $row['actualizacion'] ? date('Y-m-d H:i', strtotime($row['actualizacion'])) : '—';

          $estadoBadge = $activo === 1
            ? '<span class="badge default" title="Usuario activo">Activo</span>'
            : '<span class="badge secondary" title="Usuario inactivo">Inactivo</span>';

          // Botón "Ver perfil" (sustituye al toggle de ocultar/mostrar)
          $btnPerfil = '
            <button class="btn" type="button" title="Ver perfil"
                    onclick="openVerPerfil('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="#10b981" stroke-width="1.8" fill="none"/>
                <path d="M2 21a10 10 0 0 1 20 0" stroke="#10b981" stroke-width="1.8" fill="none"/>
              </svg>
            </button>';

          // Editar
          $btnEdit = '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditarUsuario('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>';

          // Eliminar
          $safeName = htmlspecialchars($nombre, ENT_QUOTES);
          $btnDelete = '
            <button class="btn" type="button" title="Eliminar"
                    onclick="openEliminarU('.$id.', \''.$safeName.'\')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';

          // Mail clickable
          $correoHtml = $correo ? '<a href="mailto:'.htmlspecialchars($correo).'" class="link">'.htmlspecialchars($correo).'</a>' : '—';
        ?>
        <tr>
          <!-- control -->
          <td></td>

          <td><?= $id ?></td>
          <td class="font-medium"><?= htmlspecialchars($nombre ?: '—') ?></td>
          <td><?= $correoHtml ?></td>

          <td>
            <span class="badge" title="<?= htmlspecialchars($rolNombre) ?>">
              <?= htmlspecialchars(($rolId !== null ? $rolId : '—').' - '.$rolNombre) ?>
            </span>
          </td>

          <td><?= $estadoBadge ?></td>
          <td><span class="badge" title="<?= htmlspecialchars($row['creacion'] ?? '') ?>"><?= htmlspecialchars($crea) ?></span></td>
          <td><span class="badge" title="<?= htmlspecialchars($row['actualizacion'] ?? '') ?>"><?= htmlspecialchars($actu) ?></span></td>

          <td style="display:flex; gap:8px;">
            <?= $btnPerfil ?>
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
