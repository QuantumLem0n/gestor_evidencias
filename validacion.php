<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
// Verificar si el usuario está logueado
if (!isset($_SESSION["SESUSUARIO"])) {
    // Redireccionar a la página de login si no está logueado
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['ROL'];
$user_name = $_SESSION['SESUSUARIO'];
$user_lastname = $_SESSION['APELLIDO'];
$user_email = $_SESSION['EMAIL'];
$user_id = $_SESSION['ID'];
$rol = $_SESSION['RNOMBRE'];

?>