<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id               = isset($_POST['id_ate']) ? (int)$_POST['id_ate'] : 0;
$id_tipo_atributo = isset($_POST['id_tipo_atributo']) ? (int)$_POST['id_tipo_atributo'] : 0;
$nombre           = isset($_POST['nombre_atributo']) ? trim($_POST['nombre_atributo']) : '';
$slug             = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$descripcion      = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$orden            = (isset($_POST['orden']) && $_POST['orden'] !== '') ? (int)$_POST['orden'] : 1;

$requerido        = isset($_POST['requerido']) ? 1 : 0;
$unico            = isset($_POST['unico_por_evidencia']) ? 1 : 0;
$multiple         = isset($_POST['multiple']) ? 1 : 0;

$min_len          = ($_POST['min_longitud'] ?? '') === '' ? null : (int)$_POST['min_longitud'];
$max_len          = ($_POST['max_longitud'] ?? '') === '' ? null : (int)$_POST['max_longitud'];
$min_val          = ($_POST['min_valor'] ?? '') === '' ? null : (float)$_POST['min_valor'];
$max_val          = ($_POST['max_valor'] ?? '') === '' ? null : (float)$_POST['max_valor'];
$opciones_json    = isset($_POST['opciones_json']) && trim($_POST['opciones_json']) !== '' ? trim($_POST['opciones_json']) : null;

if ($id <= 0 || $id_tipo_atributo <= 0 || !$nombre || !$slug) jexit(false, 'Datos incompletos.');
if (!preg_match('/^[a-z0-9_]+$/', $slug)) jexit(false, 'El slug solo permite minúsculas, dígitos y guion bajo.');
if (!is_null($min_len) && !is_null($max_len) && $min_len > $max_len) jexit(false, 'min_longitud > max_longitud.');
if (!is_null($min_val) && !is_null($max_val) && $min_val > $max_val) jexit(false, 'min_valor > max_valor.');

// Obtener id_tipo para validar unicidad del slug
$qTipo = "SELECT id_tipo_evidencia FROM atributos_tipo_evidencia WHERE id_ate = ?";
$st = $conn->prepare($qTipo);
if (!$st) jexit(false, 'Error prep: '.$conn->error);
$st->bind_param('i', $id);
$st->execute();
$rst = $st->get_result();
$tipoRow = $rst->fetch_assoc();
$st->close();
if (!$tipoRow) jexit(false, 'Atributo inexistente.');
$id_tipo = (int)$tipoRow['id_tipo_evidencia'];

$sqlC = "SELECT 1 FROM atributos_tipo_evidencia WHERE id_tipo_evidencia = ? AND slug = ? AND id_ate <> ?";
$st2 = $conn->prepare($sqlC);
if (!$st2) jexit(false, 'Error prep (unicidad): '.$conn->error);
$st2->bind_param('isi', $id_tipo, $slug, $id);
$st2->execute();
$st2->store_result();
if ($st2->num_rows > 0) { $st2->close(); jexit(false, 'Ya existe otro atributo con ese slug en este tipo.'); }
$st2->close();

// Validar JSON si aplica
if (!is_null($opciones_json)) {
  json_decode($opciones_json);
  if (json_last_error() !== JSON_ERROR_NONE) jexit(false, 'Opciones (JSON) inválido.');
}

// UPDATE
$sql = "UPDATE atributos_tipo_evidencia
        SET id_tipo_atributo=?, nombre_atributo=?, slug=?, descripcion=?, orden=?,
            requerido=?, unico_por_evidencia=?, multiple=?, min_longitud=?, max_longitud=?,
            min_valor=?, max_valor=?, opciones_json=?
        WHERE id_ate=?";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);

// *** CADENA CORRECTA: 14 tipos ***
$stmt->bind_param(
  'isssiiiiiiddsi',
  $id_tipo_atributo, $nombre, $slug, $descripcion,
  $orden, $requerido, $unico, $multiple,
  $min_len, $max_len, $min_val, $max_val,
  $opciones_json, $id
);

if ($stmt->execute()) {
  $stmt->close();
  jexit(true, 'Atributo actualizado.');
} else {
  $err = $stmt->error ?: $conn->error;
  $stmt->close();
  jexit(false, 'Error al actualizar: '.$err);
}
