<?php
// tipo-evidencia-eliminar.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_POST['id_tipo_evidencia']) ? (int)$_POST['id_tipo_evidencia'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

$conn->begin_transaction();
try {
  // Borrar relaciones puente
  $sqlDelRel = "DELETE FROM instrumento_tipo_evidencia WHERE id_tipo_evidencia = ?";
  $stmt1 = $conn->prepare($sqlDelRel);
  $stmt1->bind_param('i', $id);
  if (!$stmt1->execute()) throw new Exception($conn->error);
  $stmt1->close();

  // Borrar tipo
  $sql = "DELETE FROM tipos_de_evidencia WHERE id_tipo_evidencia = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $id);
  if (!$stmt->execute()) throw new Exception($conn->error);
  $stmt->close();

  $conn->commit();
  jexit(true, 'Tipo eliminado.');
} catch (Exception $e) {
  $conn->rollback();
  jexit(false, 'No se pudo eliminar: '.$e->getMessage());
}
