<?php
// evidencia-eliminar.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
require_once 'validacion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$user_id = (int)($_SESSION['ID'] ?? 0);
$user_role = (int)($_SESSION['ROL'] ?? 0);


$id = isset($_POST['id_evidencia']) ? (int)$_POST['id_evidencia'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

// Admin puede ocultar cualquiera; docente solo las suyas
if ($user_role == 1) {
  $sql = "UPDATE evidencias SET ocultar = 1 WHERE id_evidencia = ? AND ocultar = 0";
  $stmt = $conn->prepare($sql);
  if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
  $stmt->bind_param('i', $id);
} elseif ($user_role == 4) {
  $sql = "UPDATE evidencias SET ocultar = 1 WHERE id_evidencia = ? AND id_docente = ? AND ocultar = 0";
  $stmt = $conn->prepare($sql);
  if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
  $stmt->bind_param('ii', $id, $user_id);
} else {
  jexit(false, 'No tienes permisos para ocultar evidencias.');
}

if ($stmt->execute()) {
  $stmt->close();
  jexit(true, 'Evidencia ocultada.');
} else {
  $err = $stmt->error ?: $conn->error;
  $stmt->close();
  jexit(false, 'No se pudo ocultar: '.$err);
}
