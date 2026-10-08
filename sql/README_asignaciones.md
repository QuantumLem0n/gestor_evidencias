# Base independiente con asignaciones por instrumento

`gestor_evidencia_asignaciones.sql` es un SQL completo y autonomo basado en
`gestor_evidencia.sql`. Crea y selecciona **gestor_evidencia_asignaciones**.
El respaldo original y la base `gestor_evidencia` se conservan sin cambios.

## Instalacion

Desde la raiz del proyecto, con MariaDB/MySQL disponible:

```powershell
& C:/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 -e "source sql/gestor_evidencia_asignaciones.sql"
```

Tambien se puede importar el archivo completo en phpMyAdmin. El nombre de la
base debe estar libre; el script no elimina ni reemplaza bases existentes.
La importacion ya se realizo en el entorno local durante la implementacion.

`conexion.php` apunta por defecto a esta nueva base. Se puede seleccionar otra
base **con el mismo esquema actualizado** mediante `GESTOR_DB_NAME` en el entorno
del servidor PHP. La version actual requiere `evaluador_instrumento`; para volver
a la base antigua tambien debe restaurarse la version anterior del codigo.

## Datos y decisiones de conversion

- Se conservan los registros, IDs, credenciales, fechas, atributos, evidencias,
  calificaciones, indices y llaves foraneas del respaldo original.
- `tipos_de_evidencia` solo conserva `id_tipo_evidencia`, `nombre_tipo` y
  `descripcion`. Las columnas `SNI`, `PRODEP` y `ESDEPED` no se crean.
- La tabla puente `instrumento_tipo_evidencia` **ya existia** en el respaldo y
  era utilizada por el CRUD y la evaluacion. Se conservan sus relaciones y
  fechas como fuente de verdad. Las banderas antiguas estaban desactualizadas
  (por ejemplo, Libro ya tenia relacion con ESDEPED en la tabla puente).
  No se sustituyen esas relaciones por las banderas obsoletas.
- `evaluador_instrumento` tiene PK compuesta `(id_evaluador, id_instrumento)`,
  indice por instrumento y FKs a `usuarios` e `instrumentos`, con cascada al
  eliminar/actualizar IDs. La aplicacion solo asigna instrumentos al rol 3.
- Los tres evaluadores del respaldo reciben los tres instrumentos existentes:
  nueve relaciones explicitas que conservan el acceso anterior. Para restringir
  cada cuenta, editarla en Gestion de usuarios y desmarcar los instrumentos.
- Nuevos evaluadores sin seleccion no pueden evaluar. Nuevos instrumentos no
  se asignan automaticamente a nadie. No existe un limite de tres instrumentos.
- Retirar una asignacion o cambiar el rol no elimina calificaciones historicas.
- Los archivos fisicos de `uploads/` no estan contenidos en un SQL: se utilizan
  los archivos existentes. El respaldo no incorpora datos posteriores a su fecha.

## Uso

1. En **Instrumentos**, abrir **Agregar instrumento**, indicar abreviatura y
   nombre; luego editar su forma de calificar si requiere un rango numerico.
2. En **Tipos de evidencia**, editar los tipos y seleccionar el instrumento.
3. En **Gestion de usuarios**, crear o editar un usuario con rol Evaluador y
   marcar uno o varios instrumentos. Al abrir de nuevo la pagina se carga el
   catalogo completo, incluidos los instrumentos nuevos.
4. El evaluador solo ve instrumentos activos que coincidan con sus asignaciones
   y el tipo de evidencia. Los pendientes/completos se calculan sobre ese conjunto.
5. El servidor vuelve a validar asignacion, rol vigente, cuenta activa, relacion
   al tipo, instrumento activo, captura completa y rango al guardar.

Super Usuario y Administrador conservan evaluacion global. Docente solo consulta
sus propias evaluaciones; no puede calificar. La administracion de asignaciones
requiere Super Usuario o Administrador con acceso a Gestion de usuarios.

## Verificacion reproducible

```powershell
& C:/xampp/php/php.exe tests/instrumentos.php
```

La prueba crea una base temporal con nombre aleatorio, ejecuta los endpoints PHP
con sesiones de prueba y elimina esa base al terminar. Requiere el servicio
MySQL local y permisos para crear/eliminar esa base temporal. No modifica la
base original ni la nueva base de trabajo. Incluye permisos, revocacion,
transacciones y nuevos instrumentos. La comprobacion visual de los modales
en navegador es manual.
