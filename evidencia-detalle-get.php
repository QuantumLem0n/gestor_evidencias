<?php
header('Content-Type: application/json; charset=utf-8');
include 'validacion.php';
include 'conexion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'Parámetro faltante']); exit; }

// evidence + owner + type
$sql = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia,
               t.nombre_tipo
        FROM evidencias e
        LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
        WHERE e.id_evidencia = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$re = $stmt->get_result();
$evi = $re ? $re->fetch_assoc() : null;
$stmt->close();

if (!$evi) { echo json_encode(['status'=>'error','message'=>'Evidencia no encontrada']); exit; }

// Only role 4 if owner; others can read (1,2,3)
if ($user_role === 4 && (int)$evi['id_docente'] !== $user_id) {
  echo json_encode(['status'=>'error','message'=>'No autorizado']); exit;
}

// attributes
$sqlA = "SELECT a.*,
                ta.id_tipo_atributo, ta.nombre_tipo, ta.slug AS tipo_slug, ta.grupo_storage, ta.validador_regex
         FROM atributos_tipo_evidencia a
         INNER JOIN tipos_atributo ta ON ta.id_tipo_atributo = a.id_tipo_atributo
         WHERE a.id_tipo_evidencia = ?
         ORDER BY a.orden ASC, a.id_ate ASC";
$stmt = $conn->prepare($sqlA);
$stmt->bind_param('i', $evi['id_tipo_evidencia']);
$stmt->execute();
$ra = $stmt->get_result();
$attrs = $ra ? $ra->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// values
$sqlV = "SELECT v.*
         FROM evidencia_valores_atributo v
         WHERE v.id_evidencia = ?
         ORDER BY v.id_ate ASC, v.indice ASC, v.id_eva ASC";
$stmt = $conn->prepare($sqlV);
$stmt->bind_param('i', $id);
$stmt->execute();
$rv = $stmt->get_result();
$vals = [];
if ($rv) {
  while ($r = $rv->fetch_assoc()) {
    $vals[(int)$r['id_ate']][] = $r;
  }
}
$stmt->close();

// attach values
foreach ($attrs as &$a) {
  $aid = (int)$a['id_ate'];
  $a['valores'] = $vals[$aid] ?? [];
}
unset($a);

echo json_encode([
  'status' => 'ok',
  'evidencia' => $evi,
  'atributos' => $attrs
]);
