<?php
// evidencia-update.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
require_once 'validacion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$user_id = (int)($_SESSION['ID'] ?? 0);
$user_role = (int)($_SESSION['ROL'] ?? 0);


// Solo docentes pueden editar sus evidencias (según tu requerimiento)
if ($user_role != 4) jexit(false, 'No tienes permisos para editar evidencias.');

$id_evi   = isset($_POST['id_evidencia']) ? (int)$_POST['id_evidencia'] : 0;
$titulo   = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$id_tipo  = isset($_POST['id_tipo_evidencia']) ? (int)$_POST['id_tipo_evidencia'] : 0;
$archivo_actual = $_POST['archivo_actual'] ?? '';

if ($id_evi <= 0 || !$titulo || $id_tipo <= 0) jexit(false, 'Datos incompletos.');

// Verificar propiedad
$q = "SELECT archivo, id_docente FROM evidencias WHERE id_evidencia = ? AND ocultar = 0";
$st = $conn->prepare($q);
if (!$st) jexit(false, 'Error de preparación: '.$conn->error);
$st->bind_param('i', $id_evi);
$st->execute();
$r = $st->get_result();
$cur = $r->fetch_assoc();
$st->close();
if (!$cur) jexit(false, 'Evidencia no encontrada.');
if ((int)$cur['id_docente'] !== (int)$user_id) jexit(false, 'No puedes editar esta evidencia.');

$newFileName = $cur['archivo']; // por defecto conserva

// ¿Hay archivo nuevo?
if (isset($_FILES['archivo']) && is_uploaded_file($_FILES['archivo']['tmp_name'])) {
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

  // generar nombre
  $slug = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($titulo));
  $slug = trim($slug, '_');
  if ($slug === '') $slug = 'evidencia';
  $rand = substr(md5(uniqid('', true)), 0, 6);
  $newFileName = $slug.'_'.date('Ymd_His').'_'.$rand.'.'.$ext;

  $destDir = __DIR__ . '/uploads/files';
  if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0775, true)) jexit(false, 'No se pudo crear el directorio de archivos.');
  }
  $destPath = $destDir . '/' . $newFileName;
  if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
    jexit(false, 'No se pudo mover el archivo.');
  }

  // eliminar archivo anterior (si existe y es diferente)
  if ($cur['archivo'] && $cur['archivo'] !== $newFileName) {
    $oldPath = $destDir . '/' . $cur['archivo'];
    if (is_file($oldPath)) @unlink($oldPath);
  }
}

// Update
$sql = "UPDATE evidencias
        SET titulo = ?, id_tipo_evidencia = ?, archivo = ?
        WHERE id_evidencia = ? AND id_docente = ? AND ocultar = 0";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
$stmt->bind_param('sisii', $titulo, $id_tipo, $newFileName, $id_evi, $user_id);

if ($stmt->execute()) {
  $stmt->close();
  jexit(true, 'Evidencia actualizada.');
} else {
  $err = $stmt->error ?: $conn->error;
  $stmt->close();
  jexit(false, 'Error al actualizar: '.$err);
}
