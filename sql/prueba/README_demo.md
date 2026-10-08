# Demostración completa del gestor de evidencias

`demostracion_completa.sql` agrega datos sobre la estructura y los catálogos de
`sql/gestor_evidencia.sql`. Se importa en la base existente seleccionada en
phpMyAdmin. No contiene `USE`, no recrea tablas, no borra ni actualiza datos
existentes y obtiene las relaciones mediante IDs consultados en cada instalación.

## Instalación

1. Desde la raíz del proyecto, ejecutar en PowerShell:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\sql\prueba\preparar_archivos_demo.ps1
   ```

2. En phpMyAdmin, seleccionar **gestor_evidencia**, entrar a **Importar** y elegir
   **sql/prueba/demostracion_completa.sql** (UTF-8). La base debe tener ya el esquema
   y los catálogos del volcado original. Si ya están instalados, importar solamente
   este nuevo archivo. Los instrumentos deben conservar la configuración original
   de aprobación/rechazo.
3. Iniciar sesión con una de las cuentas de `usuarios_demo.csv`. Todas utilizan
   la contraseña **1234**, almacenada con un hash bcrypt distinto por usuario,
   compatible con `password_verify()`.
4. Como docente, revisar **Evidencias**, **Abrir**, los atributos y
   **Calificaciones**. Como evaluador, revisar **Evaluación**.

Las copias PDF ya están preparadas en este espacio de trabajo. Al trasladar el
proyecto, ejecutar de nuevo el paso 1: `uploads/` está excluido por `.gitignore`.
El SQL no puede copiar los archivos al directorio de la aplicación por sí solo.

Se puede repetir la preparación de archivos y la importación secuencialmente:
los registros existentes de la demostración se omiten y se conservan sus cambios.
La copia verifica SHA-256 y se detiene si un destino tiene contenido diferente.
Los registros se reconocen por correo, nombre de tipo con prefijo `[DEMO]`,
slug del atributo y nombre de archivo asociado a su docente/tipo. Renombrar esas
claves puede hacer que una reimportación cree de nuevo el registro original.

## Contenido

- 15 usuarios activos: 10 docentes, 3 evaluadores, 1 administrador y 1 superusuario.
- 15 tipos nuevos, adicionales a los del volcado original.
- 213 definiciones de atributos y 639 valores: todos los atributos tienen datos.
- 45 evidencias visibles: 3 por tipo, distribuidas entre los 10 docentes.
- 40 asociaciones entre tipos e instrumentos, consistentes con las columnas
  `SNI`, `PRODEP` y `ESDEPED` de cada tipo nuevo.
- 120 calificaciones: 105 aprobaciones y 15 rechazos, con evaluador, comentario
  y fechas. Cada evidencia tiene una calificación por cada instrumento asociado.
- 90 copias del PDF original: 45 archivos principales y 45 soportes adicionales.

Los registros corresponden a actividades de 2023, 2024 y 2025. Las fechas de
captura son posteriores a las actividades y las evaluaciones son posteriores a
la captura. Los rechazos simulan falta de pertinencia académica, aunque la captura
esté completa, para probar ambos estados de evaluación.

| Tipo nuevo (todos llevan prefijo `[DEMO]`) | Instrumentos | Atributos | Evidencias |
| --- | --- | ---: | ---: |
| Artículo científico arbitrado e indexado | SNII, PRODEP, ESDEPED | 16 | 3 |
| Capítulo de libro de investigación | SNII, PRODEP, ESDEPED | 14 | 3 |
| Libro de investigación dictaminado | SNII, PRODEP, ESDEPED | 14 | 3 |
| Proyecto de investigación concluido | SNII, PRODEP, ESDEPED | 14 | 3 |
| Dirección de tesis de licenciatura | SNII, PRODEP, ESDEPED | 14 | 3 |
| Dirección de tesis de posgrado | SNII, PRODEP, ESDEPED | 14 | 3 |
| Ponencia en congreso científico | SNII, PRODEP, ESDEPED | 14 | 3 |
| Desarrollo de software con registro | SNII, PRODEP, ESDEPED | 14 | 3 |
| Divulgación científica comunitaria | SNII, PRODEP, ESDEPED | 14 | 3 |
| Estancia de investigación académica | SNII, PRODEP, ESDEPED | 14 | 3 |
| Asignatura de licenciatura impartida | PRODEP, ESDEPED | 15 | 3 |
| Tutoría académica individual | PRODEP, ESDEPED | 14 | 3 |
| Material didáctico original validado | PRODEP, ESDEPED | 14 | 3 |
| Gestión de cuerpo académico | PRODEP, ESDEPED | 14 | 3 |
| Curso de actualización docente acreditado | PRODEP, ESDEPED | 14 | 3 |

Todos incluyen título, responsable, institución, fechas, descripción, folio y
soporte adicional, más campos específicos: datos editoriales, estudiantes,
programas educativos, productos, financiamiento, horas o resultados, según el
tipo. Se cubren texto, texto largo, enteros, decimales, fechas, booleanos, JSON y
archivos. Los archivos adicionales son opcionales en la definición, pero todos
tienen un PDF cargado en esta demostración.

## Cuentas de acceso

| Perfil | Correo de ejemplo | Contraseña |
| --- | --- | --- |
| Docente | ana.mendoza@demo.example | 1234 |
| Evaluador SNII | evaluador.snii@demo.example | 1234 |
| Evaluador PRODEP | evaluador.prodep@demo.example | 1234 |
| Evaluador ESDEPED | evaluador.esdeped@demo.example | 1234 |
| Administrador | administrador@demo.example | 1234 |
| Superusuario | superusuario@demo.example | 1234 |

La lista completa está en `usuarios_demo.csv`. Las etiquetas de los evaluadores
indican qué calificaciones emitieron en la demostración; la aplicación no dispone
de una restricción de permisos por instrumento para estos usuarios.

## Archivos y botón Abrir

Origen: `sql/prueba/Evidencia de prueba.pdf`.

Ejemplo de archivo principal: `uploads/files/demo_ge_01_01.pdf`.
En `evidencias.archivo` se guarda solamente `demo_ge_01_01.pdf`, pues la vista
antepone `uploads/files/` al construir el enlace **Abrir**.

El anexo correspondiente es `uploads/files/demo_ge_01_01_anexo.pdf`, registrado
en `evidencia_valores_atributo.valor_archivo`. Cada evidencia tiene archivos con
nombres propios para que eliminar una evidencia no afecte los PDF de las demás.
Las 90 copias tienen exactamente el mismo contenido de la hoja suministrada.

## Alcance de las asociaciones

Los nombres, instituciones, productos, identificadores y evaluaciones son datos
de prueba. Los ISBN/ISSN son ejemplos de formato; no acreditan las obras descritas.
Las asociaciones son una selección ilustrativa para probar la plataforma, sin
representar una matriz oficial de elegibilidad ni un dictamen de estos programas.

Se tomó como orientación la producción científica y formación de comunidad del
[SNII](https://www.secihti.mx/snii/), las funciones de docencia, generación de
conocimiento, tutorías y gestión del
[PRODEP](https://dgesui.ses.sep.gob.mx/programas/programa-para-el-desarrollo-profesional-docente-para-el-tipo-superior-s247-prodep),
y las funciones universitarias de docencia, investigación y difusión de la
cultura de la
[convocatoria ESDEPED BUAP 2025](https://academico.buap.mx/academico/estimulos/Docs/ESDEPED25/convocatoria.pdf).
Se conserva la abreviatura **SNII** del volcado y la columna histórica **SNI**.
Las calificaciones de los tres instrumentos son **1 = aprobado** y
**0 = no aprobado**, conforme al esquema existente; no son puntajes oficiales.

## Verificación realizada

Se importó el volcado original y este SQL en una base temporal de MariaDB 10.4.32,
con los autoincrementos elevados a 900 para verificar que las relaciones no
dependen de IDs fijos. Se comprobaron los conteos, la conservación de los registros
originales, los atributos completos y sus tipos, las asociaciones, los evaluadores,
los resultados y los 15 hashes mediante `password_verify('1234', ...)`.

Dos reimportaciones confirmaron que no se duplican registros ni se sobrescriben
cambios manuales. Las 90 copias coincidieron por SHA-256 con el PDF original y
los 90 enlaces HTTP devolvieron ese contenido con tipo `application/pdf`.
La base temporal se eliminó al finalizar; la importación en la base de la
plataforma queda a cargo del paso 2 de instalación.
