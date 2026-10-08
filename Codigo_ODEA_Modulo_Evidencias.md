# Código fuente seleccionado

## Organizador de Evidencias Académicas (ODEA)

Este documento reúne el código fuente representativo del módulo de gestión, detalle, evaluación, descarga y reporte de evidencias académicas de la plataforma ODEA. No incluye la totalidad del sistema; se seleccionó este módulo porque concentra la actividad principal de la plataforma: recepción de documentos, captura de metadatos, validación, evaluación por instrumentos y generación de salidas para los usuarios.

La selección está pensada como anexo de código para documentación académica. El volumen resulta mayor al ejemplo de referencia porque incluye el ciclo completo de trabajo de una evidencia, desde la carga inicial hasta su calificación y exportación.

## Módulo seleccionado

**Módulo:** Gestión y evaluación de evidencias académicas.

**Justificación de selección:** este módulo es el núcleo funcional del proyecto, ya que permite que el docente registre una evidencia, complete sus atributos dinámicos, y que posteriormente un evaluador revise la evidencia conforme a los instrumentos configurados en la base de datos. También se incluyen las salidas de descarga y reporte, debido a que cierran el flujo de aprovechamiento de la información.

## Archivos incluidos

| Archivo | Función dentro del módulo |
|---|---|
| `evidencia-insert.php` | Alta de evidencia y validación de archivo |
| `evidencia-atributos-get.php` | Consulta de atributos dinámicos y valores capturados |
| `evidencia-detalle-save.php` | Guardado transaccional de atributos dinámicos |
| `evaluacion-get.php` | Consulta de instrumentos calificables por evidencia |
| `evaluacion-save.php` | Registro y actualización de calificaciones |
| `vista-evaluacion.php` | Vista tabular de evidencias listas para evaluar |
| `descargas-api.php` | Listado y descarga ZIP de evidencias aprobadas |
| `calificaciones-pdf.php` | Generación de reporte PDF por instrumento |

## Código

### evidencia-insert.php

Alta de evidencia y validación de archivo.

```php
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
```

### evidencia-atributos-get.php

Consulta de atributos dinámicos y valores capturados.

```php
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
```

### evidencia-detalle-save.php

Guardado transaccional de atributos dinámicos.

