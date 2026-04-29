<?php
/**
 * Dashboard de Instrumentos
 * - Muestra una gráfica de dona por instrumento con:
 *   Compatibles vs Aprobadas (o Evaluadas/>=umbral en NUMERICA).
 * - Roles:
 *   - 1,2,3: estadísticas generales.
 *   - 4: estadísticas solo del docente logueado.
 */

include 'validacion.php';   // establece $user_role, $user_id
include 'conexion.php';

//$page = "dashboard-instrumentos.php";

/** Umbral de aprobación para instrumentos NUMÉRICOS (60% del rango por defecto) */
$NUMERIC_PASS_PCT = 0.60;

// =========================
//  Consulta de estadísticas
// =========================
// Nota: Contamos evidencias "compatibles" por cada instrumento a través de la tabla puente.
//       "Aprobadas" depende del tipo_calificacion.
//       Para NUMERICA se usa el umbral descrito arriba (ajustable).
//
//       Se consideran sólo evidencias no ocultas (e.ocultar = 0).
//       Si $user_role == 4, se filtra por e.id_docente = $user_id.

$sql = "SELECT
  i.id_instrumento,
  i.abreviatura,
  i.nombre_completo,
  i.tipo_calificacion,
  COALESCE(i.min_calificacion, 0)  AS min_cal,
  COALESCE(i.max_calificacion, 10) AS max_cal,

  COUNT(DISTINCT e.id_evidencia) AS compatibles,

  COUNT(DISTINCT
    CASE
      WHEN i.tipo_calificacion = 'APROBACION' AND ce.resultado = 1 THEN e.id_evidencia
      WHEN i.tipo_calificacion = 'NUMERICA'
        AND ce.resultado IS NOT NULL
        AND ce.resultado >= (
          COALESCE(i.min_calificacion, 0)
          + ? * (COALESCE(i.max_calificacion, 10) - COALESCE(i.min_calificacion, 0))
        )
      THEN e.id_evidencia
      ELSE NULL
    END
  ) AS aprobadas

FROM instrumentos i
JOIN instrumento_tipo_evidencia ite
  ON ite.id_instrumento = i.id_instrumento
LEFT JOIN evidencias e
  ON e.id_tipo_evidencia = ite.id_tipo_evidencia
  AND e.ocultar = 0
  " . ($user_role == 4 ? "AND e.id_docente = ?" : "") . "
LEFT JOIN calificacion_evidencia ce
  ON ce.id_evidencia   = e.id_evidencia
 AND ce.id_instrumento = i.id_instrumento

WHERE i.activo = 1
GROUP BY
  i.id_instrumento, i.abreviatura, i.nombre_completo, i.tipo_calificacion,
  min_cal, max_cal
ORDER BY i.id_instrumento ASC
";

