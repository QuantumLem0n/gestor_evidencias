# Contexto y alcance del proyecto

## Identificacion

**Nombre del repositorio:** `gestor_evidencias`

**Nombre de la base de datos:** `gestor_evidencia`

**Tipo de proyecto:** plataforma web para gestion de evidencias academicas orientada a procesos de evaluacion docente en educacion superior.

**Contexto academico:** proyecto de tesina universitaria para Ingenieria en Ciencias de la Computacion de la 
Benemérita Universidad Autónoma de Puebla.

## Descripcion general

El proyecto consiste en una plataforma web que permite registrar, organizar, consultar, evaluar y descargar evidencias academicas generadas por docentes de nivel superior. Estas evidencias pueden representar productos o actividades como libros, articulos, conferencias, diplomados, constancias, actividades de docencia, investigacion y otros documentos relacionados con la trayectoria academica.

La plataforma busca apoyar procesos de evaluacion docente utilizados en Mexico, permitiendo clasificar las evidencias por tipo y asociarlas con instrumentos o programas de evaluacion como:

- **SNII:** Sistema Nacional de Investigadores e Investigadoras.
- **PRODEP:** Programa para el Desarrollo Profesional Docente.
- **ESDEPED:** Estimulos al Desempeno del Personal Docente.

El sistema no sustituye los lineamientos oficiales de dichos instrumentos. Su funcion es servir como gestor documental y herramienta de apoyo para ordenar evidencias, identificar a que instrumentos pueden aplicar y registrar su resultado de revision.

## Problema que atiende

En procesos de evaluacion docente, los profesores suelen reunir evidencias academicas en distintos formatos y fuentes: archivos PDF, constancias, imagenes, libros publicados, articulos, reconocimientos o documentos institucionales. Cuando esta informacion no se encuentra centralizada, se dificulta:

- Localizar rapidamente los documentos requeridos.
- Saber que evidencia corresponde a cada instrumento de evaluacion.
- Validar si una evidencia cuenta con los atributos minimos necesarios.
- Descargar conjuntos de evidencias aprobadas para un instrumento especifico.
- Dar seguimiento a la revision realizada por evaluadores o administradores.

El sistema propone una solucion web que concentra los documentos, los clasifica y permite evaluarlos bajo criterios configurables.

## Objetivo general

Desarrollar una plataforma web para la administracion de evidencias academicas de docentes de nivel superior, con capacidad de clasificar evidencias por tipo, asociarlas a instrumentos de evaluacion docente y registrar calificaciones o aprobaciones para facilitar su consulta y descarga.

## Objetivos especificos

- Permitir el acceso autenticado por usuario y rol.
- Gestionar usuarios con perfiles como Super Usuario, Administrador, Evaluador y Docente.
- Registrar tipos de evidencia academica.
- Asociar tipos de evidencia con instrumentos de evaluacion docente.
- Configurar atributos dinamicos para cada tipo de evidencia.
- Permitir a docentes subir evidencias y completar sus atributos.
- Permitir a evaluadores o administradores calificar evidencias.
- Consultar evidencias aprobadas por instrumento.
- Descargar evidencias aprobadas en archivo ZIP.
- Generar reportes PDF de calificaciones aprobadas por instrumento.
- Administrar el menu de navegacion visible segun el rol.

## Alcance funcional

El proyecto contempla los siguientes modulos principales:

### Autenticacion y sesiones

El sistema cuenta con inicio y cierre de sesion. La autenticacion se realiza mediante correo y contrasena. Las contrasenas se almacenan como hash usando `password_hash` y se validan con `password_verify`.

### Gestion de usuarios

Permite registrar, consultar, actualizar y eliminar usuarios. Cada usuario tiene un rol asignado, estado activo, datos personales y correo unico.

Roles contemplados en la base de datos:

- **Super Usuario:** acceso general al sistema.
- **Administrador:** administracion operativa de usuarios, evidencias, tipos e instrumentos.
- **Evaluador:** revision y calificacion de evidencias.
- **Docente:** carga y seguimiento de sus propias evidencias.

### Gestion de tipos de evidencia

El sistema administra categorias como Libro, Conferencia/Ponencia, Clase de diplomado, Investigacion, Horas frente a grupo, Constancia, Diplomado, Articulo y Docencia.

