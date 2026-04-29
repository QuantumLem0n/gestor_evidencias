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
