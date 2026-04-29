<?php
/**
 * Gestión del menú (versión proyecto actual)
 * - Requiere sesión iniciada (incluye validacion.php).
 * - Usa header/left-menu/footer del proyecto.
 * - Carga la tabla con AJAX desde vista-gestion-menu.php.
 * - Búsqueda en cliente (tu input superior) + filtro por rol (servidor).
 */
include 'validacion.php'; //Verificar que el usuario ha inicuado sesión
include 'conexion.php';
include 'validacion-permiso.php'; //Verificar que el usuario esta permitido de acceder a esta pagina
include 'header.php';
include 'left-menu.php';

?>

<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Perfil</h1>
      <p class="text-muted-foreground">Administra los datos de tu perfil</p>
    </div>

    
  </header>

  

<!-- SweetAlert2 (opcional para feedback bonito) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<script>
</script>
<?php
include 'footer.php';
?>
