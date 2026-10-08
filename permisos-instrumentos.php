<?php
// Consultar siempre la cuenta vigente: una sesion antigua no conserva permisos revocados.
function usuarioActualInstrumentos(mysqli $conn): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $id = (int)($_SESSION['ID'] ?? 0);
    $st = $conn->prepare('SELECT id_usuario, rol FROM usuarios WHERE id_usuario=? AND activo=1');
    $st->bind_param('i', $id);
    $st->execute();
    return $st->get_result()->fetch_assoc() ?: ['id_usuario'=>0, 'rol'=>0];
}

function exigirGestion(mysqli $conn, string $pagina): void {
    $u = usuarioActualInstrumentos($conn);
    $rol = (int)$u['rol'];
    $permitido = $rol === 1;
    if (!$permitido && $rol === 2) {
        $st = $conn->prepare('SELECT 1 FROM menu_pagina p JOIN menu_rol r ON r.id_pagina=p.id_mp WHERE p.archivo=? AND r.id_rol=? AND COALESCE(p.ocultar,0)=0 AND COALESCE(r.ocultar,0)=0');
        $st->bind_param('si', $pagina, $rol);
        $st->execute();
        $permitido = $st->get_result()->num_rows > 0;
    }
    if (!$permitido) {
        http_response_code(403);
        echo json_encode(['status'=>'error', 'message'=>'No autorizado para administrar este catalogo.']);
        exit;
    }
}

// Fragmento SQL interno; alias e identificadores provienen exclusivamente del codigo.
function alcanceInstrumentos(array $usuario, string $alias = 'i'): string {
    $rol = (int)$usuario['rol'];
    $id = (int)$usuario['id_usuario'];
    if (in_array($rol, [1, 2, 4], true)) return '1=1';
    if ($rol !== 3 || !$id) return '1=0';
    return "EXISTS (SELECT 1 FROM evaluador_instrumento ei WHERE ei.id_evaluador=$id AND ei.id_instrumento=$alias.id_instrumento)";
}

function guardarInstrumentosEvaluador(mysqli $conn, int $id, int $rol, $instrumentos): void {
    if (!is_array($instrumentos)) throw new InvalidArgumentException('Instrumentos invalidos.');
    $ids = [];
    foreach ($instrumentos as $valor) {
        if (!is_scalar($valor) || !ctype_digit((string)$valor) || (int)$valor <= 0) {
            throw new InvalidArgumentException('Instrumento invalido.');
        }
        $ids[(int)$valor] = (int)$valor;
    }
    $st = $conn->prepare('DELETE FROM evaluador_instrumento WHERE id_evaluador=?');
    $st->bind_param('i', $id);
    $st->execute();
    if ($rol !== 3) return;
    $st = $conn->prepare('INSERT INTO evaluador_instrumento (id_evaluador, id_instrumento) VALUES (?, ?)');
    foreach ($ids as $iid) {
        $st->bind_param('ii', $id, $iid);
        $st->execute(); // La FK rechaza IDs inexistentes; el llamador revierte toda la transaccion.
    }
}
