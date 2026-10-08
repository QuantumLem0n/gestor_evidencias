# Instalacion limpia en servidor

Archivo: `gestor_evidencia_produccion.sql`.

Este archivo crea una base nueva llamada `gestor_evidencia_produccion`, con el
esquema completo actual y sin datos operativos. No elimina bases existentes.
No se ha ejecutado ni importado en el entorno local.

## Contenido inicial

- Una sola cuenta activa, ID 1, rol Super Usuario: `superadmin@odea.local`.
  La contraseña inicial se entrega en la conversación; el SQL solo contiene
  su hash bcrypt, compatible con el inicio de sesión de la plataforma.
  El correo es un identificador de acceso, no un buzón real.
- Instrumentos SNII, PRODEP y ESDEPED con su configuración existente.
- Roles, páginas del menú, permisos por rol e iconos.
- Catálogo de tipos de atributo, necesario para configurar atributos desde la plataforma.
- Todas las tablas, claves, índices y relaciones del esquema actual, incluida
  `evaluador_instrumento`. No existen las columnas antiguas SNI/PRODEP/ESDEPED
  en `tipos_de_evidencia`.

Quedan vacías: `tipos_de_evidencia`, `atributos_tipo_evidencia`, `evidencias`,
`evidencia_valores_atributo`, `calificacion_evidencia`,
`instrumento_tipo_evidencia` y `evaluador_instrumento`.
Sus contadores autoincrementales empiezan en 1; el próximo usuario tendrá ID 2.

## Despliegue

1. En el servidor de destino, importar el SQL en MySQL/MariaDB mediante el
   cliente SQL o phpMyAdmin. Usar una base nueva y vacía.
2. Si el proveedor asigna un nombre de base o no permite `CREATE DATABASE`,
   crear/seleccionar la base desde su panel, quitar la sentencia `CREATE DATABASE`
   del archivo y adaptar `USE` al nombre asignado antes de importarlo.
3. Configurar host, usuario y contraseña de MySQL en `conexion.php` del servidor.
   Configurar `GESTOR_DB_NAME=gestor_evidencia_produccion` en el entorno de PHP,
   o ajustar el nombre de base en esa copia de `conexion.php`. Si el proveedor
   utiliza otro nombre, emplearlo también en esta configuración.
   La configuración local del proyecto no se cambia con este archivo.
4. Publicar el código y sus dependencias. Crear `uploads/files` vacío con
   permisos de escritura para PHP; no copiar archivos de demostración.
5. Entrar en `login.php` con la cuenta inicial. En Gestión de usuarios, editar
   su correo y contraseña para esta instalación.
6. Cargar tipos de evidencia y sus instrumentos, definir atributos, crear
   docentes y evaluadores y asignar instrumentos a estos últimos.

El SQL inicializa los datos; la configuración del servidor y las dependencias
de PHP se describen en `DOCUMENTACION_TECNICA.md` y `DEPENDENCIAS.md`.
La verificación realizada fue estática y de hash; no se importó este archivo
para probarlo, conforme a la instrucción de reservarlo al servidor de despliegue.
