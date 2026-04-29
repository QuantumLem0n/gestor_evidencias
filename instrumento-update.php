<?php
// instrumento-update.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id   = isset($_POST['id_instrumento']) ? (int)$_POST['id_instrumento'] : 0;
$tipo = isset($_POST['tipo_calificacion']) ? trim($_POST['tipo_calificacion']) : 'APROBACION';

// Nombre/abreviatura NO se reciben ni se actualizan

if ($id <= 0) jexit(false, 'ID inválido.');
if ($tipo !== 'APROBACION' && $tipo !== 'NUMERICA') jexit(false, 'Tipo de calificación inválido.');

$min = isset($_POST['min_calificacion']) && $_POST['min_calificacion'] !== '' ? (float)$_POST['min_calificacion'] : null;
$max = isset($_POST['max_calificacion']) && $_POST['max_calificacion'] !== '' ? (float)$_POST['max_calificacion'] : null;

if ($tipo === 'NUMERICA') {
  if ($min === null || $max === null) jexit(false, 'Para calificación numérica, mínimo y máximo son requeridos.');
  if (!is_numeric($min) || !is_numeric($max)) jexit(false, 'Mínimo y máximo deben ser numéricos.');
  if ($min > $max) jexit(false, 'El mínimo no puede ser mayor que el máximo.');
} else {
  // APROBACION: ignora rango
  $min = null;
  $max = null;
}

$sql = "UPDATE instrumentos
        SET tipo_calificacion = ?, min_calificacion = ?, max_calificacion = ?
        WHERE id_instrumento = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sddi', $tipo, $min, $max, $id);
if ($stmt->execute()) {
  $stmt->close();
  jexit(true, 'Instrumento actualizado.');
} else {
  $err = $conn->error;
  $stmt->close();
  jexit(false, 'Error al actualizar: '.$err);
}
