<?php
// tipo-evidencia-get.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

// Tipo
$sql = "SELECT id_tipo_evidencia, nombre_tipo, descripcion
        FROM tipos_de_evidencia WHERE id_tipo_evidencia = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!($row = $res->fetch_assoc())) {
  jexit(false, 'Tipo no encontrado.');
}
$stmt->close();

// Instrumentos relacionados
$rel = [];
$sql2 = "SELECT id_instrumento FROM instrumento_tipo_evidencia WHERE id_tipo_evidencia = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $id);
$stmt2->execute();
$rs2 = $stmt2->get_result();
while ($r = $rs2->fetch_assoc()) $rel[] = (int)$r['id_instrumento'];
$stmt2->close();

jexit(true, '', ['tipo' => $row, 'instrumentos' => $rel]);
