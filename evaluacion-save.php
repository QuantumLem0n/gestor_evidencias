<?php
// evaluacion-save.php
header('Content-Type: application/json; charset=utf-8');
include 'validacion.php';
include 'conexion.php';

function jexit($ok,$msg='',$extra=[]){
  echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$msg],$extra));
  exit;
}

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

// Permisos: rol 4 (docente) no califica
if ($user_role === 4) jexit(false,'No autorizado para evaluar.');

$id_evidencia   = isset($_POST['id_evidencia'])   ? (int)$_POST['id_evidencia']   : 0;
$id_instrumento = isset($_POST['id_instrumento']) ? (int)$_POST['id_instrumento'] : 0;
$resultado_raw  = isset($_POST['resultado'])      ? trim($_POST['resultado'])      : '';
$comentario     = isset($_POST['comentario'])     ? trim($_POST['comentario'])     : '';

if ($id_evidencia<=0 || $id_instrumento<=0 || $resultado_raw==='') {
  jexit(false,'Datos incompletos');
}

// Verifica evidencia y que esté al 100% de atributos
$qE = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia,
              COALESCE(ta.total,0)  AS total_attrs,
              COALESCE(vv.filled,0) AS filled_attrs
       FROM evidencias e
       LEFT JOIN (
         SELECT id_tipo_evidencia, COUNT(*) AS total
         FROM atributos_tipo_evidencia
         GROUP BY id_tipo_evidencia
       ) ta ON ta.id_tipo_evidencia = e.id_tipo_evidencia
       LEFT JOIN (
         SELECT id_evidencia, COUNT(DISTINCT id_ate) AS filled
         FROM evidencia_valores_atributo
         GROUP BY id_evidencia
       ) vv ON vv.id_evidencia = e.id_evidencia
       WHERE e.id_evidencia = ?";
$sE = $conn->prepare($qE);
$sE->bind_param('i', $id_evidencia);
$sE->execute();
$re = $sE->get_result();
$ev = $re ? $re->fetch_assoc() : null;
$sE->close();

if (!$ev) jexit(false,'Evidencia no encontrada');
if ((int)$ev['total_attrs'] <= 0 || (int)$ev['filled_attrs'] < (int)$ev['total_attrs']) {
  jexit(false,'La evidencia no está al 100%');
}

// Verifica relación instrumento <-> tipo y lee configuración real
$qI = "SELECT i.id_instrumento,
              i.abreviatura,
              i.tipo_calificacion,
              i.min_calificacion,
              i.max_calificacion
       FROM instrumento_tipo_evidencia ite
       JOIN instrumentos i
            ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
       WHERE ite.id_tipo_evidencia = ? AND ite.id_instrumento = ?";
$sI = $conn->prepare($qI);
$tid = (int)$ev['id_tipo_evidencia'];
$sI->bind_param('ii', $tid, $id_instrumento);
$sI->execute();
$ri   = $sI->get_result();
$inst = $ri ? $ri->fetch_assoc() : null;
$sI->close();

if (!$inst) jexit(false,'Instrumento no asociado al tipo de la evidencia');

$isNumeric = ($inst['tipo_calificacion'] === 'NUMERICA');

// Normaliza y valida el resultado
if ($isNumeric) {
  $min = is_null($inst['min_calificacion']) ? 0   : (float)$inst['min_calificacion'];
  $max = is_null($inst['max_calificacion']) ? 10  : (float)$inst['max_calificacion'];
  if (!is_numeric($resultado_raw)) jexit(false,'El valor debe ser numérico.');
  $val = (float)$resultado_raw;
  if ($val < $min || $val > $max) {
    jexit(false, "El valor debe estar entre {$min} y {$max}.");
  }
} else {
  if ($resultado_raw !== '0' && $resultado_raw !== '1') {
    jexit(false,'Resultado inválido (use 1=aprobado, 0=no aprobado).');
  }
  $val = (int)$resultado_raw; // 0/1
}

// Asegura UPSERT por (id_evidencia, id_instrumento)
// Requiere UNIQUE KEY en esa pareja (ver nota abajo)
$sql = "INSERT INTO calificacion_evidencia
          (id_evidencia, id_instrumento, resultado, comentario, id_usuario_eval)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          resultado      = VALUES(resultado),
          comentario     = VALUES(comentario),
          id_usuario_eval= VALUES(id_usuario_eval),
          actualizado_en = CURRENT_TIMESTAMP";

$st = $conn->prepare($sql);
/* tipos: i (int) i (int) d (double/decimal) s (string) i (int) */
$st->bind_param('iidsi', $id_evidencia, $id_instrumento, $val, $comentario, $user_id);

if ($st->execute()) {
  $st->close();
  jexit(true, 'Guardado');
} else {
  $err = $conn->error;
  $st->close();
  jexit(false, 'Error al guardar: '.$err);
}
