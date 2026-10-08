<?php
// usuario-update.php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once 'conexion.php';
require_once 'permisos-instrumentos.php';
exigirGestion($conn, 'gestion-usuarios.php');

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id        = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
$nombre    = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellidop = isset($_POST['apellidop']) ? trim($_POST['apellidop']) : '';
$apellidom = isset($_POST['apellidom']) ? trim($_POST['apellidom']) : '';
$correo    = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$rol       = isset($_POST['rol']) ? (int)$_POST['rol'] : 0;
$activo    = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

$pass      = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';

if ($id <= 0 || !$nombre || !$apellidop || !$apellidom || !$correo || !$rol) {
  jexit(false, 'Datos incompletos.');
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  jexit(false, 'Correo inválido.');
}

// Verifica correo duplicado (otro usuario)
$sqlC = "SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario <> ?";
$stmtC = $conn->prepare($sqlC);
$stmtC->bind_param('si', $correo, $id);
$stmtC->execute();
$stmtC->store_result();
if ($stmtC->num_rows > 0) {
  $stmtC->close();
  jexit(false, 'El correo ya está en uso por otro usuario.');
}
$stmtC->close();

$conn->begin_transaction();
try {
$existente = $conn->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario=? FOR UPDATE');
$existente->bind_param('i', $id);
$existente->execute();
if (!$existente->get_result()->fetch_assoc()) throw new RuntimeException('Usuario inexistente.');
if ($pass || $confirm) {
  if ($pass !== $confirm) jexit(false, 'Las contraseñas no coinciden.');
  $hash = password_hash($pass, PASSWORD_BCRYPT);
  $sql = "UPDATE usuarios
          SET nombre=?, apellidop=?, apellidom=?, correo=?, rol=?, activo=?, password=?, updated_at=NOW()
          WHERE id_usuario=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ssssissi', $nombre, $apellidop, $apellidom, $correo, $rol, $activo, $hash, $id);
} else {
  $sql = "UPDATE usuarios
          SET nombre=?, apellidop=?, apellidom=?, correo=?, rol=?, activo=?, updated_at=NOW()
          WHERE id_usuario=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ssssisi', $nombre, $apellidop, $apellidom, $correo, $rol, $activo, $id);
}

if ($stmt->execute()) {
  $stmt->close();
  guardarInstrumentosEvaluador($conn, $id, (int)$rol, $_POST['instrumentos'] ?? []);
  $conn->commit();
  jexit(true, 'Usuario actualizado.');
} else {
  $err = $conn->error;
  $stmt->close();
  jexit(false, 'Error al actualizar: '.$err);
}

} catch (Throwable $e) {
  $conn->rollback();
  jexit(false, 'No se pudo guardar el usuario. Verifica los datos y los instrumentos seleccionados.');
}
