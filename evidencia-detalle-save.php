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