```php
<?php
header('Content-Type: application/json; charset=utf-8');
include 'validacion.php';
include 'conexion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$id_evidencia = isset($_POST['id_evidencia']) ? (int)$_POST['id_evidencia'] : 0;
if ($id_evidencia <= 0) { echo json_encode(['status'=>'error','message'=>'Falta id_evidencia']); exit; }

// evidence
$sql = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia FROM evidencias e WHERE e.id_evidencia = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_evidencia);
$stmt->execute();
$re = $stmt->get_result();
$evi = $re ? $re->fetch_assoc() : null;
$stmt->close();

if (!$evi) { echo json_encode(['status'=>'error','message'=>'Evidencia no encontrada']); exit; }
if (!($user_role === 4 && (int)$evi['id_docente'] === $user_id)) {
  echo json_encode(['status'=>'error','message'=>'No autorizado para editar']); exit;
}

// attributes for this type
$sqlA = "SELECT a.*,
                ta.id_tipo_atributo, ta.slug AS tipo_slug, ta.grupo_storage, ta.validador_regex
         FROM atributos_tipo_evidencia a
         INNER JOIN tipos_atributo ta ON ta.id_tipo_atributo = a.id_tipo_atributo
         WHERE a.id_tipo_evidencia = ?";
$stmt = $conn->prepare($sqlA);
$stmt->bind_param('i', $evi['id_tipo_evidencia']);
$stmt->execute();
$ra = $stmt->get_result();
$attrs = $ra ? $ra->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$conn->begin_transaction();

try {
  $baseUploadDir = __DIR__ . '/uploads/files';
  if (!is_dir($baseUploadDir)) @mkdir($baseUploadDir, 0775, true);

  foreach ($attrs as $a) {
    $aid  = (int)$a['id_ate'];
    $gs   = $a['grupo_storage'];
    $req  = (int)$a['requerido'] === 1;
    $unico = (int)$a['unico_por_evidencia'] === 1;
    $multi = (int)$a['multiple'] === 1;
    $minL = isset($a['min_longitud']) ? (int)$a['min_longitud'] : null;
    $maxL = isset($a['max_longitud']) ? (int)$a['max_longitud'] : null;
    $minV = isset($a['min_valor']) && $a['min_valor'] !== null ? (float)$a['min_valor'] : null;
    $maxV = isset($a['max_valor']) && $a['max_valor'] !== null ? (float)$a['max_valor'] : null;
    $regex = $a['validador_regex'];

    $indices = $_POST['indice'][$aid] ?? [];
    $cnt = is_array($indices) ? count($indices) : 0;

    // Recoger valores por grupo
    $arr_texto   = $_POST['valor_texto'][$aid]   ?? null;
    $arr_largo   = $_POST['valor_largo'][$aid]   ?? null;
    $arr_int     = $_POST['valor_int'][$aid]     ?? null;
    $arr_decimal = $_POST['valor_decimal'][$aid] ?? null;
    $arr_fecha   = $_POST['valor_fecha'][$aid]   ?? null;
    $arr_bool    = $_POST['valor_bool'][$aid]    ?? null;
    $arr_json    = $_POST['valor_json'][$aid]    ?? null;

    // Archivos: estructura $_FILES['valor_archivo']['name'][$aid][$i]
    $has_files = isset($_FILES['valor_archivo']) &&
                 isset($_FILES['valor_archivo']['name'][$aid]) &&
                 is_array($_FILES['valor_archivo']['name'][$aid]);

    // Validaciones previas
    if ($unico && $cnt > 1) {
      throw new Exception("El atributo {$a['nombre_atributo']} es único y no puede tener múltiples valores.");
    }

    // Borrar existentes
    $del = $conn->prepare("DELETE FROM evidencia_valores_atributo WHERE id_evidencia=? AND id_ate=?");
    $del->bind_param('ii', $id_evidencia, $aid);
    $del->execute();
    $del->close();

    $insert = $conn->prepare("INSERT INTO evidencia_valores_atributo
      (id_evidencia, id_ate, indice, valor_texto, valor_largo, valor_int, valor_decimal, valor_fecha, valor_bool, valor_archivo, valor_json)
      VALUES (?,?,?,?,?,?,?,?,?,?,?)");

    $insert_cnt = 0;

    for ($i=0; $i<$cnt; $i++) {
      $indice = (int)$indices[$i];

      $val_texto = $val_largo = $val_fecha = $val_archivo = $val_json = null;
      $val_int = $val_bool = null;
      $val_decimal = null;

      if ($gs === 'texto_corto') {
        $val_texto = trim((string)($arr_texto[$i] ?? ''));
        if ($val_texto === '' && !$req) continue; // permitir vacío no requerido
        if ($maxL && mb_strlen($val_texto) > $maxL) throw new Exception("{$a['nombre_atributo']}: supera longitud máxima.");
        if ($minL && mb_strlen($val_texto) < $minL) throw new Exception("{$a['nombre_atributo']}: por debajo de longitud mínima.");
        if ($regex && $val_texto !== '' && !preg_match('/'.$regex.'/i', $val_texto)) throw new Exception("{$a['nombre_atributo']}: formato inválido.");
      } elseif ($gs === 'texto_largo') {
        $val_largo = trim((string)($arr_largo[$i] ?? ''));
        if ($val_largo === '' && !$req) continue;
        if ($maxL && mb_strlen($val_largo) > $maxL) throw new Exception("{$a['nombre_atributo']}: supera longitud máxima.");
        if ($minL && mb_strlen($val_largo) < $minL) throw new Exception("{$a['nombre_atributo']}: por debajo de longitud mínima.");
      } elseif ($gs === 'entero') {
        $raw = $arr_int[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
          else continue;
        }
        if (!is_numeric($raw)) throw new Exception("{$a['nombre_atributo']}: debe ser entero.");
        $val_int = (int)$raw;
        if ($minV !== null && $val_int < $minV) throw new Exception("{$a['nombre_atributo']}: menor que mínimo.");
        if ($maxV !== null && $val_int > $maxV) throw new Exception("{$a['nombre_atributo']}: mayor que máximo.");
      } elseif ($gs === 'decimal') {
        $raw = $arr_decimal[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
          else continue;
        }
        if (!is_numeric($raw)) throw new Exception("{$a['nombre_atributo']}: debe ser número.");
        $val_decimal = (float)$raw;
        if ($minV !== null && $val_decimal < $minV) throw new Exception("{$a['nombre_atributo']}: menor que mínimo.");
        if ($maxV !== null && $val_decimal > $maxV) throw new Exception("{$a['nombre_atributo']}: mayor que máximo.");
      } elseif ($gs === 'fecha') {
        $raw = $arr_fecha[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: fecha requerida.");
          else continue;
        }
        $val_fecha = $raw;
      } elseif ($gs === 'booleano') {
        $raw = $arr_bool[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: requerido.");
          else continue;
        }
        $val_bool = ($raw === '1') ? 1 : 0;
      } elseif ($gs === 'archivo') {
        // archivo opcional si no se carga nada; si requerido y no hay existente, debe venir
        $fileName = null;
        if ($has_files) {
          $fn   = $_FILES['valor_archivo']['name'][$aid][$i] ?? null;
          $tmp  = $_FILES['valor_archivo']['tmp_name'][$aid][$i] ?? null;
          $err  = $_FILES['valor_archivo']['error'][$aid][$i] ?? UPLOAD_ERR_NO_FILE;
          $size = $_FILES['valor_archivo']['size'][$aid][$i] ?? 0;

          if ($err === UPLOAD_ERR_OK && $tmp && is_uploaded_file($tmp)) {
            if ($size > 10 * 1024 * 1024) throw new Exception("{$a['nombre_atributo']}: archivo excede 10MB.");

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp);
            finfo_close($finfo);

            $okMimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
            if (!in_array($mime, $okMimes, true)) throw new Exception("{$a['nombre_atributo']}: tipo de archivo no permitido.");

            $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
            $safeSlug = preg_replace('/[^a-z0-9_-]+/i', '-', $a['slug']);
            $fileName = 'attr_e'.$id_evidencia.'_a'.$aid.'_'.date('YmdHis').'_'.bin2hex(random_bytes(3)).'.'.$ext;
            $dest = $baseUploadDir . '/' . $fileName;
            if (!move_uploaded_file($tmp, $dest)) throw new Exception("{$a['nombre_atributo']}: no se pudo guardar el archivo.");
          }
        }
        // si requerido y nada subido, no insertamos (pero podrías exigirlo)
        if (!$fileName) {
          if ($req) {
            // Requerido: si no sube nada en esta edición, lo tomamos como error
            // (alternativa: permitir mantener existente; aquí se reemplaza completamente)
            throw new Exception("{$a['nombre_atributo']}: archivo requerido.");
          } else {
            continue;
          }
        }
        $val_archivo = $fileName;
      } elseif ($gs === 'json') {
        $raw = $arr_json[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: requerido.");
          else continue;
        }
        json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("{$a['nombre_atributo']}: JSON inválido.");
        $val_json = $raw;
      } else {
        // fallback texto
        $val_texto = trim((string)($arr_texto[$i] ?? ''));
        if ($val_texto === '' && !$req) continue;
      }

      $insert->bind_param(
        'iiissidisis',
        $id_evidencia,
        $aid,
        $indice,
        $val_texto,
        $val_largo,
        $val_int,
        $val_decimal,
        $val_fecha,
        $val_bool,
        $val_archivo,
        $val_json
      );
      $insert->execute();
      $insert_cnt++;
    }

    $insert->close();

    // Si es requerido y no insertó nada -> error
    if ($req && $insert_cnt === 0) {
      throw new Exception("{$a['nombre_atributo']}: debes capturar al menos un valor.");
    }
  }

  $conn->commit();
  echo json_encode(['status'=>'ok']);
} catch (Exception $ex) {
  $conn->rollback();
  echo json_encode(['status'=>'error','message'=>$ex->getMessage()]);
}
```

