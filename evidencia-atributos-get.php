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

// values (todos los valores de esta evidencia, por atributo)
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
    $aid = (int)$r['id_ate'];
    if (!isset($vals[$aid])) $vals[$aid] = [];
    $vals[$aid][] = $r;
  }
}
$stmt->close();

// Helper de renderizado por tipo/grupo
function render_valor($aMeta, $row) {
  $grupo = $aMeta['grupo_storage'] ?? '';
  $slug  = $aMeta['tipo_slug'] ?? '';

  switch ($grupo) {
    case 'texto_corto':
      // Slugs especiales siguen siendo texto (doi, isbn, issn, url, email)
      return (string)($row['valor_texto'] ?? '');

    case 'texto_largo':
      return (string)($row['valor_largo'] ?? '');

    case 'entero':
      return ($row['valor_int'] === null) ? '' : (string)$row['valor_int'];

    case 'decimal':
      if ($row['valor_decimal'] === null) return '';
      // Formatea con máximo 2 decimales si aplica
      $num = (float)$row['valor_decimal'];
      return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');

    case 'fecha':
      if (!$row['valor_fecha']) return '';
      // Muestra YYYY-MM-DD tal cual o formateado
      return (string)$row['valor_fecha'];

    case 'booleano':
      if ($row['valor_bool'] === null) return '';
      return ((int)$row['valor_bool'] === 1) ? 'Sí' : 'No';

    case 'archivo':
      // Puedes ajustar a basename o ruta completa
      return (string)($row['valor_archivo'] ?? '');

    case 'json':
      if (!$row['valor_json']) return '';
      // Devuelve JSON compacto
      $decoded = json_decode($row['valor_json'], true);
      return $decoded === null ? (string)$row['valor_json'] : json_encode($decoded, JSON_UNESCAPED_UNICODE);

    default:
      // fallback por si faltara configurar algún grupo
      foreach (['valor_texto','valor_largo','valor_int','valor_decimal','valor_fecha','valor_bool','valor_archivo','valor_json'] as $k) {
        if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
          return (string)$row[$k];
        }
      }
      return '';
  }
}

// Adjunta valores + valores_render (texto ya listo)
foreach ($attrs as &$a) {
  $aid = (int)$a['id_ate'];
  $lista = $vals[$aid] ?? [];
  $rend  = [];
  foreach ($lista as $r) {
    $txt = render_valor($a, $r);
    $rend[] = $txt;
  }
  $a['valores'] = $lista;            // crudos (por si los necesitas)
  $a['valores_render'] = $rend;      // listos para mostrar
}
unset($a);

echo json_encode([
  'status' => 'ok',
  'evidencia' => $evi,
  'atributos' => $attrs
]);
