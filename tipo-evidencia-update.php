<?php
// tipo-evidencia-update.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id     = isset($_POST['id_tipo_evidencia']) ? (int)$_POST['id_tipo_evidencia'] : 0;
$nombre = isset($_POST['nombre_tipo']) ? trim($_POST['nombre_tipo']) : '';
$desc   = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$insts  = isset($_POST['instrumentos']) && is_array($_POST['instrumentos']) ? $_POST['instrumentos'] : [];

if ($id <= 0) jexit(false, 'ID inválido.');
if (!$nombre) jexit(false, 'El nombre del tipo es obligatorio.');
if (mb_strlen($nombre) > 100) jexit(false, 'El nombre supera 100 caracteres.');

// Unicidad excluyendo el propio id
$sqlC = "SELECT 1 FROM tipos_de_evidencia WHERE nombre_tipo = ? AND id_tipo_evidencia <> ?";
$stmt = $conn->prepare($sqlC);
$stmt->bind_param('si', $nombre, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  jexit(false, 'Ya existe otro tipo con ese nombre.');
}
$stmt->close();

$conn->begin_transaction();
try {
  // Update tipo
  $sqlU = "UPDATE tipos_de_evidencia SET nombre_tipo = ?, descripcion = ? WHERE id_tipo_evidencia = ?";
  $stmtU = $conn->prepare($sqlU);
  $stmtU->bind_param('ssi', $nombre, $desc, $id);
  if (!$stmtU->execute()) throw new Exception($conn->error);
  $stmtU->close();

  // Reemplazar relaciones
  $sqlDel = "DELETE FROM instrumento_tipo_evidencia WHERE id_tipo_evidencia = ?";
  $stmtD = $conn->prepare($sqlDel);
  $stmtD->bind_param('i', $id);
  if (!$stmtD->execute()) throw new Exception($conn->error);
  $stmtD->close();

  if (!empty($insts)) {
    $sqlIns = "INSERT IGNORE INTO instrumento_tipo_evidencia (id_instrumento, id_tipo_evidencia) VALUES (?, ?)";
    $stmtI = $conn->prepare($sqlIns);
    foreach ($insts as $iid) {
      $iid = (int)$iid;
      if ($iid <= 0) continue;
      $stmtI->bind_param('ii', $iid, $id);
      if (!$stmtI->execute()) throw new Exception($conn->error);
    }
    $stmtI->close();
  }

  $conn->commit();
  jexit(true, 'Tipo actualizado.');
} catch (Exception $e) {
  $conn->rollback();
  jexit(false, 'Error al actualizar: '.$e->getMessage());
}