### evaluacion-get.php

Consulta de instrumentos calificables por evidencia.

```php
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
```

### evaluacion-save.php

Registro y actualización de calificaciones.

```php
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
```

### vista-evaluacion.php

Vista tabular de evidencias listas para evaluar.

```php
<?php
/**
 * Vista HTML (tbody) para la ventana de Evaluación
 * Muestra solo evidencias con 100% de atributos y que tengan al menos 1 instrumento relacionado.
 * Filtros: estado (todas|pendiente|completa) e instrumento (id).
 */
include 'conexion.php';
include 'validacion.php';

$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$estado = isset($_GET['estado']) ? trim($_GET['estado']) : 'todas'; // pendiente|completa|todas
$inst   = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;

// Query base
$sql = "SELECT
          e.id_evidencia, e.titulo, e.fecha_subida, e.archivo, e.id_docente,
          u.nombre, u.apellidop, u.apellidom,
          t.id_tipo_evidencia, t.nombre_tipo,
          COALESCE(ta.total,0)   AS total_attrs,
          COALESCE(vv.filled,0)  AS filled_attrs,
          COALESCE(insts.total_inst,0) AS total_inst,
          COALESCE(ceg.graded,0) AS graded_inst
        FROM evidencias e
        LEFT JOIN usuarios u ON u.id_usuario = e.id_docente
        LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
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
        LEFT JOIN (
          SELECT te.id_tipo_evidencia, COUNT(DISTINCT ite.id_instrumento) AS total_inst
          FROM tipos_de_evidencia te
          LEFT JOIN instrumento_tipo_evidencia ite ON ite.id_tipo_evidencia = te.id_tipo_evidencia
          LEFT JOIN instrumentos i ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
          GROUP BY te.id_tipo_evidencia
        ) insts ON insts.id_tipo_evidencia = t.id_tipo_evidencia
        LEFT JOIN (
          SELECT id_evidencia, COUNT(DISTINCT id_instrumento) AS graded
          FROM calificacion_evidencia
          GROUP BY id_evidencia
        ) ceg ON ceg.id_evidencia = e.id_evidencia
        WHERE e.ocultar = 0";

$params = []; $types = '';

// Rol 4 (docente): solo sus evidencias
if ($user_role === 4) {
  $sql   .= " AND e.id_docente = ? ";
  $types .= 'i'; $params[] = $user_id;
}

// Filtro por instrumento: evidencias cuyo TIPO tenga relación con ese instrumento
if ($inst > 0) {
  $sql .= " AND EXISTS (
    SELECT 1
    FROM instrumento_tipo_evidencia ite2
    JOIN instrumentos i2 ON i2.id_instrumento = ite2.id_instrumento AND i2.activo=1
    WHERE ite2.id_tipo_evidencia = e.id_tipo_evidencia AND ite2.id_instrumento = ?
  )";
  $types .= 'i'; $params[] = $inst;
}

$sql .= " ORDER BY e.id_evidencia DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
?>
<div class="card-content" style="overflow:hidden;">
  <table id="tabla-evaluacion" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <th data-priority="1"></th>
        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Título</th>
        <th class="dt-orderable" data-priority="3">Tipo</th>
        <th class="dt-orderable" data-priority="3">Docente</th>
        <th class="dt-orderable" data-priority="4">Archivo</th>
        <th class="dt-orderable" data-priority="4">Fecha</th>
        <th class="dt-orderable" data-priority="3">Avance</th>
        <th class="dt-orderable" data-priority="3">Estado</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($res && $res->num_rows > 0):
        while ($row = $res->fetch_assoc()):
          $id    = (int)$row['id_evidencia'];
          $tit   = $row['titulo'] ?? '';
          $tipoN = $row['nombre_tipo'] ?? '—';
          $docN  = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
          $file  = $row['archivo'] ?? '';
          $date  = $row['fecha_subida'] ? date('Y-m-d H:i', strtotime($row['fecha_subida'])) : '—';

          $totalAttrs  = (int)$row['total_attrs'];
          $filledAttrs = (int)$row['filled_attrs'];
          $totalInst   = (int)$row['total_inst'];
          $gradedInst  = (int)$row['graded_inst'];

          // Solo 100% y con al menos 1 instrumento
          if ($totalAttrs <= 0 || $filledAttrs < $totalAttrs || $totalInst <= 0) continue;

          $pct = 100;
          $href = $file ? ('uploads/files/'.rawurlencode($file)) : '';

          $pend = max(0, $totalInst - $gradedInst);
          $estadoCalc = ($pend === 0) ? 'completa' : 'pendiente';

          // Aplicar filtro de estado
          if ($estado === 'pendiente' && $estadoCalc !== 'pendiente') continue;
          if ($estado === 'completa' && $estadoCalc !== 'completa')   continue;

          $badgeEstado = $estadoCalc === 'completa'
            ? '<span class="badge badge-ok">Completa</span>'
            : '<span class="badge badge-warn">Pendiente ('.$pend.'/'.$totalInst.')</span>';

          $btnDownload = $file ? '
            <a class="btn" href="'.$href.'" download="'.htmlspecialchars($file).'" title="Descargar archivo">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M12 3v12M7 10l5 5 5-5M5 19h14" stroke="#111827" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>' : '—';

          $btnVerEval = '
            <button class="btn" type="button" title="Ver / Evaluar"
                    onclick="openEvaluarEvidencia('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="#0ea5e9" stroke-width="1.8"/>
                <circle cx="12" cy="12" r="3" stroke="#0ea5e9" stroke-width="1.8"/>
              </svg>
            </button>';

      ?>
      <tr data-estado="<?= $estadoCalc ?>" data-pend="<?= $pend ?>" data-total-inst="<?= $totalInst ?>">
        <td></td>
        <td><?= $id ?></td>
        <td class="font-medium"><?= htmlspecialchars($tit ?: '—') ?></td>
        <td><span class="badge"><?= htmlspecialchars($tipoN) ?></span></td>
        <td><?= htmlspecialchars($docN ?: '—') ?></td>
        <td><?= $btnDownload ?></td>
        <td><span class="badge" title="<?= htmlspecialchars($row['fecha_subida'] ?? '') ?>"><?= htmlspecialchars($date) ?></span></td>
        <td title="<?= $filledAttrs ?>/<?= $totalAttrs ?> atributos">
          <div class="progress"><div class="progress-bar" style="width: 100%;"></div></div>
          <span style="margin-left:8px; font-size:12px; color:var(--muted-foreground,#555);">100%</span>
        </td>
        <td><?= $badgeEstado ?></td>
        <td style="display:flex; gap:8px;"><?= $btnVerEval ?></td>
      </tr>
      <?php
        endwhile;
      endif;
      ?>
    </tbody>
  </table>
</div>
```

