<?php
// evaluacion-get.php
header('Content-Type: application/json; charset=utf-8');
include 'validacion.php';
include 'conexion.php';

function jexit($ok,$msg='',$extra=[]){
  echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$msg],$extra));
  exit;
}

$user_id  = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role= isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;
$id       = isset($_GET['id'])      ? (int)$_GET['id']      : 0;

if ($id <= 0) jexit(false,'Parámetros inválidos');

// Datos de la evidencia
$sqlE = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia, t.nombre_tipo
         FROM evidencias e
         LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
         WHERE e.id_evidencia = ?";
$stmtE = $conn->prepare($sqlE);
$stmtE->bind_param('i', $id);
$stmtE->execute();
$re = $stmtE->get_result();
$evi = $re ? $re->fetch_assoc() : null;
$stmtE->close();

if (!$evi) jexit(false,'Evidencia no encontrada');

// Docente solo su evidencia
if ($user_role === 4 && (int)$evi['id_docente'] !== (int)$user_id) {
  jexit(false,'No autorizado');
}

// OJO: la tabla puente correcta es instrumento_tipo_evidencia
$sql = "SELECT
          i.id_instrumento,
          i.abreviatura,
          i.nombre_completo,
          /* Derivados para que el frontend NO cambie */
          CASE WHEN i.tipo_calificacion = 'NUMERICA' THEN 1 ELSE 0 END AS es_numerico,
          CASE WHEN i.tipo_calificacion = 'NUMERICA' THEN COALESCE(i.min_calificacion, 0)  ELSE 0  END AS cal_min,
          CASE WHEN i.tipo_calificacion = 'NUMERICA' THEN COALESCE(i.max_calificacion, 10) ELSE 1  END AS cal_max,
          ce.resultado,
          ce.comentario,
          ce.calificado_en,
          ce.actualizado_en
        FROM instrumento_tipo_evidencia ite
        JOIN instrumentos i
              ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
        LEFT JOIN calificacion_evidencia ce
              ON ce.id_instrumento = i.id_instrumento
             AND ce.id_evidencia   = ?
        WHERE ite.id_tipo_evidencia = ?
        ORDER BY i.id_instrumento ASC";

$stmt = $conn->prepare($sql);
$tid  = (int)$evi['id_tipo_evidencia'];
$stmt->bind_param('ii', $id, $tid);
$stmt->execute();
$rs = $stmt->get_result();

$rows = [];
if ($rs) {
  while ($r = $rs->fetch_assoc()) {
    // Normaliza tipos para el JSON
    $r['es_numerico'] = (int)$r['es_numerico'];
    if (array_key_exists('resultado', $r)) {
      if (is_null($r['resultado'])) {
        $r['resultado'] = null;
      } else {
        // Si es numérico lo regresamos float; si es aprobación (0/1) va como int pero no rompemos front.
        $r['resultado'] = ($r['es_numerico'] === 1) ? (float)$r['resultado'] : (int)$r['resultado'];
      }
    }
    $rows[] = $r;
  }
}
$stmt->close();

jexit(true, '', ['evidencia'=>$evi, 'instrumentos'=>$rows]);
