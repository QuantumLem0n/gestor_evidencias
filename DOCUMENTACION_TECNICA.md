# Documentacion tecnica

## Arquitectura general

El sistema esta construido como una aplicacion web PHP procedural. No utiliza framework MVC formal; la organizacion se basa en archivos PHP independientes para vistas, modales, controladores AJAX y operaciones CRUD.

La aplicacion se apoya en:

- PHP para logica de servidor.
- MySQL/MariaDB para persistencia.
- HTML, CSS y JavaScript para interfaz.
- Sesiones PHP para autenticacion.
- Consultas preparadas con `mysqli`.
- Archivos locales para almacenamiento de evidencias.

## Estructura de carpetas

```text
.
|-- assets/
|   `-- icons/
|       `-- favicon.ico
|-- fpdf/                  # Libreria FPDF, ignorada por Git
|-- sql/
|   `-- gestor_evidencia.sql
|-- styles/
|   |-- global.css
|   `-- global-old.css
|-- uploads/               # Archivos cargados, ignorado por Git
|   `-- files/
|-- *.php
|-- README.md
|-- .gitignore
```

La carpeta `uploads` almacena archivos cargados por usuarios y no se versiona. La carpeta `fpdf` tambien esta ignorada, por lo que debe instalarse o copiarse manualmente en cada servidor.

## Configuracion de base de datos

La conexion se define en `conexion.php`:

```php
$servername = "localhost";
$database = "gestor_evidencia";
$username = "root";
$password = "";
```

Para produccion se recomienda cambiar estos valores por un usuario especifico de base de datos con permisos limitados.

El archivo SQL principal es:

```text
sql/gestor_evidencia.sql
```

Este archivo contiene:

- Estructura de tablas.
- Llaves primarias.
- Llaves foraneas.
- Indices.
- Valores `AUTO_INCREMENT`.
- Datos iniciales y de muestra.

## Modelo de datos

### Tablas principales

| Tabla | Proposito |
| --- | --- |
| `usuarios` | Almacena usuarios, credenciales, rol y estado activo. |
| `roles` | Define los roles del sistema. |
| `menu_pagina` | Lista paginas disponibles en el menu. |
| `menu_rol` | Relaciona roles con paginas visibles. |
| `iconos` | Guarda SVG usados por el menu. |
| `tipos_de_evidencia` | Catalogo de categorias de evidencia academica. |
| `instrumentos` | Catalogo de instrumentos de evaluacion docente. |
| `instrumento_tipo_evidencia` | Relacion muchos a muchos entre instrumentos y tipos de evidencia. |
| `atributos_tipo_evidencia` | Define atributos dinamicos por tipo de evidencia. |
| `tipos_atributo` | Catalogo de tipos de dato para atributos dinamicos. |
| `evidencias` | Guarda evidencias cargadas por docentes. |
| `evidencia_valores_atributo` | Guarda valores capturados para atributos dinamicos. |
| `calificacion_evidencia` | Guarda resultados de evaluacion por evidencia e instrumento. |

### Relaciones importantes

- `usuarios.rol` se relaciona con `roles.id_rol`.
- `evidencias.id_docente` se relaciona con `usuarios.id_usuario`.
- `evidencias.id_tipo_evidencia` se relaciona con `tipos_de_evidencia.id_tipo_evidencia`.
- `atributos_tipo_evidencia.id_tipo_evidencia` se relaciona con `tipos_de_evidencia.id_tipo_evidencia`.
- `atributos_tipo_evidencia.id_tipo_atributo` se relaciona con `tipos_atributo.id_tipo_atributo`.
- `evidencia_valores_atributo.id_evidencia` se relaciona con `evidencias.id_evidencia`.
- `evidencia_valores_atributo.id_ate` se relaciona con `atributos_tipo_evidencia.id_ate`.
- `instrumento_tipo_evidencia` relaciona `instrumentos` con `tipos_de_evidencia`.
- `calificacion_evidencia` relaciona una evidencia con un instrumento y un evaluador.

## Flujo de autenticacion

### Archivos relacionados

- `login.php`
- `acceder.php`
- `logout.php`
- `validacion.php`

### Funcionamiento

1. El usuario ingresa correo y contrasena en `login.php`.
2. `acceder.php` recibe los datos por `POST`.
3. El correo se normaliza con `trim` y `strtolower`.
4. Se consulta la tabla `usuarios` mediante consulta preparada.
5. La contrasena se valida con `password_verify`.
6. Si las credenciales son validas, se regenera el ID de sesion con `session_regenerate_id(true)`.
7. Se almacenan datos del usuario en `$_SESSION`, incluyendo ID, nombre, correo, rol y nombre de rol.
8. El usuario es redirigido a `index.php`.

`validacion.php` se incluye en paginas protegidas para asegurar que exista una sesion activa. Si no hay sesion, redirige a `login.php`.

## Control de permisos

El proyecto cuenta con dos niveles de control:

### Validacion de sesion

Se realiza mediante `validacion.php`, que verifica que exista `$_SESSION["SESUSUARIO"]`.

### Validacion por pagina y rol

El archivo `validacion-permiso.php` consulta `menu_pagina` y `menu_rol` para determinar si el rol actual puede acceder al archivo solicitado.

El rol Super Usuario (`id_rol = 1`) tiene bypass de permisos.

Nota tecnica: en el estado actual del proyecto, no todas las paginas incluyen explicitamente `validacion-permiso.php`. Para endurecer seguridad, conviene incluirlo en todas las vistas principales que deban estar protegidas por rol.

## Menu lateral dinamico

### Archivo principal

- `left-menu.php`

### Funcionamiento

El menu se genera desde base de datos:

1. Lee `$_SESSION['ROL']`.
2. Consulta `menu_pagina`, `menu_rol` e `iconos`.
3. Filtra paginas ocultas.
4. Renderiza enlaces con iconos SVG guardados en la tabla `iconos`.
5. Marca como activa la pagina actual.

El menu incluye comportamiento responsivo:

- En escritorio puede colapsarse y guarda el estado en `localStorage`.
- En movil funciona como menu lateral tipo off-canvas.

## Layout e interfaz

### Archivos base

- `header.php`
- `footer.php`
- `left-menu.php`
- `styles/global.css`

`header.php` define estructura HTML comun, carga el favicon y la hoja de estilos global, muestra la marca ODEA, el rol activo, avatar del usuario, menu de perfil y cambio de tema.

`styles/global.css` concentra los estilos de la aplicacion, incluyendo tarjetas, tablas, formularios, modales, sidebar, tema oscuro y diseno responsivo.

## Gestion de usuarios

### Archivos relevantes

- `gestion-usuarios.php`
- `vista-gestion-usuarios.php`
- `modal-agregar-usuario.php`
- `modal-editar-usuario.php`
- `usuario-get.php`
- `usuario-insert.php`
- `usuario-update.php`
- `usuario-eliminar.php`

### Funcionamiento

El modulo permite:

- Listar usuarios.
- Crear usuarios.
- Editar datos personales.
- Asignar rol.
- Activar o desactivar usuarios.
- Eliminar usuarios.

`usuario-insert.php` valida campos requeridos, valida formato de correo, verifica unicidad del correo y guarda la contrasena con `password_hash(..., PASSWORD_BCRYPT)`.

## Gestion de tipos de evidencia

### Archivos relevantes

- `tipos-evidencia.php`
- `vista-tipos-evidencia.php`
- `modal-agregar-tipo-evidencia.php`
- `modal-editar-tipo-evidencia.php`
- `tipo-evidencia-get.php`
- `tipo-evidencia-insert.php`
- `tipo-evidencia-update.php`
- `tipo-evidencia-eliminar.php`

### Funcionamiento

Cada tipo de evidencia representa una categoria academica. Al crear o editar un tipo, se pueden asociar instrumentos de evaluacion. La relacion se guarda en `instrumento_tipo_evidencia`.

El sistema utiliza transacciones en operaciones donde se guarda el tipo y sus relaciones con instrumentos.

## Gestion de atributos por tipo de evidencia

### Archivos relevantes

- `atributos-tipo.php`
- `vista-atributos-tipo.php`
- `modal-agregar-atributo-tipo.php`
- `modal-editar-atributo-tipo.php`
- `atributo-tipo-get.php`
- `atributo-tipo-insert.php`
- `atributo-tipo-update.php`
- `atributo-tipo-eliminar.php`

### Funcionamiento

Los atributos dinamicos permiten que cada tipo de evidencia tenga su propia ficha de captura. Por ejemplo:

- Libro: autores, titulo, ano de publicacion, editorial, paginas, URL, ISBN.
- Conferencia: nombre del evento, fecha, ciudad, pais, horas, constancia.
- Diplomado: nombre, duracion, programa.

Cada atributo se define con:

- Nombre visible.
- Slug.
- Tipo de atributo.
- Orden.
- Requerido.
- Unico por evidencia.
- Multiple.
- Reglas de longitud.
- Reglas numericas.
- Opciones JSON.

## Gestion de evidencias

### Archivos relevantes

- `gestion-evidencias.php`
- `vista-gestion-evidencias.php`
- `modal-agregar-evidencia.php`
- `modal-editar-evidencia.php`
- `evidencia-get.php`
- `evidencia-insert.php`
- `evidencia-update.php`
- `evidencia-eliminar.php`

### Funcionamiento

`evidencia-insert.php` permite crear evidencias con archivo principal. El codigo valida:

- Usuario autenticado.
- Rol permitido para crear evidencia: Super Usuario o Docente.
- Titulo.
- Tipo de evidencia.
- Archivo cargado.
- Tamano maximo de 10 MB.
- MIME permitido.
- Extension permitida.

Formatos aceptados:

- PDF.
- JPG/JPEG.
- PNG.
- WEBP.

El archivo se guarda en:

```text
uploads/files
```

El nombre del archivo se genera a partir del titulo, fecha, hora y sufijo aleatorio.

## Captura de detalle de evidencia

### Archivos relevantes

- `evidencia-detalle.php`
- `vista-evidencia-detalle.php`
- `modal-editar-evidencia-detalle.php`
- `evidencia-detalle-get.php`
- `evidencia-detalle-save.php`
- `evidencia-atributos-get.php`

### Funcionamiento

`evidencia-detalle-save.php` guarda los valores de los atributos dinamicos de una evidencia.

El flujo es:

1. Valida sesion.
2. Recibe `id_evidencia`.
3. Consulta la evidencia.
4. Verifica que el usuario sea docente y propietario de la evidencia.
5. Obtiene los atributos definidos para el tipo de evidencia.
6. Inicia transaccion.
7. Borra valores anteriores de cada atributo.
8. Valida y guarda los nuevos valores.
9. Confirma la transaccion o revierte en caso de error.

Segun el tipo de atributo, el valor se guarda en una de estas columnas:

- `valor_texto`
- `valor_largo`
- `valor_int`
- `valor_decimal`
- `valor_fecha`
- `valor_bool`
- `valor_archivo`
- `valor_json`

## Instrumentos de evaluacion

### Archivos relevantes

- `instrumentos.php`
- `vista-instrumentos.php`
- `modal-editar-instrumento.php`
- `instrumento-get.php`
- `instrumento-update.php`

### Funcionamiento

La tabla `instrumentos` contiene instrumentos como SNII, PRODEP y ESDEPED. Cada instrumento tiene:

- Abreviatura.
- Nombre completo.
- Tipo de calificacion.
- Rango minimo y maximo opcional.
- Estado activo.

Los tipos de calificacion soportados son:

- `APROBACION`
- `NUMERICA`

## Evaluacion de evidencias

### Archivos relevantes

- `evaluacion.php`
- `vista-evaluacion.php`
- `modal-evaluar-evidencia.php`
- `evaluacion-get.php`
- `evaluacion-save.php`

### Funcionamiento

`evaluacion-save.php` guarda o actualiza la calificacion de una evidencia para un instrumento.

Reglas principales:

- El rol Docente no puede calificar.
- La evidencia debe existir.
- La evidencia debe tener todos sus atributos capturados.
- El instrumento debe estar asociado al tipo de evidencia.
- Si el instrumento es numerico, el valor debe estar en el rango configurado.
- Si el instrumento es de aprobacion, solo acepta `0` o `1`.

La tabla `calificacion_evidencia` tiene una llave unica por `id_evidencia` e `id_instrumento`, por lo que el codigo usa `INSERT ... ON DUPLICATE KEY UPDATE` para actualizar calificaciones existentes.

## Calificaciones, descargas y PDF

### Archivos relevantes

- `calificaciones-recibidas.php`
- `vista-calificaciones-recibidas.php`
- `modal-descargar-archivos.php`
- `descargas-api.php`
- `calificaciones-pdf.php`

### Descarga ZIP

`descargas-api.php` tiene dos acciones:

- `action=list`: lista evidencias aprobadas por instrumento.
- `action=zip`: genera un ZIP con evidencias aprobadas seleccionadas.

Para instrumentos numericos, una evidencia se considera aprobada si alcanza al menos el 60% del rango configurado. Para instrumentos de aprobacion, se considera aprobada si el resultado es mayor o igual a 1.

Si el usuario es Docente, solo se devuelven sus propias evidencias.

### Reporte PDF

`calificaciones-pdf.php` genera un PDF con evidencias aprobadas por instrumento. Incluye:

- Datos del instrumento.
- Tipo de calificacion.
- Evidencias aprobadas.
- Docente.
- Tipo de evidencia.
- Fecha de subida.
- Resultado.
- Comentario.
- Atributos capturados.

Requiere que FPDF exista en:

```text
fpdf/fpdf.php
```

## Configuracion de menu

### Archivos relevantes

- `gestion-menu.php`
- `vista-gestion-menu.php`
- `icons-gallery.php`
- `modal-agregar-pagina.php`
- `modal-editar-pagina.php`
- `menu-pagina-get.php`
- `menu-pagina-insert.php`
- `menu-pagina-update.php`
- `menu-pagina-eliminar.php`
- `menu-pagina-toggle.php`

### Funcionamiento

El modulo permite administrar paginas del menu, iconos y visibilidad. La visibilidad por rol se controla en `menu_rol`.

## Seguridad implementada

El codigo actual incluye varias practicas importantes:

- Uso de sesiones PHP.
- Regeneracion de ID de sesion despues de login.
- Hash de contrasenas con BCRYPT.
- Consultas preparadas en modulos principales.
- Validacion de MIME y extension para archivos cargados.
- Limite de tamano de archivo.
- Transacciones para operaciones compuestas.
- Escapado de salida HTML con `htmlspecialchars` en componentes de UI.

## Riesgos y recomendaciones tecnicas

Para despliegue en produccion se recomienda:

- Cambiar credenciales por defecto de `conexion.php`.
- Usar HTTPS.
- Crear un usuario de base de datos con permisos minimos.
- Proteger o excluir archivos SQL con datos de muestra si contienen informacion sensible.
- Revisar que todas las paginas protegidas incluyan `validacion.php` y, si aplica, `validacion-permiso.php`.
- Mantener `uploads` fuera del repositorio.
- Validar permisos de escritura solo sobre `uploads/files`.
- Configurar `display_errors = Off` en produccion.
- Definir `upload_max_filesize` y `post_max_size` segun el limite deseado.
- Revisar que FPDF este presente en servidores donde se requieran reportes PDF.
- Considerar migrar configuracion sensible a variables de entorno.

## Archivos de entrada comunes

| Archivo | Uso |
| --- | --- |
| `login.php` | Pantalla de inicio de sesion. |
| `index.php` | Panel inicial despues de iniciar sesion. |
| `gestion-usuarios.php` | Gestion de usuarios. |
| `tipos-evidencia.php` | Gestion de tipos de evidencia. |
| `atributos-tipo.php` | Gestion de atributos dinamicos. |
| `gestion-evidencias.php` | Gestion y consulta de evidencias. |
| `evidencia-detalle.php` | Captura de atributos de una evidencia. |
| `instrumentos.php` | Gestion de instrumentos. |
| `evaluacion.php` | Evaluacion de evidencias. |
| `calificaciones-recibidas.php` | Consulta y descarga de evidencias aprobadas. |
| `gestion-menu.php` | Administracion del menu. |
| `perfil.php` | Vista de perfil de usuario. |