### descargas-api.php

Listado y descarga ZIP de evidencias aprobadas.

```php
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
```

### calificaciones-pdf.php

Generación de reporte PDF por instrumento.

```php
<?php
/**
 * Generador PDF: Calificaciones aprobadas por instrumento
 * Uso: calificaciones-pdf.php?instrumento=ID
 *
 * Requisitos:
 * - Tener FPDF disponible. Ajusta la ruta de require_once abajo si es necesario.
 * - Codificación: FPDF clásico no maneja UTF-8 nativamente; usamos utf8_decode() / cp1252.
 */

include 'validacion.php';
include 'conexion.php';

// === Ruta a FPDF (ajústala si la tiene en otro lado) ===
if (!class_exists('FPDF')) {
  require_once __DIR__ . '/fpdf/fpdf.php';
}

// ==================== Configurables ====================
$NUMERIC_PASS_PCT = 0.60; // 60% del rango -> umbral para aprobar en instrumentos NUMÉRICOS

// ==================== Helpers ====================
function to1252($s){
  // FPDF clásico: mejor utf8_decode; si tu texto trae emojis/utf8 avanzado, usa tFPDF
  return is_null($s) ? '' : utf8_decode((string)$s);
}
function fmtNum($n, $dec=2){
  if ($n === null || $n === '') return '';
  $f = floatval($n);
  $s = number_format($f, $dec, '.', '');
  // quita ceros sobrantes: 10.00 -> 10 ; 1.50 -> 1.5
  $s = rtrim(rtrim($s,'0'),'.');
  return $s;
}
function approvedFlag($tipo, $resultado, $min, $max, $pct){
  if ($resultado === null || $resultado === '') return false;
  $res = (float)$resultado;
  if ($tipo === 'NUMERICA') {
    $umbral = (float)$min + $pct * ((float)$max - (float)$min);
    return ($res >= $umbral);
  }
  // APROBACION
  return ($res >= 1.0);
}

// ==================== Lee parámetros ====================
$user_id   = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = isset($_SESSION['ROL']) ? (int)$_SESSION['ROL'] : 0;

$instId = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;
if ($instId <= 0) {
  http_response_code(400);
  echo "Falta parámetro 'instrumento'."; exit;
}

// ==================== Carga instrumento ====================
$sqlI = "SELECT id_instrumento, abreviatura, nombre_completo, tipo_calificacion,
                COALESCE(min_calificacion,0) AS min_cal,
                COALESCE(max_calificacion,10) AS max_cal,
                activo, creado_en, actualizado_en
         FROM instrumentos
         WHERE id_instrumento = ? AND activo = 1";
$stI = $conn->prepare($sqlI);
$stI->bind_param('i', $instId);
$stI->execute();
$rI = $stI->get_result();
$inst = $rI ? $rI->fetch_assoc() : null;
$stI->close();

if (!$inst) {
  http_response_code(404);
  echo "Instrumento no encontrado o inactivo."; exit;
}

$instAbbr = $inst['abreviatura'];
$instName = $inst['nombre_completo'];
$instTipo = $inst['tipo_calificacion']; // APROBACION | NUMERICA
$minCal   = (float)$inst['min_cal'];
$maxCal   = (float)$inst['max_cal'];

// ==================== Carga evidencias relacionadas y su calificación ====================
//
// Traemos todas las evidencias cuyo TIPO está asignado al instrumento
// y luego filtramos en PHP por "aprobadas" según la lógica arriba.
// Incluimos datos del docente y del tipo para imprimir en el PDF.
//
$sqlE = "SELECT
           e.id_evidencia, e.titulo, e.archivo, e.fecha_subida, e.id_docente,
           u.nombre, u.apellidop, u.apellidom,
           t.id_tipo_evidencia, t.nombre_tipo,
           ce.resultado, ce.comentario, ce.calificado_en, ce.actualizado_en
         FROM evidencias e
         JOIN instrumento_tipo_evidencia ite
              ON ite.id_tipo_evidencia = e.id_tipo_evidencia
             AND ite.id_instrumento    = ?
         JOIN instrumentos i
              ON i.id_instrumento = ite.id_instrumento
             AND i.activo = 1
         LEFT JOIN calificacion_evidencia ce
              ON ce.id_evidencia   = e.id_evidencia
             AND ce.id_instrumento = i.id_instrumento
         LEFT JOIN usuarios u
              ON u.id_usuario = e.id_docente
         LEFT JOIN tipos_de_evidencia t
              ON t.id_tipo_evidencia = e.id_tipo_evidencia
         WHERE e.ocultar = 0 ";
$params = [$instId]; $types = 'i';

if ($user_role === 4) {
  $sqlE .= " AND e.id_docente = ? ";
  $params[] = $user_id; $types .= 'i';
}
$sqlE .= " ORDER BY e.id_evidencia DESC";

$stE = $conn->prepare($sqlE);
$stE->bind_param($types, ...$params);
$stE->execute();
$resE = $stE->get_result();

$evidencias = [];
if ($resE) {
  while ($row = $resE->fetch_assoc()) {
    // Filtrar por APROBADAS
    $ok = approvedFlag($instTipo, $row['resultado'], $minCal, $maxCal, $NUMERIC_PASS_PCT);
    if ($ok) $evidencias[] = $row;
  }
}
$stE->close();

// Si no hay aprobadas, informamos con un PDF mínimo
if (empty($evidencias)) {
  class PDF extends FPDF {
    function Header(){
      $this->SetFont('Arial','B',12);
      $this->Cell(0,8,to1252('Calificaciones aprobadas por instrumento'),0,1,'C');
      $this->Ln(2);
    }
    function Footer(){
      $this->SetY(-15);
      $this->SetFont('Arial','I',8);
      $this->Cell(0,8,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
  }
  $pdf = new PDF();
  $pdf->AliasNbPages();
  $pdf->AddPage();
  $pdf->SetAutoPageBreak(true, 18);

  $pdf->SetFont('Arial','',11);
  $pdf->Cell(0,8,to1252("Instrumento: {$instAbbr} — {$instName}"),0,1,'L');
  $pdf->Ln(2);
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(0,8,to1252('No hay evidencias aprobadas.'),0,1,'L');

  $fname = 'calificaciones_'.$instAbbr.'_'.date('Ymd_His').'.pdf';
  $pdf->Output('I', $fname);
  exit;
}

// ==================== Cache de definiciones de atributos por tipo ====================
//
// Para evitar consultar definiciones por cada evidencia de un mismo tipo,
// mantenemos un pequeño cache: tipo_id => [definiciones]
//
$defsCache = []; // id_tipo_evidencia => [definiciones]
function loadAttrDefs($conn, $tipoId){
  $sqlA = "SELECT a.id_ate, a.nombre_atributo, a.slug, a.descripcion, a.orden,
                  a.requerido, a.unico_por_evidencia, a.multiple,
                  a.min_longitud, a.max_longitud, a.min_valor, a.max_valor, a.opciones_json,
                  ta.id_tipo_atributo, ta.nombre_tipo AS tipo_nombre, ta.slug AS tipo_slug, ta.grupo_storage, ta.validador_regex
           FROM atributos_tipo_evidencia a
           INNER JOIN tipos_atributo ta ON ta.id_tipo_atributo = a.id_tipo_atributo
           WHERE a.id_tipo_evidencia = ?
           ORDER BY a.orden ASC, a.id_ate ASC";
  $st = $conn->prepare($sqlA);
  $st->bind_param('i', $tipoId);
  $st->execute();
  $rs = $st->get_result();
  $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
  $st->close();
  return $rows;
}

function loadAttrValues($conn, $eviId){
  $sqlV = "SELECT id_eva, id_ate, indice,
                  valor_texto, valor_largo, valor_int, valor_decimal,
                  valor_fecha, valor_bool, valor_archivo, valor_json
           FROM evidencia_valores_atributo
           WHERE id_evidencia = ?
           ORDER BY id_ate ASC, indice ASC, id_eva ASC";
  $st = $conn->prepare($sqlV);
  $st->bind_param('i', $eviId);
  $st->execute();
  $rs = $st->get_result();
  $map = [];
  if ($rs) {
    while ($r = $rs->fetch_assoc()) {
      $map[(int)$r['id_ate']][] = $r;
    }
  }
  $st->close();
  return $map;
}

function pickListByGroup($def, $vals){
  $gs = $def['grupo_storage'];
  $out = [];

  if (empty($vals)) return $out;

  if ($gs === 'archivo') {
    foreach ($vals as $v) {
      if (!empty($v['valor_archivo'])) $out[] = $v['valor_archivo'];
    }
  } elseif ($gs === 'texto_corto') {
    foreach ($vals as $v) {
      $t = trim((string)$v['valor_texto']);
      if ($t !== '') $out[] = $t;
    }
  } elseif ($gs === 'texto_largo') {
    foreach ($vals as $v) {
      $t = trim((string)$v['valor_largo']);
      if ($t !== '') $out[] = $t;
    }
  } elseif ($gs === 'entero') {
    foreach ($vals as $v) {
      if ($v['valor_int'] !== null && $v['valor_int'] !== '') $out[] = (string)$v['valor_int'];
    }
  } elseif ($gs === 'decimal') {
    foreach ($vals as $v) {
      if ($v['valor_decimal'] !== null && $v['valor_decimal'] !== '') $out[] = fmtNum($v['valor_decimal']);
    }
  } elseif ($gs === 'fecha') {
    foreach ($vals as $v) {
      if (!empty($v['valor_fecha'])) $out[] = $v['valor_fecha'];
    }
  } elseif ($gs === 'booleano') {
    foreach ($vals as $v) {
      $out[] = ((int)$v['valor_bool'] === 1) ? 'Sí' : 'No';
    }
  } elseif ($gs === 'json') {
    foreach ($vals as $v) {
      if (!empty($v['valor_json'])) $out[] = $v['valor_json'];
    }
  }
  return $out;
}

// ==================== PDF ====================
class PDF extends FPDF {
  function Header(){
    // Título general se imprime en portada; aquí dejamos borde inferior sutil
    $this->SetDrawColor(220,220,220);
    $this->Line(10, 20, 200, 20);
    $this->Ln(4);
  }
  function Footer(){
    $this->SetY(-15);
    $this->SetFont('Arial','I',8);
    $this->Cell(0,8,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
  }
  function H1($txt){
    $this->SetFont('Arial','B',16);
    $this->Cell(0,10,to1252($txt),0,1,'L');
  }
  function H2($txt){
    $this->SetFont('Arial','B',13);
    $this->Cell(0,8,to1252($txt),0,1,'L');
  }
  function KV($k, $v){
    $this->SetFont('Arial','',10);
    $this->Cell(40,6,to1252($k.':'),0,0,'L');
    $this->SetFont('Arial','B',10);
    $this->Cell(0,6,to1252($v),0,1,'L');
  }
  function Badge($label, $ok=true){
    // Dibuja una “badge” sencilla usando celdas
    $w = 30; $h=7;
    if ($ok) $this->SetFillColor(209, 250, 229); // verde suave
    else     $this->SetFillColor(254, 243, 199); // amarillo suave
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','B',10);
    $this->Cell($w,$h,to1252($label),0,1,'C',true);
  }
  function MultiLine($txt){
    $this->SetFont('Arial','',10);
    $this->MultiCell(0,6,to1252($txt));
  }
  function Separator(){
    $this->Ln(1);
    $this->SetDrawColor(230,230,230);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    $this->Ln(2);
  }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 18);

// === Portada / Encabezado del informe ===
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,to1252('Calificaciones aprobadas por instrumento'),0,1,'L');
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,7,to1252('Fecha de generación: '.date('Y-m-d H:i')),0,1,'L');
$pdf->Ln(2);
$pdf->H2("Instrumento: {$instAbbr} — {$instName}");
$pdf->KV('Tipo', $instTipo === 'NUMERICA' ? 'NUMÉRICA' : 'APROBACIÓN');
if ($instTipo === 'NUMERICA') {
  $umbral = $minCal + $NUMERIC_PASS_PCT * ($maxCal - $minCal);
  $pdf->KV('Rango', fmtNum($minCal).' – '.fmtNum($maxCal));
  $pdf->KV('Umbral', fmtNum($umbral));
}
$pdf->KV('Total evidencias aprobadas', (string)count($evidencias));
$pdf->Separator();
$pdf->Ln(2);

// ==================== Contenido por evidencia ====================
foreach ($evidencias as $row) {
  $eviId   = (int)$row['id_evidencia'];
  $titulo  = $row['titulo'] ?: ('Evidencia #'.$eviId);
  $tipoNom = $row['nombre_tipo'] ?: '—';
  $docN    = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
  $fecha   = $row['fecha_subida'] ? date('Y-m-d H:i', strtotime($row['fecha_subida'])) : '—';
  $res     = $row['resultado'];
  $com     = $row['comentario'] ?: '';
  $calEn   = $row['calificado_en'] ?: '—';

  // Encabezado de evidencia
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(0,8,to1252("{$eviId} — {$titulo}"),0,1,'L');
  $pdf->SetFont('Arial','',10);
  $pdf->KV('Tipo de evidencia', $tipoNom);
  $pdf->KV('Docente', $docN !== '' ? $docN : '—');
  $pdf->KV('Fecha subida', $fecha);

  // Resultado + Comentario
  $pdf->SetFont('Arial','',10);
  if ($instTipo === 'NUMERICA') {
    $pdf->KV('Resultado', fmtNum($res));
    $pdf->KV('Calificado en', $calEn);
    $pdf->Badge('APROBADA', true);
  } else {
    // Aprobación
    $aprob = ((float)$res >= 1.0);
    $pdf->KV('Resultado', $aprob ? 'Aprobada (1)' : 'No aprobada (0)');
    $pdf->KV('Calificado en', $calEn);
    $pdf->Badge($aprob ? 'APROBADA' : 'NO APROBADA', $aprob);
  }
  if (trim($com) !== '') {
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,7,to1252('Comentario'),0,1,'L');
    $pdf->MultiLine($com);
  }

  // Atributos
  $tipoId = (int)$row['id_tipo_evidencia'];
  if (!isset($defsCache[$tipoId])) {
    $defsCache[$tipoId] = loadAttrDefs($conn, $tipoId);
  }
  $defs = $defsCache[$tipoId];
  $valsMap = loadAttrValues($conn, $eviId);

  if (empty($defs)) {
    $pdf->Ln(2);
    $pdf->MultiLine('Este tipo de evidencia no tiene atributos definidos.');
  } else {
    $pdf->Ln(2);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,to1252('Atributos capturados'),0,1,'L');
    $pdf->SetFont('Arial','',10);

    foreach ($defs as $def) {
      $aid = (int)$def['id_ate'];
      $vals = isset($valsMap[$aid]) ? $valsMap[$aid] : [];

      $leftW = 60; // ancho etiqueta
      $pdf->SetFont('Arial','B',10);
      $pdf->Cell($leftW,6,to1252($def['nombre_atributo']),0,0,'L');

      $pdf->SetFont('Arial','',10);
      $list = pickListByGroup($def, $vals);

      if (empty($list)) {
        $pdf->Cell(0,6,to1252('—'),0,1,'L');
      } else {
        // Para texto largo / JSON, imprimimos en MultiCell; para listas cortas, una línea
        $gs = $def['grupo_storage'];
        if ($gs === 'texto_largo' || $gs === 'json') {
          $pdf->Ln(0);
          // mover cursor a la derecha para alinear con valor
          $x = $pdf->GetX(); $y = $pdf->GetY();
          $pdf->SetXY($x + $leftW, $y);
          foreach ($list as $idx => $t) {
            $pdf->MultiCell(0,6,to1252($t));
            if ($idx < count($list)-1) {
              $x2 = $pdf->GetX(); $y2 = $pdf->GetY();
              $pdf->SetXY($x2 + $leftW, $y2);
            }
          }
        } else {
          // lista de chips en línea (comma)
          $joined = implode(', ', array_map(function($v){ return $v; }, $list));
          $pdf->Cell(0,6,to1252($joined),0,1,'L');
        }
      }
    }
  }

  // Separador entre evidencias
  $pdf->Ln(2);
  $pdf->Separator();
  $pdf->Ln(2);
}

// ==================== Salida ====================
$fname = 'calificaciones_'.$instAbbr.'_'.date('Ymd_His').'.pdf';
$pdf->Output('I', $fname);
```

