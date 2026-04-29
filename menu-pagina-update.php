<?php
// menu-pagina-update.php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$id_mp = isset($_POST['id_mp']) ? intval($_POST['id_mp']) : 0;
$nombre = trim($_POST['nombre_pagina'] ?? '');
$archivo = trim($_POST['archivo'] ?? '');
$id_icono = isset($_POST['id_icono']) ? intval($_POST['id_icono']) : 0;
$roles = isset($_POST['roles']) && is_array($_POST['roles']) ? array_map('intval', $_POST['roles']) : [];

if ($id_mp<=0 || $nombre==='' || $archivo==='' || $id_icono<=0) {
  echo json_encode(['status'=>'error','message'=>'Datos incompletos']); exit;
}

// Unicidad de archivo (excluir este mismo id)
$qUni = "SELECT 1 FROM menu_pagina WHERE archivo = ? AND id_mp <> ? LIMIT 1";
$stmt = $conn->prepare($qUni);
$stmt->bind_param('si', $archivo, $id_mp);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  echo json_encode(['status'=>'error','message'=>'El archivo ya existe en otra página.']); 
  $stmt->close(); exit;
}
$stmt->close();

$conn->begin_transaction();
try {
  // Update página
  $qU = "UPDATE menu_pagina
         SET nombre_pagina = ?, archivo = ?, id_icono = ?
         WHERE id_mp = ?";
  $stmtU = $conn->prepare($qU);
  $stmtU->bind_param('ssii', $nombre, $archivo, $id_icono, $id_mp);
  if (!$stmtU->execute()) throw new Exception('No se pudo actualizar la página.');
  $stmtU->close();

  // Reset roles: borrar y volver a insertar los marcados (siempre rol 1)
  $qDel = "DELETE FROM menu_rol WHERE id_pagina = ?";
  $stmtD = $conn->prepare($qDel);
  $stmtD->bind_param('i', $id_mp);
  if (!$stmtD->execute()) throw new Exception('No se pudo limpiar roles.');
  $stmtD->close();

  if (!in_array(1, $roles, true)) $roles[] = 1;
  $roles = array_values(array_unique(array_filter($roles, fn($v)=>$v>0)));

  $qIns = "INSERT INTO menu_rol (id_pagina, id_rol, ocultar) VALUES (?, ?, 0)";
  $stmtI = $conn->prepare($qIns);
  foreach ($roles as $rid) {
    $stmtI->bind_param('ii', $id_mp, $rid);
    if (!$stmtI->execute()) throw new Exception('No se pudo insertar roles.');
  }
  $stmtI->close();

  $conn->commit();
  echo json_encode(['status'=>'ok']);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
