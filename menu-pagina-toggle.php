<?php
// menu-pagina-toggle.php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$id = isset($_POST['id_mp']) ? intval($_POST['id_mp']) : 0;
$ocultar = isset($_POST['ocultar']) ? intval($_POST['ocultar']) : 0;
if ($id<=0 || ($ocultar!==0 && $ocultar!==1)) { echo json_encode(['status'=>'error','message'=>'Parámetros inválidos']); exit; }

$sql = "UPDATE menu_pagina SET ocultar = ? WHERE id_mp = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $ocultar, $id);
if ($stmt->execute()) {
  echo json_encode(['status'=>'ok']);
} else {
  echo json_encode(['status'=>'error','message'=>'No se pudo actualizar.']);
}
$stmt->close();
