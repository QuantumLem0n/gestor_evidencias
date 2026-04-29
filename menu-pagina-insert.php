<?php
// menu-pagina-insert.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__.'/conexion.php';

// Entradas
$nombre   = isset($_POST['nombre_pagina']) ? trim($_POST['nombre_pagina']) : '';
$archivo  = isset($_POST['archivo']) ? trim($_POST['archivo']) : '';
$id_icono = isset($_POST['id_icono']) ? (int)$_POST['id_icono'] : 0;
$roles    = isset($_POST['roles']) && is_array($_POST['roles']) ? array_map('intval', $_POST['roles']) : [];

if ($nombre === '' || $archivo === '' || $id_icono <= 0 || empty($roles)) {
  echo json_encode(['status'=>'error','message'=>'Datos incompletos.']); exit;
}
if (preg_match('/\s/', $archivo)) {
  echo json_encode(['status'=>'error','message'=>'El archivo no puede contener espacios.']); exit;
}

// Unicidad de archivo
$sqlCheck = "SELECT 1 FROM menu_pagina WHERE LOWER(archivo)=LOWER(?) LIMIT 1";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param('s', $archivo);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
if ($exists) {
  echo json_encode(['status'=>'error','message'=>'Ya existe una página con ese archivo.']); exit;
}

// Icono válido
$sqlIcon = "SELECT 1 FROM iconos WHERE id_icono=? LIMIT 1";
$stmt = $conn->prepare($sqlIcon);
$stmt->bind_param('i', $id_icono);
$stmt->execute();
$iconOk = $stmt->get_result()->num_rows > 0;
if (!$iconOk) {
  echo json_encode(['status'=>'error','message'=>'Icono inválido.']); exit;
}

// Insert en menu_pagina
$sqlIns = "INSERT INTO menu_pagina (nombre_pagina, archivo, id_icono, ocultar) VALUES (?,?,?,0)";
$stmt = $conn->prepare($sqlIns);
$stmt->bind_param('ssi', $nombre, $archivo, $id_icono);
if (!$stmt->execute()) {
  echo json_encode(['status'=>'error','message'=>'No se pudo crear la página.']); exit;
}
$id_pagina = $stmt->insert_id;

// Asegurar rol 1
if (!in_array(1, $roles, true)) $roles[] = 1;
$roles = array_values(array_unique($roles));

// Inserta relaciones en menu_rol (si no existen)
$sqlRel = "INSERT INTO menu_rol (id_pagina, id_rol, ocultar)
           SELECT ?, ?, 0
           WHERE NOT EXISTS (
             SELECT 1 FROM menu_rol WHERE id_pagina=? AND id_rol=?
           )";
$stmtRel = $conn->prepare($sqlRel);

foreach ($roles as $rid) {
  $stmtRel->bind_param('iiii', $id_pagina, $rid, $id_pagina, $rid);
  $stmtRel->execute(); // ignoramos errores individuales para continuar
}

echo json_encode(['status'=>'ok','id_pagina'=>$id_pagina]);
