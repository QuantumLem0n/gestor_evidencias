<?php
// menu-pagina-get.php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID inválido']); exit; }

$sql = "SELECT id_mp, nombre_pagina, archivo, id_icono, COALESCE(ocultar,0) AS ocultar
        FROM menu_pagina WHERE id_mp = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) { echo json_encode(['status'=>'error','message'=>'No encontrado']); exit; }

// roles
$roles = [];
$sqlR = "SELECT id_rol FROM menu_rol WHERE id_pagina = ? AND COALESCE(ocultar,0) = 0";
$stmt2 = $conn->prepare($sqlR);
$stmt2->bind_param('i', $id);
$stmt2->execute();
$r2 = $stmt2->get_result();
while ($rr = $r2->fetch_assoc()) { $roles[] = (int)$rr['id_rol']; }
$stmt2->close();

echo json_encode([
  'status'=>'ok',
  'data'=>[
    'id_mp' => (int)$row['id_mp'],
    'nombre_pagina' => $row['nombre_pagina'],
    'archivo' => $row['archivo'],
    'id_icono' => (int)$row['id_icono'],
    'ocultar' => (int)$row['ocultar'],
    'roles' => $roles
  ]
]);
