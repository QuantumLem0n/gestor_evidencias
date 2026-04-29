<?php
// validacion-permiso.php
// Uso: incluir al inicio de cada página principal DESPUÉS de tener la sesión iniciada.
// Requiere: $_SESSION['ROL'] y conexión $conn (include 'conexion.php').

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['ROL'])) { header('Location: login.php'); exit(); }

//require_once __DIR__.'/conexion.php';
require_once 'conexion.php';
// Bypass para Super Usuario
if ((int)($_SESSION['ROL']) === 1) { return; }

// Nombre del archivo actual (sin querystring)
$archivo = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$rol     = (int)$_SESSION['ROL'];

// Chequea si el rol tiene acceso a esta página y que no esté oculta
$sql = "SELECT 1
        FROM menu_pagina mp
        INNER JOIN menu_rol mr ON mr.id_pagina = mp.id_mp
        WHERE mp.archivo = ?
          AND COALESCE(mp.ocultar,0) = 0
          AND COALESCE(mr.ocultar,0) = 0
          AND mr.id_rol = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $archivo, $rol);
$stmt->execute();
$allowed = $stmt->get_result()->num_rows > 0;

if (!$allowed) { header('Location: index.php'); exit(); }
