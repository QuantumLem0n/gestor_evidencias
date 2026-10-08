# Síntesis del sistema

## Organizador de Evidencias Académicas para la Evaluación Docente (ODEA)

ODEA, Organizador de Evidencias Académicas, es una plataforma web desarrollada para apoyar la gestión documental de docentes de nivel superior durante procesos de evaluación académica. El sistema permite registrar, clasificar, consultar, evaluar y descargar evidencias relacionadas con la trayectoria docente, tales como libros, artículos, conferencias, constancias, diplomados, actividades de docencia, investigación y otros productos académicos.

El proyecto surge como respuesta a una problemática frecuente en instituciones de educación superior: la dispersión de archivos, formatos y requisitos solicitados por distintos procesos de evaluación docente. En la práctica, un profesor puede necesitar presentar la misma evidencia en diferentes convocatorias, como SNII, PRODEP, ESDEPED u otros instrumentos internos. Cuando la información se administra de forma manual o en carpetas separadas, se duplican esfuerzos, se pierde tiempo localizando documentos y aumenta la posibilidad de omitir requisitos importantes.

ODEA propone centralizar las evidencias en una sola plataforma, asociarlas con tipos de evidencia y relacionarlas con instrumentos de evaluación. De esta manera, el docente captura su información una vez y puede reutilizarla en distintos contextos de evaluación. A su vez, los evaluadores cuentan con una vista organizada para revisar evidencias, emitir calificaciones y consultar reportes.

> **[ESPACIO PARA IMAGEN 1: Pantalla de inicio de sesión de ODEA]**
>
> Insertar captura donde se observe el acceso al sistema con correo y contraseña.

## Objetivo general

El objetivo principal de ODEA es proporcionar una herramienta web para almacenar, organizar y manejar evidencias académicas de docentes, facilitando su disponibilidad para distintos procesos de evaluación en instituciones de educación superior.

## Objetivos específicos

- Permitir el acceso autenticado de usuarios mediante correo y contraseña.
- Administrar perfiles de usuario con roles diferenciados: superusuario, administrador, evaluador y docente.
- Registrar tipos de evidencia académica y sus atributos configurables.
- Asociar tipos de evidencia con instrumentos de evaluación docente.
- Permitir a los docentes cargar archivos y completar metadatos según el tipo de evidencia.
- Facilitar a evaluadores y administradores la revisión y calificación de evidencias.
- Generar descargas y reportes de evidencias aprobadas por instrumento.

## Descripción funcional general

La plataforma se organiza alrededor de cuatro perfiles de usuario. El docente puede cargar evidencias, consultar sus documentos y completar la información solicitada por el sistema. El evaluador puede revisar evidencias que ya cuentan con información completa y emitir una calificación según el instrumento correspondiente. El administrador gestiona usuarios, tipos de evidencia, atributos e instrumentos de evaluación. El superusuario conserva acceso general para tareas de configuración y supervisión.

El sistema utiliza una base de datos relacional para guardar usuarios, roles, evidencias, tipos de evidencia, atributos dinámicos, instrumentos de evaluación y calificaciones. Esta estructura permite que cada tipo de evidencia tenga campos propios. Por ejemplo, un libro puede solicitar autores, título, año de publicación, editorial, número de páginas, URL e ISBN; mientras que una conferencia puede requerir nombre del evento, fecha, ciudad, país, horas impartidas y constancia.

> **[ESPACIO PARA IMAGEN 2: Panel principal o dashboard de ODEA]**
>
> Insertar captura donde se observe la navegación general del sistema y el menú lateral.

## Módulo seleccionado para el anexo de código

Para el documento de código se seleccionó el módulo de **gestión y evaluación de evidencias académicas**, debido a que representa el flujo principal de uso de la plataforma. Este módulo concentra la actividad sustancial del sistema: cargar una evidencia, validar el archivo, capturar atributos dinámicos, revisar la evidencia, calificarla según un instrumento y generar salidas de consulta, descarga o reporte.

No se incluyó todo el código de la plataforma porque el propósito del anexo es mostrar una porción representativa del desarrollo, no duplicar el repositorio completo. El módulo elegido permite observar el uso de sesiones, validaciones, consultas preparadas, manejo de archivos, reglas de negocio, transacciones, generación de archivos ZIP y generación de reportes PDF.

## Flujo del módulo de evidencias

El flujo inicia cuando un docente accede al sistema y registra una nueva evidencia. En este punto se solicita un título, el tipo de evidencia y el archivo principal. El sistema valida que el usuario tenga permiso para crear la evidencia, que el archivo exista, que su tamaño no supere el límite establecido y que el formato corresponda a los tipos permitidos: PDF, JPG, PNG o WEBP.

