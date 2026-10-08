# Changelog

## 2026-10-08

### SQL para instalacion limpia en servidor

- Nuevo `sql/gestor_evidencia_produccion.sql`, con el esquema completo, una sola
  cuenta Super Usuario activa y contraseña almacenada como hash bcrypt.
- Conserva instrumentos, roles, menús, permisos, iconos y tipos de atributo;
  elimina del conjunto inicial usuarios de ejemplo y todos los datos operativos.
- Reinicia contadores de tablas operativas y deja las relaciones de evaluadores
  y tipos de evidencia vacías para configurarlas desde la plataforma.
- Instrucciones en `sql/README_produccion.md`. No se modifica la conexión local
  ni se ejecuta/importa el SQL. Verificación estática y de contraseña realizada.

### Vista previa de documentos durante la evaluacion

- La tabla de evaluacion ofrece **Ver documento** en otra pestaña y conserva la descarga.
- El modal muestra el documento junto a los instrumentos para calificar, con atributos
  desplegables; en pantallas pequeñas los paneles se apilan.
- Nuevo visor local `evidencia-archivo.php`: PDF, imagenes JPEG/PNG/GIF/WebP y texto
  plano se sirven inline. Otros formatos muestran una alternativa de descarga.
- El visor y la entrega del archivo verifican cuenta activa y permisos vigentes:
  evaluador asignado a un instrumento activo del tipo, docente propietario o administrador.
  Se rechazan evidencias ocultas, archivos inexistentes y rutas fuera de `uploads/files`.
- El tipo real del archivo se detecta con Fileinfo; no se sirve HTML/SVG como contenido
  activo. No se envian documentos a visores externos ni se cambia la base de datos.
- Al cerrar el modal se libera el visor; respuestas tardias de otra evidencia no
  reemplazan los atributos ni los instrumentos de la seleccion actual.
- Verificacion: 42 comprobaciones de integracion correctas, sintaxis PHP y
  `git diff --check` sin errores. Pendiente revision visual en navegador.

## 2026-10-07

### Asignacion de evaluadores y normalizacion de instrumentos

- Nueva tabla `evaluador_instrumento` con clave compuesta y llaves foraneas.
- Seleccion multiple en alta/edicion de evaluadores y asignaciones visibles en
  la tabla de usuarios. Guardado transaccional; al cambiar de rol se retiran
  asignaciones sin borrar las calificaciones historicas.
- Verificacion en servidor del rol y estado vigentes y de los instrumentos
  asignados. Un POST directo no permite calificar instrumentos ajenos.
- Modal, filtros y pendientes de evaluacion limitados al conjunto autorizado.
  Administradores conservan alcance global; docentes permanecen en consulta.
- Alta de instrumentos desde el catalogo y filtros dinamicos por ID: se admiten
  nuevos instrumentos sin modificar codigo ni columnas de tipos de evidencia.
- SQL completo `sql/gestor_evidencia_asignaciones.sql`, con todos los datos del
  respaldo, sin columnas SNI/PRODEP/ESDEPED en `tipos_de_evidencia` y conservando
  las relaciones existentes de `instrumento_tipo_evidencia` como fuente vigente.
- Los evaluadores del respaldo reciben nueve asignaciones explicitas para
  conservar sus accesos previos; los nuevos registros requieren seleccion manual.
- `conexion.php` apunta a la nueva base; admite `GESTOR_DB_NAME`. Base original
  y respaldo original intactos. Nueva base importada localmente para la prueba.
- Autorizacion administrativa en endpoints de usuarios, alta/edicion de
  instrumentos y alta/edicion de tipos; relaciones invalidas revierten el guardado.
- Documentacion en `DOCUMENTACION_TECNICA.md` y `sql/README_asignaciones.md`.
- Verificacion: 33 pruebas de integracion correctas en `tests/instrumentos.php`, todos los PHP sin errores de sintaxis y `git diff --check` limpio; comprobacion
  visual en navegador pendiente.


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
