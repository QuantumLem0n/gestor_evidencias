<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id_tipo          = isset($_POST['id_tipo_evidencia']) ? (int)$_POST['id_tipo_evidencia'] : 0;
$id_tipo_atributo = isset($_POST['id_tipo_atributo']) ? (int)$_POST['id_tipo_atributo'] : 0;
$nombre           = isset($_POST['nombre_atributo']) ? trim($_POST['nombre_atributo']) : '';
$slug             = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$descripcion      = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$orden            = (isset($_POST['orden']) && $_POST['orden'] !== '') ? (int)$_POST['orden'] : null;

$requerido        = isset($_POST['requerido']) ? 1 : 0;
$unico            = isset($_POST['unico_por_evidencia']) ? 1 : 0;
$multiple         = isset($_POST['multiple']) ? 1 : 0;

$min_len          = ($_POST['min_longitud'] ?? '') === '' ? null : (int)$_POST['min_longitud'];
$max_len          = ($_POST['max_longitud'] ?? '') === '' ? null : (int)$_POST['max_longitud'];
$min_val          = ($_POST['min_valor'] ?? '') === '' ? null : (float)$_POST['min_valor'];
$max_val          = ($_POST['max_valor'] ?? '') === '' ? null : (float)$_POST['max_valor'];
$opciones_json    = isset($_POST['opciones_json']) && trim($_POST['opciones_json']) !== '' ? trim($_POST['opciones_json']) : null;

if ($id_tipo <= 0 || $id_tipo_atributo <= 0 || !$nombre || !$slug) jexit(false, 'Datos incompletos.');
if (!preg_match('/^[a-z0-9_]+$/', $slug)) jexit(false, 'El slug solo permite minúsculas, dígitos y guion bajo.');
if (!is_null($min_len) && !is_null($max_len) && $min_len > $max_len) jexit(false, 'min_longitud no puede ser mayor que max_longitud.');
if (!is_null($min_val) && !is_null($max_val) && $min_val > $max_val) jexit(false, 'min_valor no puede ser mayor que max_valor.');

// Unicidad slug dentro del tipo
$sqlC = "SELECT 1 FROM atributos_tipo_evidencia WHERE id_tipo_evidencia = ? AND slug = ?";
$stmtC = $conn->prepare($sqlC);
if (!$stmtC) jexit(false, 'Error prep (unicidad): '.$conn->error);
$stmtC->bind_param('is', $id_tipo, $slug);
$stmtC->execute();
$stmtC->store_result();
if ($stmtC->num_rows > 0) { $stmtC->close(); jexit(false, 'Ya existe un atributo con ese slug en este tipo.'); }
$stmtC->close();

// Sugerir orden si viene vacío
if (is_null($orden)) {
  $stmtN = $conn->prepare("SELECT COALESCE(MAX(orden),0)+1 AS nexto FROM atributos_tipo_evidencia WHERE id_tipo_evidencia = ?");
  if ($stmtN) {
    $stmtN->bind_param('i', $id_tipo);
    $stmtN->execute();
    $stmtN->bind_result($nexto);
    $stmtN->fetch();
    $stmtN->close();
    $orden = (int)$nexto;
  } else {
    $orden = 1;
  }
}

// Validar JSON si aplica
if (!is_null($opciones_json)) {
  json_decode($opciones_json);
  if (json_last_error() !== JSON_ERROR_NONE) jexit(false, 'Opciones (JSON) inválido.');
}

// INSERT
$sql = "INSERT INTO atributos_tipo_evidencia
        (id_tipo_evidencia, id_tipo_atributo, nombre_atributo, slug, descripcion, orden,
         requerido, unico_por_evidencia, multiple, min_longitud, max_longitud, min_valor, max_valor, opciones_json)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);

// *** CADENA CORRECTA: 14 tipos ***
$stmt->bind_param(
  'iisssiiiiiidds',
  $id_tipo, $id_tipo_atributo, $nombre, $slug, $descripcion,
  $orden, $requerido, $unico, $multiple,
  $min_len, $max_len, $min_val, $max_val, $opciones_json
);

if ($stmt->execute()) {
  $newId = $stmt->insert_id;
  $stmt->close();
  jexit(true, 'Atributo creado.', ['id' => $newId]);
} else {
  $err = $stmt->error ?: $conn->error;
  $stmt->close();
  jexit(false, 'Error al crear: '.$err);
}
