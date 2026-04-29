<?php
// atributo-tipo-eliminar.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_POST['id_ate']) ? (int)$_POST['id_ate'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

/*
 * Si tienes FK en evidencia_valores_atributo (id_ate) con ON DELETE CASCADE,
 * esto borrará también los valores capturados para ese atributo.
 * Si prefieres "soft delete", agrega un flag a atributos_tipo_evidencia.
 */
$sql = "DELETE FROM atributos_tipo_evidencia WHERE id_ate = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
  $stmt->close();
  jexit(true, 'Atributo eliminado.');
} else {
  $err = $conn->error;
  $stmt->close();
  jexit(false, 'No se pudo eliminar: '.$err);
}
