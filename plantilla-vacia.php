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

    </div>

    

</section>

<?php
// Cierre del layout principal (</main></body></html>)
include 'footer.php';
?>