Después de crear la evidencia, el docente completa sus atributos dinámicos. Estos atributos no están fijos en el formulario, sino que se cargan desde la base de datos según el tipo de evidencia seleccionado. Esto permite que el sistema sea flexible: si en el futuro se agrega un nuevo tipo de documento o se modifican los requisitos de un instrumento, no es necesario rediseñar toda la base de datos ni crear formularios estáticos para cada caso.

> **[ESPACIO PARA IMAGEN 3: Formulario para agregar evidencia]**
>
> Insertar captura del modal o pantalla donde el docente selecciona tipo de evidencia y archivo.

> **[ESPACIO PARA IMAGEN 4: Captura de atributos dinámicos de una evidencia]**
>
> Insertar captura donde se vean campos como autores, título, año, editorial, URL, ISBN u otros metadatos.

Una vez capturada la información, la evidencia puede pasar a revisión. La vista de evaluación muestra solamente evidencias con atributos completos y que tengan al menos un instrumento asociado a su tipo de evidencia. Esto evita que el evaluador revise registros incompletos o que no correspondan a ningún proceso de evaluación configurado.

El evaluador puede asignar una calificación de aprobación o una calificación numérica, dependiendo de la configuración del instrumento. Para instrumentos de aprobación, el sistema acepta valores binarios: aprobado o no aprobado. Para instrumentos numéricos, valida que la calificación se encuentre dentro del rango permitido.

> **[ESPACIO PARA IMAGEN 5: Vista de evaluación de evidencias]**
>
> Insertar captura de la tabla de evidencias listas para evaluar, con avance, estado y acciones.

> **[ESPACIO PARA IMAGEN 6: Modal de evaluación de evidencia]**
>
> Insertar captura donde se observe la asignación de resultado y comentario del evaluador.

## Resultados y salidas del módulo

El módulo permite consultar evidencias aprobadas por instrumento y descargarlas en un archivo ZIP. Esta función resulta útil cuando un docente o un administrador necesita reunir rápidamente los documentos aceptados para un proceso determinado.

También se genera un reporte PDF con las evidencias aprobadas por instrumento. El reporte incluye datos del instrumento, tipo de calificación, total de evidencias aprobadas, docente, tipo de evidencia, fecha de carga, resultado, comentario y atributos capturados. Esta salida facilita la revisión documental y permite conservar evidencia del proceso de evaluación.

> **[ESPACIO PARA IMAGEN 7: Descarga de evidencias aprobadas]**
>
> Insertar captura del modal o pantalla donde se seleccionan evidencias para descarga.

> **[ESPACIO PARA IMAGEN 8: Reporte PDF generado por instrumento]**
>
> Insertar captura de la primera página del reporte PDF de calificaciones aprobadas.

## Arquitectura y tecnologías utilizadas

ODEA está desarrollado como una aplicación web tradicional en PHP, con MySQL/MariaDB como gestor de base de datos. La interfaz utiliza HTML, CSS y JavaScript. El sistema se ejecuta en un entorno compatible con Apache y PHP, como XAMPP, WAMP, Laragon o un servidor LAMP.

La arquitectura del prototipo se organiza por archivos PHP independientes para vistas, modales, endpoints AJAX y operaciones CRUD. Aunque no utiliza un framework MVC formal, sí separa responsabilidades de manera práctica: existen archivos para visualización, archivos para formularios modales, archivos para operaciones de consulta y archivos para guardar o actualizar información.

Entre las prácticas técnicas implementadas se encuentran:

- Uso de sesiones PHP para autenticación.
- Hash de contraseñas mediante funciones seguras de PHP.
- Consultas preparadas con `mysqli` para reducir riesgos de inyección SQL.
- Validación de archivos por extensión, tipo MIME y tamaño.
- Control de acceso por rol.
- Uso de transacciones en operaciones de guardado de atributos.
- Generación de archivos ZIP mediante `ZipArchive`.
- Generación de reportes PDF mediante FPDF.

## Aportación del sistema

La principal aportación de ODEA es ordenar el proceso de recolección y evaluación de evidencias académicas mediante una plataforma centralizada. El sistema reduce la duplicidad de carga documental, facilita la consulta por tipo e instrumento, y permite que los evaluadores trabajen con información más estructurada.

Desde el punto de vista académico, el proyecto demuestra la aplicación de conocimientos de ingeniería en ciencias de la computación en áreas como desarrollo web, modelado de bases de datos relacionales, autenticación, control de roles, manejo de archivos, validación de formularios dinámicos y generación de reportes.

## Conclusión

ODEA representa un prototipo funcional orientado a resolver una necesidad real dentro de los procesos de evaluación docente. Su diseño permite que los docentes administren sus evidencias en un solo lugar y que los evaluadores revisen documentos con criterios más claros. El módulo de gestión y evaluación de evidencias, seleccionado para el anexo de código, muestra el funcionamiento central de la plataforma y evidencia cómo el sistema conecta la carga documental con la evaluación y generación de reportes.

