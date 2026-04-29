<?php
// descargas-api.php
declare(strict_types=1);

// --- Endpoints limpios: JSON / ZIP, sin HTML mezclado ---
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if (function_exists('header_remove')) { header_remove('X-Powered-By'); }

// Arranca buffers para poder limpiar cualquier salida previa
if (ob_get_level() === 0) { ob_start(); }

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/conexion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

const NUMERIC_PASS_PCT = 0.60;

// ---------- Utilidades ----------
function safe_filename(string $name): string {
  // Mantiene letras con acentos (unicode) y caracteres seguros
  $name = preg_replace('/[^\p{L}\p{N} _\.\-]/u', '', $name);
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if ($name === '') $name = 'archivo';
  if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);
  return $name;
}

function json_out($data, int $code = 200): void {
  // Limpia cualquier salida previa (evita que rompa el JSON)
  while (ob_get_level() > 0) { ob_end_clean(); }
  http_response_code($code);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function text_out(string $text, int $code = 200): void {
  while (ob_get_level() > 0) { ob_end_clean(); }
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $text;
  exit;
}

function get_aprobadas(mysqli $conn, int $instId, int $userId, int $userRole): array {
  $sql = "SELECT
            e.id_evidencia, e.titulo, e.archivo, e.id_docente,
            i.id_instrumento, i.abreviatura, i.nombre_completo, i.tipo_calificacion,
            COALESCE(i.min_calificacion,0)  AS min_cal,
            COALESCE(i.max_calificacion,10) AS max_cal,
            ce.resultado
          FROM evidencias e
          JOIN instrumento_tipo_evidencia ite
                ON ite.id_tipo_evidencia = e.id_tipo_evidencia
               AND ite.id_instrumento    = ?
          JOIN instrumentos i
                ON i.id_instrumento = ite.id_instrumento
               AND i.activo = 1
          LEFT JOIN calificacion_evidencia ce
                ON ce.id_evidencia  = e.id_evidencia
               AND ce.id_instrumento = i.id_instrumento
          WHERE e.ocultar = 0";
  $types = 'i'; $params = [$instId];
  if ($userRole === 4) { // docente: solo sus evidencias
    $sql .= " AND e.id_docente = ? ";
    $types .= 'i'; $params[] = $userId;
  }
  $sql .= " ORDER BY e.id_evidencia DESC";

  $stmt = $conn->prepare($sql);
  if (!$stmt) { return []; }
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();

  $out = [];
  while ($res && ($r = $res->fetch_assoc())) {
    $resRaw = $r['resultado'];
    if ($resRaw === null || $resRaw === '') continue;

    $approved = false;
    if ($r['tipo_calificacion'] === 'NUMERICA') {
      $min = (float)$r['min_cal']; $max = (float)$r['max_cal'];
      $umbral = $min + NUMERIC_PASS_PCT * ($max - $min);
      $approved = ((float)$resRaw >= $umbral);
    } else {
      // APROBACION (binaria)
      $approved = ((float)$resRaw >= 1.0);
    }

    if ($approved && !empty($r['archivo'])) {
      $out[] = [
        'id'      => (int)$r['id_evidencia'],
        'titulo'  => (string)$r['titulo'],
        'archivo' => (string)$r['archivo'],
        'inst'    => [
          'id'   => (int)$r['id_instrumento'],
          'abbr' => (string)$r['abreviatura'],
          'name' => (string)$r['nombre_completo'],
          'tipo' => (string)$r['tipo_calificacion'],
        ],
      ];
    }
  }
  $stmt->close();
  return $out;
}

// ---------- Router ----------
$action = $_GET['action'] ?? '';

if ($action === 'list') {
  $instId = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;
  if ($instId <= 0) json_out(['status'=>'error','message'=>'Instrumento inválido'], 400);

  $items = get_aprobadas($conn, $instId, $user_id, $user_role);

  // Trae título/abbr del instrumento (aunque no haya aprobadas)
  $instAbbr = ''; $instName = '';
  if ($q = $conn->prepare("SELECT abreviatura, nombre_completo FROM instrumentos WHERE id_instrumento=?")) {
    $q->bind_param('i', $instId);
    $q->execute();
    if ($r = $q->get_result()->fetch_assoc()) {
      $instAbbr = (string)$r['abreviatura']; $instName = (string)$r['nombre_completo'];
    }
    $q->close();
  }

  json_out([
    'status' => 'ok',
    'instrumento' => ['id'=>$instId, 'abbr'=>$instAbbr, 'name'=>$instName],
    'items' => $items
  ]);
}

if ($action === 'zip') {
  $instId = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;
  $idsStr = $_GET['ids'] ?? '';
  if ($instId <= 0 || $idsStr === '') text_out('Parámetros inválidos', 400);

  $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsStr)), fn($v)=>$v>0)));
  if (empty($ids)) text_out('IDs inválidos', 400);

  $aprobadas = get_aprobadas($conn, $instId, $user_id, $user_role);
  if (empty($aprobadas)) text_out('No hay aprobadas', 404);

  $idx = [];
  foreach ($aprobadas as $it) $idx[$it['id']] = $it;
  $seleccion = [];
  foreach ($ids as $i) if (isset($idx[$i])) $seleccion[] = $idx[$i];
  if (empty($seleccion)) text_out('Ninguna evidencia válida', 404);

  // Si no hay ZipArchive, fallback: descargas múltiples
  if (!class_exists('ZipArchive')) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Descargas</title></head><body>";
    foreach ($seleccion as $s) {
      $href = 'uploads/files/'.rawurlencode($s['archivo']);
      $href = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
      echo "<a href=\"{$href}\" download style=\"display:none;\" class=\"dl\"></a>";
    }
    echo "<script>document.querySelectorAll('.dl').forEach((a,i)=>setTimeout(()=>a.click(), i*400));</script>";
    echo "<p>Iniciando descargas… Puedes cerrar esta pestaña.</p>";
    echo "</body></html>";
    exit;
  }

  // Crear ZIP temporal
  $tmpBase = tempnam(sys_get_temp_dir(), 'odea_zip_');
  @unlink($tmpBase);
  $zipPath = $tmpBase . '.zip';

  $zip = new ZipArchive();
  if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) {
    text_out('No se pudo crear el ZIP', 500);
  }

  $added = 0;
  foreach ($seleccion as $s) {
    $rel = 'uploads/files/'.$s['archivo'];
    $abs = __DIR__ . '/' . $rel; // ruta absoluta
    if (!is_file($abs)) continue;
    $ext  = pathinfo($abs, PATHINFO_EXTENSION);
    $name = safe_filename($s['titulo']);
    $entry = sprintf('%03d - %s%s', (int)$s['id'], $name, $ext ? ('.'.$ext) : '');
    $zip->addFile($abs, $entry);
    $added++;
  }
  $zip->close();

  if ($added === 0 || !is_file($zipPath)) {
    @is_file($zipPath) && @unlink($zipPath);
    text_out('No se agregaron archivos', 404);
  }

  // Nombre del ZIP con abreviatura del instrumento
  $abbr = '';
  if ($q = $conn->prepare("SELECT abreviatura FROM instrumentos WHERE id_instrumento=?")) {
    $q->bind_param('i', $instId);
    $q->execute();
    if ($r = $q->get_result()->fetch_assoc()) $abbr = (string)$r['abreviatura'];
    $q->close();
  }

  while (ob_get_level() > 0) { ob_end_clean(); }

  $fname = 'evidencias_'.$abbr.'_'.date('Ymd_His').'.zip';
  header('Content-Type: application/zip');
  header('Content-Length: '.filesize($zipPath));
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  readfile($zipPath);
  @unlink($zipPath);
  exit;
}

// Acción no válida
text_out('Acción inválida', 400);
