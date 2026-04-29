<?php
// menu-pagina-eliminar.php
header('Content-Type: application/json; charset=utf-8');
require 'conexion.php';

$id = isset($_POST['id_mp']) ? intval($_POST['id_mp']) : 0;
if ($id<=0) { echo json_encode(['status'=>'error','message'=>'ID inválido']); exit; }

$conn->begin_transaction();
try {
  $q1 = "DELETE FROM menu_rol WHERE id_pagina = ?";
  $s1 = $conn->prepare($q1);
  $s1->bind_param('i', $id);
  if (!$s1->execute()) throw new Exception('No se pudo eliminar roles asociados.');
  $s1->close();

  $q2 = "DELETE FROM menu_pagina WHERE id_mp = ?";
  $s2 = $conn->prepare($q2);
  $s2->bind_param('i', $id);
  if (!$s2->execute()) throw new Exception('No se pudo eliminar la página.');
  $s2->close();

  $conn->commit();
  echo json_encode(['status'=>'ok']);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
