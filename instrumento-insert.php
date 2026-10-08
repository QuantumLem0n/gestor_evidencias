<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
require_once 'permisos-instrumentos.php';
exigirGestion($conn, 'instrumentos.php');
$abrev = trim($_POST['abreviatura'] ?? '');
$nombre = trim($_POST['nombre_completo'] ?? '');
if ($abrev === '' || $nombre === '' || mb_strlen($abrev)>20 || mb_strlen($nombre)>150) {
    echo json_encode(['status'=>'error', 'message'=>'Indica una abreviatura (hasta 20 caracteres) y un nombre (hasta 150).']);
    exit;
}
try {
    $st = $conn->prepare('INSERT INTO instrumentos (abreviatura, nombre_completo) VALUES (?, ?)');
    $st->bind_param('ss', $abrev, $nombre);
    $st->execute();
    echo json_encode(['status'=>'ok', 'message'=>'Instrumento creado. Puedes configurar su calificacion y asignarlo a tipos de evidencia y evaluadores.']);
} catch (Throwable $e) {
    echo json_encode(['status'=>'error', 'message'=>'No se pudo crear. Verifica que la abreviatura no exista.']);
}
