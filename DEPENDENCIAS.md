# Dependencias e instalacion

## Requisitos generales

Para montar el proyecto se necesita un servidor web compatible con PHP y MySQL/MariaDB.

Entornos recomendados:

- XAMPP para desarrollo local en Windows.
- WAMP o Laragon en Windows.
- LAMP en Linux.
- Hosting con Apache, PHP y MySQL/MariaDB.

## Versiones usadas como referencia

El archivo `sql/gestor_evidencia.sql` fue exportado desde un entorno con:

- phpMyAdmin 5.2.1
- MariaDB 10.4.32
- PHP 8.2.12

Estas versiones son una referencia de compatibilidad. El proyecto deberia funcionar en PHP 8.x y MySQL/MariaDB moderno, siempre que esten disponibles las extensiones necesarias.

## Dependencias de servidor

### PHP

Version recomendada:

```text
PHP 8.1 o superior
```

Version usada en el dump SQL:

```text
PHP 8.2.12
```

### Servidor web

Recomendado:

```text
Apache 2.4 o superior
```

Tambien puede ejecutarse en Nginx si se configura correctamente el procesamiento de archivos PHP mediante PHP-FPM.

### Base de datos

Recomendado:

```text
MariaDB 10.4 o superior
```

Alternativa:

```text
MySQL 8.0 o superior
```

La base de datos esperada por el codigo se llama:

```text
gestor_evidencia
```

## Extensiones PHP necesarias

El proyecto requiere o utiliza las siguientes extensiones PHP:

| Extension | Uso |
| --- | --- |
| `mysqli` | Conexion y consultas a MySQL/MariaDB. |
| `fileinfo` | Validacion de tipo MIME en archivos cargados. |
| `mbstring` | Manejo de cadenas multibyte en interfaz y validaciones. |
| `json` | Validacion y manejo de datos JSON. |
| `zip` | Creacion de archivos ZIP mediante `ZipArchive`. |
| `session` | Manejo de sesiones de usuario. |

En XAMPP, varias de estas extensiones suelen venir activadas. Si alguna no esta activa, se debe habilitar en `php.ini`.

## Librerias externas

### FPDF

El archivo `calificaciones-pdf.php` requiere FPDF:

```php
require_once __DIR__ . '/fpdf/fpdf.php';
```

Por lo tanto debe existir:

```text
fpdf/fpdf.php
```

La carpeta `fpdf` esta ignorada por Git mediante `.gitignore`, por lo que no se descarga automaticamente desde este repositorio. En cada servidor se debe copiar o instalar FPDF manualmente.

Estructura esperada:

```text
fpdf/
|-- fpdf.php
|-- font/
|-- ...
```

### Librerias frontend

El estado actual del proyecto no depende de gestores como npm, Composer, Vite o Webpack. La interfaz usa CSS y JavaScript incluidos directamente en archivos del proyecto.

## Carpetas que deben existir

La carpeta de subida no se versiona, pero debe existir en el servidor:

```text
uploads/files
```

Si no existe, algunos endpoints intentan crearla automaticamente. Aun asi, para despliegue se recomienda crearla manualmente y asignar permisos de escritura al usuario del servidor web.

En Windows/XAMPP normalmente basta con que la carpeta exista dentro del proyecto.

En Linux, un ejemplo seria:

```bash
mkdir -p uploads/files
chmod 775 uploads uploads/files
```

Si Apache usa un usuario como `www-data`, puede ser necesario asignar propietario:

```bash
chown -R www-data:www-data uploads
```

## Archivos y carpetas ignorados por Git

El archivo `.gitignore` actual excluye:

```text
uploads
fpdf
```

Esto significa:

- Los archivos cargados por usuarios no se suben al repositorio.
- La libreria FPDF debe instalarse o copiarse manualmente.
- Al clonar el repositorio en otro servidor, se deben recrear estas carpetas segun sea necesario.

## Instalacion en XAMPP

### 1. Copiar o clonar el proyecto

Ubicar el proyecto dentro de:

```text
C:\xampp\htdocs\gestor_evidencia
```

Si se clona desde GitHub:

```bash
git clone https://github.com/QuantumLem0n/gestor_evidencias.git gestor_evidencia
```

### 2. Iniciar servicios

Desde el panel de XAMPP, iniciar:

- Apache
- MySQL

### 3. Crear la base de datos

Entrar a phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Crear una base de datos llamada:

```text
gestor_evidencia
```

Usar cotejamiento recomendado:

```text
utf8mb4_general_ci
```

### 4. Importar el SQL

Importar:

```text
sql/gestor_evidencia.sql
```

Desde phpMyAdmin:

