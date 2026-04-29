<?php
  /**
   * ============================
   * PLANTILLA BASE DE PÁGINA
   * ============================
*/
  include 'header.php';
  include 'left-menu.php';
?>

<!-- =========================================================
     CONTENIDO DE LA PÁGINA (agrega aquí tu layout y componentes)
     ========================================================= -->

<!--
  RECOMENDACIÓN: utiliza la utilería de grillas que ya tienes en global.css.
  - .grid .grid-cols-1 .md:grid-cols-2 .md:grid-cols-3 .lg:grid-cols-3
  - .gap-4 .gap-6
-->

<section class="space-y-6">
  <!-- Ejemplo: Título y descripción de la página -->
  <header>
    <h1 class="text-2xl font-semibold">Título de la Página</h1>
    <p class="text-muted-foreground">Descripción breve o instrucciones de esta sección.</p>
  </header>

  <!-- Ejemplo: Fila de tarjetas (Cards) reutilizables -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Card 1 -->
    <article class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold">Card A</h3>
        <p class="card-description">Subtítulo o descripción</p>
      </div>
      <div class="card-content">
        <p>Contenido de la tarjeta. Puedes poner métricas, texto o listas.</p>
      </div>
    </article>

    <!-- Card 2 (variante para rol teacher: deja el HTML igual; cambia el rol con $roleClass) -->
    <article class="card border-2 teacher-border-light hover:shadow-lg hover:border-teacher-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold">Card B</h3>
        <p class="card-description">Otra descripción</p>
      </div>
      <div class="card-content">
        <p>Contenido de la tarjeta para profesor.</p>
      </div>
    </article>

    <!-- Card 3 con badges y botones -->
    <article class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold">Card C</h3>
      </div>
      <div class="card-content">
        <p style="margin-bottom:12px;">Ejemplo de badges y botones:</p>
        <!-- Badges (usa .badge, .default, .secondary) -->
        <div class="space-x-2">
          <span class="badge default">Primario</span>
          <span class="badge secondary">Secundario</span>
        </div>
        <!-- Botones (usa .btn y .btn-primary) -->
        <div class="space-x-2" style="margin-top:12px;">
          <button class="btn" type="button">Acción</button>
          <button class="btn btn-primary" type="button">Acción Primaria</button>
        </div>
      </div>
    </article>
  </div>

  <!-- Ejemplo: Tabs (marcado estático, sin JS) -->
  <!--
    Cómo activar una pestaña sin JS:
    - Coloca data-active="true" en el botón y en el panel que quieras mostrar.
    - Para interactividad, usa el mismo patrón que ya tenemos en login/teacher/student (se integrará luego).
  -->
  <section class="tabs space-y-4">
    <div class="tabs-list">
      <button class="tabs-trigger" data-tab="tab1" data-active="true">Pestaña 1</button>
      <button class="tabs-trigger" data-tab="tab2" data-active="false">Pestaña 2</button>
      <button class="tabs-trigger" data-tab="tab3" data-active="false">Pestaña 3</button>
    </div>

    <div id="tab-tab1" class="tabs-content" data-active="true">
      <div class="card border-2 student-border-light">
        <div class="card-header" style="padding-bottom:12px;">
          <h3 class="text-lg font-semibold">Contenido de Pestaña 1</h3>
          <p class="card-description">Puedes colocar aquí cualquier layout.</p>
        </div>
        <div class="card-content">
          <p>Texto de ejemplo dentro de la pestaña 1.</p>
        </div>
      </div>
    </div>

    <div id="tab-tab2" class="tabs-content" data-active="false">
      <div class="card border-2 teacher-border-light">
        <div class="card-header" style="padding-bottom:12px;">
          <h3 class="text-lg font-semibold">Contenido de Pestaña 2</h3>
        </div>
        <div class="card-content">
          <!-- Tabla base sin JS -->
          <table class="table">
            <thead>
              <tr>
                <th>Columna A</th>
                <th>Columna B</th>
                <th>Columna C</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Fila 1A</td>
                <td>Fila 1B</td>
                <td><span class="badge secondary">Etiqueta</span></td>
              </tr>
              <tr>
                <td>Fila 2A</td>
                <td>Fila 2B</td>
                <td><button class="btn btn-primary" type="button">Ver</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="tab-tab3" class="tabs-content" data-active="false">
      <div class="card border-2 student-border-light">
        <div class="card-header" style="padding-bottom:12px;">
          <h3 class="text-lg font-semibold">Contenido de Pestaña 3</h3>
        </div>
        <div class="card-content">
          <!-- Formulario base (sin JS) -->
          <form class="space-y-4">
            <div class="form-group">
              <label class="label" for="input-1">Etiqueta</label>
              <input class="input" id="input-1" placeholder="Escribe algo..." />
            </div>
            <div class="form-group">
              <label class="label" for="select-1">Selección</label>
              <select class="input" id="select-1">
                <option value="" selected disabled>Selecciona una opción</option>
                <option>Opción A</option>
                <option>Opción B</option>
              </select>
            </div>
            <button class="btn btn-primary" type="submit">Enviar</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Ejemplo: Modal (estructura estática, requiere JS para abrir/cerrar)
       - Usa el patrón global del modal (dialog-backdrop + dialog + dialog-card).
       - Para que NO se vea aquí, mantenemos data-open="false".
       - Cuando agregues JS, alterna data-open entre "true"/"false" en ambos elementos.
  -->
  <section aria-label="Modal de ejemplo (estructura)">
    <div class="dialog-backdrop" data-open="false"></div>
    <div class="dialog" data-open="false" aria-hidden="true">
      <div class="dialog-card">
        <div class="dialog-header">
          <h4 class="dialog-title">Título del Modal</h4>
          <p class="dialog-desc">Texto de soporte o descripción breve.</p>
        </div>
        <div class="space-y-4">
          <p>Contenido del modal (texto, inputs, etc.).</p>
          <div class="dialog-actions">
            <button class="btn" type="button">Cancelar</button>
            <button class="btn btn-primary" type="button">Acción</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Nota: Este bloque solo documenta la estructura. Puedes borrarlo en páginas que no usen modal. -->
  </section>

</section>

<?php
  // Cierre del layout principal (</main></body></html>)
  include 'footer.php';
?>