Cada tipo puede asociarse a uno o varios instrumentos de evaluacion mediante la tabla `instrumento_tipo_evidencia`.

### Atributos dinamicos por tipo de evidencia

Cada tipo de evidencia puede tener atributos propios. Por ejemplo, un libro puede solicitar autores, titulo, ano de publicacion, editorial, numero de paginas, URL e ISBN.

Los atributos tienen reglas de validacion como:

- Requerido o no requerido.
- Unico por evidencia.
- Multiple o simple.
- Longitud minima y maxima.
- Valor minimo y maximo.
- Tipo de almacenamiento.
- Expresion regular para formatos como DOI, ISBN, ISSN, URL o correo.

### Gestion de evidencias

Los docentes pueden cargar archivos de evidencia y asociarlos con un tipo de evidencia. Los archivos aceptados por el codigo son PDF e imagenes en formatos JPG, PNG y WEBP, con limite de 10 MB.

Los archivos se guardan localmente en `uploads/files`, carpeta excluida del repositorio por `.gitignore`.

### Captura de detalle de evidencias

Una vez creada una evidencia, se pueden capturar sus atributos dinamicos. Estos valores se guardan en la tabla `evidencia_valores_atributo`, usando columnas separadas segun el tipo de dato: texto corto, texto largo, entero, decimal, fecha, booleano, archivo o JSON.

### Evaluacion de evidencias

Usuarios que no sean docentes pueden evaluar evidencias. Antes de calificar, el sistema valida que la evidencia tenga todos sus atributos completos.

Los instrumentos pueden manejar:

- Calificacion por aprobacion: valor 1 para aprobado y 0 para no aprobado.
- Calificacion numerica: valor dentro de un rango minimo y maximo.

La calificacion se guarda por combinacion unica de evidencia e instrumento.

### Descargas y reportes

El sistema permite consultar evidencias aprobadas por instrumento y descargarlas como ZIP. Tambien genera reportes PDF con evidencias aprobadas por instrumento.

La descarga ZIP depende de la extension `ZipArchive` de PHP. La generacion PDF depende de FPDF, que debe estar disponible en la carpeta `fpdf`.

### Menu dinamico por rol

El menu lateral se genera desde la base de datos usando las tablas `menu_pagina`, `menu_rol` e `iconos`. Esto permite controlar que paginas ve cada rol y ocultar opciones sin editar directamente el HTML.

## Alcance no funcional

El sistema esta desarrollado como aplicacion PHP tradicional, con archivos PHP por vista, modal y endpoint. Usa MySQL/MariaDB como base de datos y CSS propio en `styles/global.css`.

El proyecto esta pensado para ejecutarse en un entorno tipo XAMPP, WAMP, LAMP o servidor compatible con PHP y MySQL/MariaDB.

## Fuera de alcance actual

Con base en el estado actual del codigo, quedan fuera del alcance implementado:

- Integracion directa con APIs oficiales de SNII, PRODEP o ESDEPED.
- Firma electronica o validacion criptografica de documentos.
- Almacenamiento en nube.
- Historial detallado de cambios por cada evidencia.
- Recuperacion automatica de contrasena.
- Motor avanzado de busqueda semantica.
- Pruebas automatizadas.
- API REST formal versionada.

## Usuarios objetivo

- **Docentes:** registran evidencias academicas y completan la informacion requerida.
- **Evaluadores:** revisan evidencias y emiten aprobacion o calificacion.
- **Administradores:** mantienen usuarios, tipos de evidencia, instrumentos y configuracion general.
- **Comites academicos:** pueden usar la informacion organizada para procesos de evaluacion interna.

## Valor academico del proyecto

El proyecto demuestra la aplicacion de conocimientos de Ingenieria en Ciencias de la Computacion en:

- Desarrollo web con PHP, HTML, CSS y JavaScript.
- Modelado de bases de datos relacionales.
- Autenticacion y control de sesiones.
- Control de acceso basado en roles.
- Manejo de archivos.
- Validacion de formularios y datos dinamicos.
- Generacion de reportes y paquetes de descarga.
- Diseno de una solucion informatica para un problema academico real.