1. Seleccionar la base `gestor_evidencia`.
2. Entrar a la pestana Importar.
3. Seleccionar el archivo `sql/gestor_evidencia.sql`.
4. Ejecutar la importacion.

### 5. Revisar conexion

Abrir `conexion.php` y confirmar:

```php
$servername = "localhost";
$database = "gestor_evidencia";
$username = "root";
$password = "";
```

Estos valores corresponden a una instalacion local comun de XAMPP.

### 6. Crear carpetas no versionadas

Crear:

```text
uploads/files
```

Copiar FPDF en:

```text
fpdf/
```

Debe existir:

```text
fpdf/fpdf.php
```

### 7. Abrir el sistema

En el navegador:

```text
http://localhost/gestor_evidencia/login.php
```

## Instalacion en servidor LAMP

### 1. Copiar el proyecto al servidor

Ejemplo:

```bash
cd /var/www/html
git clone https://github.com/QuantumLem0n/gestor_evidencias.git gestor_evidencia
```

### 2. Crear base de datos y usuario

Ejemplo en MariaDB/MySQL:

```sql
CREATE DATABASE gestor_evidencia CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'gestor_user'@'localhost' IDENTIFIED BY 'cambiar_esta_contrasena';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON gestor_evidencia.* TO 'gestor_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Importar SQL

```bash
mysql -u gestor_user -p gestor_evidencia < sql/gestor_evidencia.sql
```

### 4. Configurar conexion

Editar `conexion.php`:

```php
$servername = "localhost";
$database = "gestor_evidencia";
$username = "gestor_user";
$password = "cambiar_esta_contrasena";
```

### 5. Preparar carpetas

```bash
mkdir -p uploads/files
chmod 775 uploads uploads/files
```

Si aplica:

```bash
chown -R www-data:www-data uploads
```

### 6. Instalar FPDF

Copiar la libreria en:

```text
fpdf/
```

Comprobar:

```text
fpdf/fpdf.php
```

### 7. Configurar Apache

El `DocumentRoot` puede apuntar directamente a la carpeta del proyecto o a un virtual host.

Ejemplo basico:

```apache
<VirtualHost *:80>
    ServerName gestor.local
    DocumentRoot /var/www/html/gestor_evidencia

    <Directory /var/www/html/gestor_evidencia>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Reiniciar Apache despues de cambios.

## Configuracion recomendada de PHP

Como el codigo limita archivos a 10 MB, se recomienda configurar `php.ini` con valores iguales o superiores:

```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20
memory_limit = 128M
```

Para produccion:

```ini
display_errors = Off
log_errors = On
```

Para desarrollo:

```ini
display_errors = On
error_reporting = E_ALL
```

## Verificacion posterior a la instalacion

Despues de montar el proyecto, validar:

- Que Apache cargue `login.php`.
- Que `conexion.php` conecte a la base de datos.
- Que las tablas existan en `gestor_evidencia`.
- Que se pueda iniciar sesion con un usuario de prueba.
- Que `uploads/files` tenga permisos de escritura.
- Que se pueda subir un PDF menor a 10 MB.
- Que FPDF exista si se usaran reportes PDF.
- Que la extension `zip` este activa si se usaran descargas ZIP.

## Usuarios de prueba

El archivo SQL contiene usuarios de muestra con contrasenas hasheadas. Por seguridad, al montar el sistema se recomienda:

- Cambiar las contrasenas iniciales.
- Crear un nuevo Super Usuario.
- Desactivar o eliminar usuarios que no correspondan al ambiente real.

## Solucion de problemas comunes

### Error de conexion a base de datos

Revisar:

- Nombre de base de datos.
- Usuario y contrasena en `conexion.php`.
- Servicio MySQL/MariaDB activo.
- Permisos del usuario de base de datos.

### No se pueden subir archivos

Revisar:

- Que exista `uploads/files`.
- Permisos de escritura.
- `upload_max_filesize`.
- `post_max_size`.
- Tipo de archivo permitido.
- Tamano maximo de 10 MB.

### No se genera PDF

Revisar:

- Que exista `fpdf/fpdf.php`.
- Que la carpeta `fpdf/font` exista.
- Que el servidor pueda leer la carpeta `fpdf`.

### No se genera ZIP

Revisar que la extension `zip` este activa. En PHP debe existir la clase:

```php
ZipArchive
```

### Problemas con acentos

El proyecto usa `utf8mb4` en la base de datos. Revisar:

- Cotejamiento de la base de datos.
- Codificacion del archivo SQL.
- Que `conexion.php` ejecute `set_charset("utf8")`.

Para una instalacion nueva se recomienda mantener archivos en UTF-8.

