<?php
// instrumento-get.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

$sql = "SELECT id_instrumento, abreviatura, nombre_completo, tipo_calificacion, min_calificacion, max_calificacion
        FROM instrumentos WHERE id_instrumento = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  jexit(true, '', ['instrumento' => $row]);
}
jexit(false, 'Instrumento no encontrado.');
