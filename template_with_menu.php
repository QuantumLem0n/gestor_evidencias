<?php
  /**
   * PLANTILLA CON MENÚ LATERAL
   * - Incluye header (CSS global + header + <main> abierto)
   * - Incluye left-menu (sidebar responsive)
   * - No contiene JS adicional (más allá del del menú)
   * - Usa tus tokens y estilos existentes
   *
   * Personaliza rol y usuario si lo deseas:
   *   $roleClass = 'student' | 'teacher'
   *   $currentUser = ['name'=>'...', 'email'=>'...']
   */

  // $roleClass = 'teacher'; // opcional (por defecto suele ser 'student')
  // $currentUser = ['name'=>'Invitado','email'=>''];

  include 'header.php';
  include 'left-menu.php';
?>

<!--
  CONTENIDO DE LA PÁGINA
  Recomendado: estructura de secciones con cards/tabs/tablas.
  El <main> ya está abierto en header.php y su margen izquierdo se ajusta
  automáticamente según el estado del sidebar (expandido/colapsado/off-canvas).
-->

<section class="space-y-6">
  <header>
    <h1 class="text-2xl font-semibold">Título de página</h1>
    <p class="text-muted-foreground">Breve descripción de esta vista.</p>
  </header>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <article class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold">Card 1</h3>
        <p class="card-description">Subtítulo o métrica</p>
      </div>
      <div class="card-content">
        <p>Contenido de ejemplo.</p>
      </div>
    </article>

    <article class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold">Card 2</h3>
      </div>
      <div class="card-content">
        <button class="btn" type="button">Acción</button>
        <button class="btn btn-primary" type="button">Primaria</button>
      </div>
    </article>

    <article class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
      <div class="card-header" style="padding-bottom:12px;">
        <h3 class="text-lg font-semibold">Card 3</h3>
      </div>
      <div class="card-content">
        <span class="badge default">Badge</span>
        <span class="badge secondary">Etiqueta</span>
      </div>
    </article>
  </div>

  <section class="tabs space-y-4">
    <div class="tabs-list">
      <button class="tabs-trigger" data-tab="a" data-active="true">Pestaña A</button>
      <button class="tabs-trigger" data-tab="b" data-active="false">Pestaña B</button>
    </div>

    <div id="tab-a" class="tabs-content" data-active="true">
      <div class="card border-2 student-border-light">
        <div class="card-header" style="padding-bottom:12px;">
          <h3 class="text-lg font-semibold">Contenido A</h3>
        </div>
        <div class="card-content">
          <table class="table">
            <thead>
              <tr><th>Col 1</th><th>Col 2</th><th>Col 3</th></tr>
            </thead>
            <tbody>
              <tr><td>Fila 1</td><td>Dato</td><td><span class="badge secondary">Ok</span></td></tr>
              <tr><td>Fila 2</td><td>Dato</td><td><button class="btn btn-primary" type="button">Ver</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="tab-b" class="tabs-content" data-active="false">
      <div class="card border-2 student-border-light">
        <div class="card-header" style="padding-bottom:12px;">
          <h3 class="text-lg font-semibold">Contenido B</h3>
        </div>
        <div class="card-content">
          <form class="space-y-4">
            <div class="form-group">
              <label class="label" for="nombre">Nombre</label>
              <input class="input" id="nombre" placeholder="Tu nombre" />
            </div>
            <div class="form-group">
              <label class="label" for="opcion">Opción</label>
              <select class="input" id="opcion">
                <option value="" selected disabled>Selecciona</option>
                <option>A</option>
                <option>B</option>
              </select>
            </div>
            <button class="btn btn-primary" type="submit">Guardar</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</section>

<?php include 'footer.php'; ?>
