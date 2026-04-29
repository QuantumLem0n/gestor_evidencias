<?php
// evidencia-get.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
require_once 'validacion.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$user_id = (int)($_SESSION['ID'] ?? 0);
$user_role = (int)($_SESSION['ROL'] ?? 0);


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

$sql = "SELECT id_evidencia, titulo, archivo, id_docente, id_tipo_evidencia
        FROM evidencias
        WHERE id_evidencia = ? AND ocultar = 0";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$e = $res->fetch_assoc();
$stmt->close();

if (!$e) jexit(false, 'Evidencia no encontrada.');

// Permite que solo el docente dueño la edite (o admin si luego lo habilitas)
if ($user_role == 4 && (int)$e['id_docente'] !== (int)$user_id) {
  jexit(false, 'No puedes editar esta evidencia.');
}

jexit(true, '', ['evidencia' => $e]);
