<?php
/**
 * Cierra la sesión del usuario de forma segura y redirige al login.
 * - Limpia variables de sesión
 * - Destruye la sesión y su cookie
 * - Regenera el ID para evitar fijación
 * - Redirige a login.php con un pequeño flag (?logout=1) opcional
 */

declare(strict_types=1);

// Iniciar sesión si no existe
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 1) Vaciar el arreglo de sesión
$_SESSION = [];

// 2) Borrar la cookie de sesión (si se está usando cookies)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    // Caduca la cookie en el pasado
    setcookie(
        session_name(),      // nombre de la cookie de sesión
        '',                  // valor vacío
        time() - 42000,      // tiempo en el pasado
        $params['path'] ?? '/', 
        $params['domain'] ?? '',
        (bool)($params['secure'] ?? false),
        (bool)($params['httponly'] ?? true)
    );
}

// 3) Destruir la sesión en el servidor
session_destroy();

// 4) (Opcional) Regenerar un nuevo ID de sesión para el siguiente request
//    Esto ayuda a evitar fijación de sesión residual.
session_start();
session_regenerate_id(true);

// 5) Redirigir al login (puedes mostrar un mensaje con ?logout=1)
header('Location: login.php?logout=1');
exit;
