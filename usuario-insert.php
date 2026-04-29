<?php
// usuario-insert.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$nombre    = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellidop = isset($_POST['apellidop']) ? trim($_POST['apellidop']) : '';
$apellidom = isset($_POST['apellidom']) ? trim($_POST['apellidom']) : '';
$correo    = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';
$rol       = $_POST['rol'] ?? '';
$activo    = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

if (!$nombre || !$apellidop || !$apellidom || !$correo || !$password || !$confirm || !$rol) {
  jexit(false, 'Datos incompletos.');
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  jexit(false, 'Correo inválido.');
}
if ($password !== $confirm) {
  jexit(false, 'Las contraseñas no coinciden.');
}

// ¿correo ya existe?
$sql_check = "SELECT id_usuario FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  jexit(false, 'El correo ya está en uso.');
}
$stmt->close();

// Hash
$password_hashed = password_hash($password, PASSWORD_BCRYPT);

// Insert
$sql_insert = "INSERT INTO usuarios (nombre, apellidop, apellidom, correo, password, rol, activo, created_at, updated_at)
               VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
$stmt2 = $conn->prepare($sql_insert);
$stmt2->bind_param("sssssis", $nombre, $apellidop, $apellidom, $correo, $password_hashed, $rol, $activo);

if ($stmt2->execute()) {
  $newId = $stmt2->insert_id;
  $stmt2->close();
  jexit(true, 'Usuario creado.', ['id' => $newId]);
} else {
  $err = $conn->error;
  $stmt2->close();
  jexit(false, 'Error al registrar usuario: '.$err);
}