if ($stmt = $conn->prepare($sql)) {
  if ($user_role == 4) {
    // bind: (double) pct, (int) id_docente
    $stmt->bind_param('di', $NUMERIC_PASS_PCT, $user_id);
  } else {
    // bind: (double) pct
    $stmt->bind_param('d', $NUMERIC_PASS_PCT);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $stats = [];
  $totCompat = 0; $totAprob = 0;

  while ($r = $res->fetch_assoc()) {
    $compatibles = (int)$r['compatibles'];
    $aprobadas   = (int)$r['aprobadas'];
    $pendientes  = max(0, $compatibles - $aprobadas);

    $stats[] = [
      'id_instrumento'   => (int)$r['id_instrumento'],
      'abreviatura'      => $r['abreviatura'],
      'nombre'           => $r['nombre_completo'],
      'tipo'             => $r['tipo_calificacion'], // APROBACION / NUMERICA
      'min_cal'          => (float)$r['min_cal'],
      'max_cal'          => (float)$r['max_cal'],
      'compatibles'      => $compatibles,
      'aprobadas'        => $aprobadas,
      'pendientes'       => $pendientes
    ];

    $totCompat += $compatibles;
    $totAprob  += $aprobadas;
  }
  $stmt->close();
} else {
  $stats = [];
  $totCompat = 0; $totAprob = 0;
}

$totPend = max(0, $totCompat - $totAprob);

// Helper para escapar HTML
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

include 'header.php';
include 'left-menu.php';
?>

<!-- ============================
     CONTENIDO
     ============================ -->
<section class="space-y-6">

  <!-- Encabezado -->
  <header class="mb-2">
    <h1 class="text-2xl font-semibold">
      Estadísticas por instrumento
    </h1>
    <p class="text-muted-foreground">
      <?php if (in_array((int)$user_role, [1,2,3], true)): ?>
        Vista general de evidencias visibles (compatibles vs aprobadas).
      <?php else: ?>
        Tus evidencias compatibles vs aprobadas
      <?php endif; ?>
    </p>
  </header>

  <!-- KPIs compactos (reemplaza el bloque anterior) -->
  <div class="kpi-set">
    <!-- Compatibles -->
    <div class="kpi" title="Evidencias con al menos un instrumento asignado">
      <div class="ico" aria-hidden="true">
        <!-- icono: carpeta/check -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M3 7h6l2 2h10v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M9 14l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div>
        <div class="val"><?php echo number_format($totCompat); ?></div>
        <div class="lbl">Compatibles</div>
      </div>
    </div>

    <!-- Aprobadas -->
    <div class="kpi" title="Cumplen criterio (resultado=1 o ≥ umbral en numéricas)">
      <div class="ico" aria-hidden="true">
        <!-- icono: badge/check -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M12 3l2.5 2.5L17 3l2 2-2.5 2.5L19 10l-2 2-2.5-2.5L12 12l-2.5-2.5L7 12l-2-2 2.5-2.5L5 5l2-2 2.5 2.5L12 3z" stroke="currentColor" stroke-width="1.2" opacity=".6"/>
          <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div>
        <div class="val"><?php echo number_format($totAprob); ?></div>
        <div class="lbl">Aprobadas</div>
      </div>
    </div>

    <!-- Pendientes -->
    <div class="kpi" title="Compatibles que aún no alcanzan criterio de aprobación">
      <div class="ico" aria-hidden="true">
        <!-- icono: reloj/pendiente -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
          <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div>
        <div class="val"><?php echo number_format($totPend); ?></div>
        <div class="lbl">Pendientes</div>
      </div>
    </div>
  </div>


  <!-- Nota para instrumentos numéricos -->
  <div class="text-xs text-muted-foreground">
    <strong>Nota:</strong> para instrumentos <em>NUMÉRICOS</em> se considera “aprobada” si la calificación es ≥ 
    <code>min + <?php echo (int)($NUMERIC_PASS_PCT*100); ?>% del rango</code>.
    <!-- Ajusta el porcentaje en <code>$NUMERIC_PASS_PCT</code> dentro de este archivo. -->
  </div>

  <!-- Tarjetas con gráficas por instrumento -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <?php if (empty($stats)): ?>
      <article class="card border-2 student-border-light">
        <div class="card-content">
          <p class="text-muted-foreground">No hay instrumentos activos o no hay evidencias disponibles.</p>
        </div>
      </article>
    <?php else: ?>
      <?php foreach ($stats as $item): ?>
        <?php
          $id     = (int)$item['id_instrumento'];
          $abbr   = $item['abreviatura'];
          $nombre = $item['nombre'];
          $tipo   = $item['tipo'];
          $compat = (int)$item['compatibles'];
          $aprob  = (int)$item['aprobadas'];
          $pend   = (int)$item['pendientes'];
          $pct    = ($compat > 0) ? round(($aprob / $compat) * 100) : 0;
        ?>
        <article class="card border-2 student-border-light hover:shadow-lg hover:border-student-border">
          <div class="card-header" style="padding-bottom:12px;">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold"><?php echo h($abbr); ?></h3>
              <span class="badge"><?php echo h($tipo); ?></span>
            </div>
            <p class="card-description"><?php echo h($nombre); ?></p>
          </div>
          <div class="card-content">
            <?php if ($compat > 0): ?>
              <div class="flex items-center gap-4">
                <div style="width:130px;height:130px;">
                  <canvas id="chart_inst_<?php echo $id; ?>"></canvas>
                </div>
                <div class="space-y-1 text-sm">
                  <div><strong>Compatibles:</strong> <?php echo number_format($compat); ?></div>
                  <div><strong>Aprobadas:</strong> <?php echo number_format($aprob); ?></div>
                  <div><strong>Pendientes:</strong> <?php echo number_format($pend); ?></div>
                  <div class="text-muted-foreground"><strong>Avance:</strong> <?php echo $pct; ?>%</div>
                </div>
              </div>
            <?php else: ?>
              <p class="text-muted-foreground">Sin evidencias compatibles para este instrumento.</p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</section>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function(){
  // Datos pasados desde PHP
  const stats = <?php echo json_encode($stats, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);?>;

  // Paleta simple (aprobadas / pendientes)
  const COLORS = {
    ok:   'rgba(16, 185, 129, 0.9)',   // verde
    wait: 'rgba(234, 179, 8, 0.9)',    // amarillo
    okBorder:   'rgba(16, 185, 129, 1)',
    waitBorder: 'rgba(234, 179, 8, 1)'
  };

  stats.forEach(s => {
    const compat = Number(s.compatibles) || 0;
    if (compat <= 0) return; // no grafica si no hay compatibilidad

    const aprob = Number(s.aprobadas) || 0;
    const pend  = Math.max(0, compat - aprob);

    const ctx = document.getElementById('chart_inst_' + s.id_instrumento);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Aprobadas', 'Pendientes'],
        datasets: [{
          data: [aprob, pend],
          backgroundColor: [COLORS.ok, COLORS.wait],
          borderColor: [COLORS.okBorder, COLORS.waitBorder],
          borderWidth: 1,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        cutout: '65%',
        plugins: {
          legend: { display: true, position: 'bottom' },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const val = ctx.parsed;
                const total = aprob + pend;
                const pct = total > 0 ? (val * 100 / total).toFixed(1) : '0.0';
                return `${ctx.label}: ${val} (${pct}%)`;
              }
            }
          }
        }
      }
    });
  });
})();
</script>
  <style>
    .kpi-set {
      display: grid;
      grid-template-columns: repeat(1, minmax(0,1fr));
      gap: 12px;
    }
    @media (min-width: 768px) {
      .kpi-set { grid-template-columns: repeat(3, 1fr); }
    }
    .kpi {
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid var(--border, #e5e7eb);
      border-radius: 12px;
      padding: 10px 12px;
      background: var(--card, #fff);
      transition: box-shadow .15s ease, border-color .15s ease, transform .15s ease;
    }
    .kpi:hover {
      box-shadow: 0 6px 16px rgba(0,0,0,.06);
      border-color: var(--border-strong, #d1d5db);
    }
    .kpi .ico {
      width: 36px; height: 36px;
      display: grid; place-items: center;
      border-radius: 10px;
      background: var(--muted, #f3f4f6);
      flex: none;
    }
    .kpi .val {
      font-weight: 700;
      font-size: 1.1rem; /* más pequeño que 3xl */
      line-height: 1.1;
    }
    .kpi .lbl {
      font-size: .75rem;
      color: var(--muted-foreground, #6b7280);
      line-height: 1.2;
      margin-top: 1px;
    }
  </style>
<?php
include 'footer.php';
