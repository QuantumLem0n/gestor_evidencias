<?php
// evidencia-insert.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
require_once 'validacion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$user_id = (int)($_SESSION['ID'] ?? 0);
$user_role = (int)($_SESSION['ROL'] ?? 0);


// Permitir crear a admin y docentes
if (!in_array($user_role, [1,4], true)) jexit(false, 'No tienes permisos para agregar evidencias.');

// Inputs
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$id_tipo= isset($_POST['id_tipo_evidencia']) ? (int)$_POST['id_tipo_evidencia'] : 0;

if (!$titulo || $id_tipo <= 0) jexit(false, 'Datos incompletos.');

if (!isset($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
  jexit(false, 'Debes seleccionar un archivo.');
}

// Validaciones de archivo
$maxSize = 10 * 1024 * 1024; // 10MB
$allowedMime = ['application/pdf','image/jpeg','image/png','image/webp'];
$allowedExt  = ['pdf','jpg','jpeg','png','webp'];

$size = (int)$_FILES['archivo']['size'];
if ($size <= 0 || $size > $maxSize) jexit(false, 'Archivo demasiado grande (máx. 10 MB).');

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['archivo']['tmp_name']);
finfo_close($finfo);

$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
if (!in_array($mime, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
  jexit(false, 'Tipo de archivo no permitido. Usa PDF o imagen (JPG/PNG/WEBP).');
}

// Generar nombre de archivo limpio
$slug = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($titulo));
$slug = trim($slug, '_');
if ($slug === '') $slug = 'evidencia';
$rand = substr(md5(uniqid('', true)), 0, 6);
$filename = $slug.'_'.date('Ymd_His').'_'.$rand.'.'.$ext;

$destDir = __DIR__ . '/uploads/files';
if (!is_dir($destDir)) {
  if (!mkdir($destDir, 0775, true)) jexit(false, 'No se pudo crear el directorio de archivos.');
}

$destPath = $destDir . '/' . $filename;
if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
  jexit(false, 'No se pudo mover el archivo.');
}

// Insert
$sql = "INSERT INTO evidencias (titulo, archivo, id_docente, id_tipo_evidencia, ocultar)
        VALUES (?, ?, ?, ?, 0)";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
$stmt->bind_param('ssii', $titulo, $filename, $user_id, $id_tipo);

if ($stmt->execute()) {
  $newId = $stmt->insert_id;
  $stmt->close();
  jexit(true, 'Evidencia creada.', ['id'=>$newId, 'archivo'=>$filename]);
} else {
  $err = $stmt->error ?: $conn->error;
  $stmt->close();
  // Si falla, intenta eliminar el archivo subido para no dejar basura
  @unlink($destPath);
  jexit(false, 'Error al crear: '.$err);
}
