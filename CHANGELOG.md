# Changelog

## 2026-10-07

### Corrección del layout y la barra lateral

- Se corrigió el margen del contenido que era anulado por `.container`, causando que el menú cubriera la página.
- El encabezado y el contenido ahora reservan el mismo ancho: 260 px con el menú expandido, 72 px contraído y 0 px en móvil.
- Se eliminó el límite de 1120 px en las páginas que incluyen `left-menu.php`; tablas, encabezado y controles de sesión aprovechan el ancho disponible. Aplica a `index.php`, `gestion-usuarios.php`, `tipos-evidencia.php` y las demás páginas con el componente compartido.
- Se corrigieron las etiquetas del menú contraído y la distribución de su logo y botón. El menú permite desplazamiento vertical en pantallas bajas.
- Se unificó el breakpoint: escritorio desde 1024 px; menú superpuesto por debajo. El botón flotante solo aparece en móvil y los controles de apertura comparten estado accesible.
- Se permite cerrar el menú móvil con su botón, el fondo y Escape. Al pasar entre móvil y escritorio se limpia la apertura y se conserva la preferencia de escritorio.
- DataTables recalcula sus columnas al cambiar el espacio del menú, sin recargar los datos. Las tablas anchas pueden desplazarse dentro de su tarjeta, incluso en las vistas AJAX con `overflow:hidden` inline.
- Los encabezados pueden distribuir sus controles en varias líneas y los filtros y tarjetas se ajustan a pantallas pequeñas.
- Se comentaron los bloques modificados en `styles/global.css`, `header.php` y `left-menu.php`.

### Verificación

- Sintaxis PHP de `header.php` y `left-menu.php`: correcta (`php -l`).
- `git diff --check`: sin errores de whitespace.
- Pendiente comprobación visual con sesión real a 375, 768, 1024, 1552 y 1920 px, incluyendo menú expandido/contraído, cambio de resolución y tablas AJAX. El intento de ejecutar Edge sin interfaz en este entorno no produjo un resultado verificable.
