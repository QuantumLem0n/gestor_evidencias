<?php
// tipo-evidencia-insert.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$nombre = isset($_POST['nombre_tipo']) ? trim($_POST['nombre_tipo']) : '';
$desc   = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$insts  = isset($_POST['instrumentos']) && is_array($_POST['instrumentos']) ? $_POST['instrumentos'] : [];

if (!$nombre) jexit(false, 'El nombre del tipo es obligatorio.');
if (mb_strlen($nombre) > 100) jexit(false, 'El nombre supera 100 caracteres.');

// Unicidad
$sqlC = "SELECT 1 FROM tipos_de_evidencia WHERE nombre_tipo = ?";
$stmt = $conn->prepare($sqlC);
$stmt->bind_param('s', $nombre);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  jexit(false, 'Ya existe un tipo con ese nombre.');
}
$stmt->close();

$conn->begin_transaction();
try {
  // Insert tipo
  $sqlI = "INSERT INTO tipos_de_evidencia (nombre_tipo, descripcion) VALUES (?, ?)";
  $stmtI = $conn->prepare($sqlI);
  $stmtI->bind_param('ss', $nombre, $desc);
  if (!$stmtI->execute()) throw new Exception($conn->error);
  $newId = $stmtI->insert_id;
  $stmtI->close();

  // Insert relaciones (si hay)
  if (!empty($insts)) {
    $sqlR = "INSERT IGNORE INTO instrumento_tipo_evidencia (id_instrumento, id_tipo_evidencia) VALUES (?, ?)";
    $stmtR = $conn->prepare($sqlR);
    foreach ($insts as $iid) {
      $iid = (int)$iid;
      if ($iid <= 0) continue;
      $stmtR->bind_param('ii', $iid, $newId);
      if (!$stmtR->execute()) throw new Exception($conn->error);
    }
    $stmtR->close();
  }

  $conn->commit();
  jexit(true, 'Tipo creado.', ['id' => $newId]);
} catch (Exception $e) {
  $conn->rollback();
  jexit(false, 'Error al crear: '.$e->getMessage());
}
