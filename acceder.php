<?php
session_start(); // Inicia la sesión
include('conexion.php'); // Conexión a la base de datos

// === Recomendado: normalizar entradas ===
$correo = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
$password = $_POST['password'] ?? '';

// Si faltan datos, vuelve al login
if ($correo === '' || $password === '') {
  header('location: login.php?err=1');
  exit();
}

// Consulta preparada...
$sqltxt = "SELECT u.id_usuario, u.nombre, u.apellidop, u.apellidom, u.correo, u.password, u.rol, u.activo, r.nombre as nombrerol
FROM usuarios u 
LEFT JOIN roles r ON r.id_rol = u.rol
WHERE u.correo = ?";

$stmt = $conn->prepare($sqltxt);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        // === Recomendado: prevenir fijación de sesión ===
        session_regenerate_id(true);

        $_SESSION['ID'] = $row['id_usuario'];
        $_SESSION['SESUSUARIO'] = $row['nombre'];
        $_SESSION['APELLIDO'] = $row['apellidop'] . " " . $row['apellidom'];
        $_SESSION['EMAIL'] = $row['correo'];
        $_SESSION['ROL'] = $row['rol'];
        $_SESSION['RNOMBRE'] = $row['nombrerol'];
        $_SESSION['MENU'] = 1;

        if ($row['rol'] == "1") { 
            header('location: index.php');
        } elseif ($row['rol'] == "5" && $row['activo'] == '0') {
            header('location: deactivation.php');
        } else {
            header('location: index.php');
        }
        exit();
    }
}

// Credenciales inválidas
header('location: login.php?err=1');
exit();
