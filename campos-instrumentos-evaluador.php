<?php
// $prefijoUsuario se define en cada modal para mantener IDs distintos.
$catalogoEvaluador = $conn->query('SELECT id_instrumento, abreviatura, nombre_completo, activo FROM instrumentos ORDER BY id_instrumento');
?>
<fieldset id="<?= $prefijoUsuario ?>_instrumentos" hidden class="form-group">
  <legend>Instrumentos asignados al evaluador</legend>
  <p>Selecciona los instrumentos que puede calificar. Sin seleccion no podra evaluar.</p>
  <?php while ($instrumento = $catalogoEvaluador->fetch_assoc()): ?>
    <label style="display:block; margin:8px 0;">
      <input type="checkbox" name="instrumentos[]" value="<?= (int)$instrumento['id_instrumento'] ?>">
      <?= htmlspecialchars($instrumento['abreviatura'].' - '.$instrumento['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
      <?= $instrumento['activo'] ? '' : '(inactivo; no permite calificar)' ?>
    </label>
  <?php endwhile; ?>
</fieldset>
<script>
(() => {
  const rol = document.getElementById('<?= $prefijoUsuario ?>_rol');
  const campos = document.getElementById('<?= $prefijoUsuario ?>_instrumentos');
  const actualizar = () => {
    campos.hidden = rol.value !== '3';
    campos.disabled = rol.value !== '3';
  };
  rol.addEventListener('change', actualizar);
  rol.form.addEventListener('reset', () => setTimeout(actualizar, 0));
  actualizar();
})();
</script>
