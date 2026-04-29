<?php
// usuario-eliminar.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

// Si prefieres “soft delete”, cambia a: UPDATE usuarios SET activo = 0, updated_at=NOW() WHERE id_usuario=?
$sql = "DELETE FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
  $stmt->close();
  jexit(true, 'Usuario eliminado.');
} else {
  $err = $conn->error;
  $stmt->close();
  jexit(false, 'No se pudo eliminar: '.$err);
}
