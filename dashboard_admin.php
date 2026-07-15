<?php
// dashboard_admin.php
// Panel gerencial del Canciller: KPIs y gráficas comparando un periodo de
// análisis (mes 1 / módulo 1) contra un periodo comparativo (mes 2 / módulo 2).
// Las consultas viven en MetricasCanciller; aquí solo se orquesta y presenta.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admin');

$metricas = new MetricasCanciller(Database::conexion());

$meses_nombres = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
$anio = 2026;

// Filtros seleccionados
$mes_1 = $_GET['mes_1'] ?? date('n');
$mes_2 = $_GET['mes_2'] ?? (date('n') == 1 ? 12 : date('n') - 1);
$mod_1 = $_GET['mod_1'] ?? 'todos';
$mod_2 = $_GET['mod_2'] ?? 'todos';

// Botones rápidos
if (isset($_GET['ver_todos_meses'])) {
    $mes_1 = 'todos';
    $mes_2 = 'todos';
}
if (isset($_GET['ver_todos_modulos'])) {
    $mod_1 = 'todos';
    $mod_2 = 'todos';
}

// Rango de fechas de un mes seleccionado ('todos' = año completo).
// Si se compara Enero contra Diciembre, Diciembre pertenece al año anterior.
function rangoDelMes($mes, int $anio, array $nombres, $mes_referencia = null): array
{
    if ($mes === 'todos') {
        return ["$anio-01-01", "$anio-12-31", 'Anual'];
    }
    if ($mes_referencia == 1 && $mes == 12) {
        $anio--;
    }
    $inicio = sprintf('%04d-%02d-01', $anio, $mes);
    return [$inicio, date('Y-m-t', strtotime($inicio)), $nombres[(int) $mes]];
}

[$fi_1, $ff_1, $label_mes_1] = rangoDelMes($mes_1, $anio, $meses_nombres);
[$fi_2, $ff_2, $label_mes_2] = rangoDelMes($mes_2, $anio, $meses_nombres, $mes_1);

$crecimiento = function (float $actual, float $anterior): float {
    if ($anterior > 0) {
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
    return $actual > 0 ? 100 : 0;
};

// Valores por defecto: si una consulta falla, la página igual se renderiza
// y el aviso rojo muestra el detalle del error en lugar de romper el HTML.
$modulos_disponibles = [];
$total_estudiantes = $total_ordinario = $total_validacion = 0;
$nuevos_mes_actual = $nuevos_mes_anterior = 0;
$crecimiento_est = $crecimiento_ingresos = 0;
$ingresos_mes_actual = $ingresos_mes_anterior = $total_proyectado = 0.0;
$carreras_activas = 0;
$carreras_labels = $carreras_valores = [];
$carrera_top_nombre = 'N/A';
$carrera_top_porcentaje = 0;
$pagos_consolidado = $pagos_revision = $pagos_rechazado = 0.0;
$ingresos_anuales_data = array_fill(0, 12, 0.0);

try {
    $modulos_disponibles = $metricas->modulosDisponibles();

    // KPI 1: población estudiantil (global del módulo analizado)
    $totales = $metricas->totalesEstudiantes($mod_1);
    $total_estudiantes = $totales['total'];
    $total_ordinario   = $totales['ordinario'];
    $total_validacion  = $totales['validacion'];

    // KPI 2: ingresos nuevos del periodo vs comparativo
    $nuevos_mes_actual   = $metricas->nuevosEstudiantesEntre($mod_1, $fi_1, $ff_1);
    $nuevos_mes_anterior = $metricas->nuevosEstudiantesEntre($mod_2, $fi_2, $ff_2);
    $crecimiento_est = $mes_1 === 'todos' ? 0 : $crecimiento($nuevos_mes_actual, $nuevos_mes_anterior);

    // KPI 3: ingresos consolidados del periodo vs comparativo
    $ingresos_mes_actual   = $metricas->ingresosConsolidadosEntre($mod_1, $fi_1, $ff_1);
    $ingresos_mes_anterior = $metricas->ingresosConsolidadosEntre($mod_2, $fi_2, $ff_2);
    $crecimiento_ingresos = $mes_1 === 'todos' ? 0 : $crecimiento($ingresos_mes_actual, $ingresos_mes_anterior);

    // KPI 4: proyección total y ranking de carreras
    $total_proyectado = $metricas->proyeccionIngresos($mod_1);
    $datos_carrera = $metricas->rankingCarreras($mod_1);

    $carreras_activas = count($datos_carrera);
    $carreras_labels = array_column($datos_carrera, 'carrera');
    $carreras_valores = array_column($datos_carrera, 'cantidad');
    $carrera_top_nombre = 'N/A';
    $carrera_top_porcentaje = 0;
    if ($carreras_activas > 0 && $total_estudiantes > 0) {
        $carrera_top_nombre = $datos_carrera[0]['carrera'];
        $carrera_top_porcentaje = round(($datos_carrera[0]['cantidad'] / $total_estudiantes) * 100, 1);
    }

    // Gráficas: estado de cobros del periodo e ingresos por mes del año
    $datos_estado_pagos = $metricas->cobrosPorEstadoEntre($mod_1, $fi_1, $ff_1);
    $pagos_consolidado = (float) ($datos_estado_pagos['Consolidado'] ?? 0);
    $pagos_revision    = (float) ($datos_estado_pagos['En Revisión'] ?? 0);
    $pagos_rechazado   = (float) ($datos_estado_pagos['Rechazado'] ?? 0);

    $ingresos_anuales_data = $metricas->ingresosMensuales($mod_1, (string) $anio);
} catch (Throwable $e) {
    $error_msg = 'Error al cargar las estadísticas: ' . $e->getMessage();
}

// Helpers visuales de crecimiento
function getGrowthClass($valor) { return $valor >= 0 ? 'text-success' : 'text-danger'; }
function getGrowthIcon($valor)  { return $valor >= 0 ? '▲' : '▼'; }

$label_comparativa = ($mes_1 === 'todos') ? 'Sin comparativa' : "vs $label_mes_2" . ($mod_2 !== 'todos' ? " (Mod $mod_2)" : '');
$titulo_pagina = 'Dashboard Canciller';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .main-content { padding: 20px 30px; }

        .topbar { margin-bottom: 15px; }
        .topbar h1 { font-size: 20px; margin: 0; }
        .topbar p { font-size: 13px; margin: 2px 0 0 0; }

        .filters-container { background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 11px; color: var(--text-dark); text-transform: uppercase; }
        .filter-group select { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 15px; }
        @media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

        .kpi-card { background: white; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; }
        .kpi-title { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-value { font-size: 24px; font-weight: 700; color: var(--text-dark); margin: 5px 0; }
        .kpi-subtitle { font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 8px; margin-top: 5px; align-items: center;}

        .growth-badge { font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; }
        .text-success { color: #059669; background: #d1fae5; }
        .text-danger { color: #dc2626; background: #fee2e2; }

        .charts-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        @media (max-width: 992px) { .charts-container { grid-template-columns: 1fr; } }

        .chart-card { background: white; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .chart-header { font-size: 14px; font-weight: 600; margin-bottom: 5px; color: var(--text-dark); display: flex; justify-content: space-between; align-items: center; }
        .chart-desc { font-size: 11px; color: var(--text-muted); margin-bottom: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
        .canvas-wrapper { position: relative; height: 230px; width: 100%; display: flex; justify-content: center; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Panel de Control Gerencial</h1>
                <p class="text-muted">Métricas y estadísticas en tiempo real (Base <?php echo $anio; ?>).</p>
            </div>
        </header>

        <?php if (isset($error_msg)) mostrar_aviso('error', $error_msg); ?>

        <div class="filters-container">
            <form action="dashboard_admin.php" method="GET" style="display: flex; flex-direction: column; gap: 15px; margin: 0;">

                <!-- Fila 1: periodos y módulos -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                    <div class="filter-group">
                        <label>1. Mes (Análisis)</label>
                        <select name="mes_1">
                            <option value="todos" <?php echo $mes_1 === 'todos' ? 'selected' : ''; ?>>Todos los meses</option>
                            <?php foreach ($meses_nombres as $num => $nombre): ?>
                                <option value="<?php echo $num; ?>" <?php echo (string) $mes_1 === (string) $num ? 'selected' : ''; ?>><?php echo $nombre; ?> <?php echo $anio; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>2. Mes (Comparativa)</label>
                        <select name="mes_2">
                            <option value="todos" <?php echo $mes_2 === 'todos' ? 'selected' : ''; ?>>Ninguno (No Comparar)</option>
                            <?php foreach ($meses_nombres as $num => $nombre): ?>
                                <option value="<?php echo $num; ?>" <?php echo (string) $mes_2 === (string) $num ? 'selected' : ''; ?>><?php echo $nombre; ?> <?php echo $anio; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>3. Módulo (Análisis)</label>
                        <select name="mod_1">
                            <option value="todos" <?php echo $mod_1 === 'todos' ? 'selected' : ''; ?>>Todos los Módulos</option>
                            <?php foreach ($modulos_disponibles as $mod): ?>
                                <option value="<?php echo e($mod); ?>" <?php echo $mod_1 === $mod ? 'selected' : ''; ?>>Módulo <?php echo e($mod); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>4. Módulo (Comparativa)</label>
                        <select name="mod_2">
                            <option value="todos" <?php echo $mod_2 === 'todos' ? 'selected' : ''; ?>>Ninguno (Módulo General)</option>
                            <?php foreach ($modulos_disponibles as $mod): ?>
                                <option value="<?php echo e($mod); ?>" <?php echo $mod_2 === $mod ? 'selected' : ''; ?>>Módulo <?php echo e($mod); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fila 2: botones globales -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <button type="submit" name="ver_todos_meses" value="1" class="btn-secondary" style="border: 1px dashed #3b82f6; color: #3b82f6; justify-content: center;">
                        Ver Dashboard de Todos los Meses
                    </button>
                    <button type="submit" name="ver_todos_modulos" value="1" class="btn-secondary" style="border: 1px dashed #8b5cf6; color: #8b5cf6; justify-content: center;">
                        Ver Dashboard de Todos los Módulos
                    </button>
                </div>

                <!-- Fila 3: acciones -->
                <div style="display: flex; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                    <button type="submit" class="btn-primary" style="padding: 0 30px; height: 38px;">Aplicar Filtros</button>
                    <a href="dashboard_admin.php" class="btn-secondary" style="padding: 0 30px; height: 38px; display:flex; align-items:center; text-decoration:none;">Limpiar</a>
                </div>

            </form>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card" style="border-top: 4px solid #3b82f6;">
                <span class="kpi-title">Estudiantes Totales (Global)</span>
                <span class="kpi-value"><?php echo $total_estudiantes; ?></span>
                <div class="kpi-subtitle">
                    <span>Ord: <b><?php echo $total_ordinario; ?></b></span>
                    <span>Val: <b><?php echo $total_validacion; ?></b></span>
                </div>
            </div>

            <div class="kpi-card" style="border-top: 4px solid #10b981;">
                <span class="kpi-title">Ingresos Nuevos (<?php echo strtoupper($label_mes_1); ?>)</span>
                <span class="kpi-value"><?php echo $nuevos_mes_actual; ?> <span style="font-size: 12px; font-weight:500; color:var(--text-muted);">alumnos</span></span>
                <div class="kpi-subtitle">
                    <span><?php echo $label_comparativa; ?></span>
                    <?php if ($mes_1 !== 'todos'): ?>
                        <span class="growth-badge <?php echo getGrowthClass($crecimiento_est); ?>">
                            <?php echo getGrowthIcon($crecimiento_est); ?> <?php echo abs($crecimiento_est); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kpi-card" style="border-top: 4px solid #8b5cf6;">
                <span class="kpi-title">Ingresos Mensuales (<?php echo strtoupper($label_mes_1); ?>)</span>
                <span class="kpi-value"><?php echo dinero($ingresos_mes_actual); ?></span>
                <div class="kpi-subtitle">
                    <span><?php echo $label_comparativa; ?></span>
                    <?php if ($mes_1 !== 'todos'): ?>
                        <span class="growth-badge <?php echo getGrowthClass($crecimiento_ingresos); ?>">
                            <?php echo getGrowthIcon($crecimiento_ingresos); ?> <?php echo abs($crecimiento_ingresos); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kpi-card" style="border-top: 4px solid #f59e0b;">
                <span class="kpi-title">Proyección de Ingresos</span>
                <span class="kpi-value"><?php echo dinero($total_proyectado); ?></span>
                <div class="kpi-subtitle">
                    <span>Total deuda contratada</span>
                    <span><b><?php echo $carreras_activas; ?></b> Carreras activas</span>
                </div>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-header">Evolución de Ingresos <?php echo $anio; ?></div>
                <div class="chart-desc">Flujo de caja consolidado de Enero a Diciembre (Afectado por Módulo Analizado).</div>
                <div class="canvas-wrapper"><canvas id="ingresosAnualesChart"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    Ranking de Carreras
                    <?php if ($carreras_activas > 0): ?>
                        <span class="growth-badge text-success">Top: <?php echo e($carrera_top_nombre); ?> (<?php echo $carrera_top_porcentaje; ?>%)</span>
                    <?php endif; ?>
                </div>
                <div class="chart-desc">Distribución de los <?php echo $total_estudiantes; ?> estudiantes inscritos.</div>
                <div class="canvas-wrapper"><canvas id="carrerasChart"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">Estado de Cobros (<?php echo $label_mes_1; ?>)</div>
                <div class="chart-desc">Distribución financiera de las transacciones del periodo analizado.</div>
                <div class="canvas-wrapper"><canvas id="pagosChart"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">Distribución por Programa</div>
                <div class="chart-desc">Porcentaje de estudiantes en programa Ordinario vs Validación.</div>
                <div class="canvas-wrapper"><canvas id="programasChart"></canvas></div>
            </div>
        </div>
    </main>

    <script>
        const carrerasLabels = <?php echo json_encode($carreras_labels); ?>;
        const carrerasData = <?php echo json_encode($carreras_valores); ?>;
        const ingresosAnualesData = <?php echo json_encode($ingresos_anuales_data); ?>;
        const totalOrdinario = <?php echo $total_ordinario; ?>;
        const totalValidacion = <?php echo $total_validacion; ?>;
        const pagosConsolidado = <?php echo $pagos_consolidado; ?>;
        const pagosRevision = <?php echo $pagos_revision; ?>;
        const pagosRechazado = <?php echo $pagos_rechazado; ?>;

        const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } };

        new Chart(document.getElementById('ingresosAnualesChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Ingresos USD',
                    data: ingresosAnualesData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('carrerasChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: carrerasLabels,
                datasets: [{ data: carrerasData, backgroundColor: 'rgba(139, 92, 246, 0.8)', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        new Chart(document.getElementById('pagosChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Consolidado', 'En Revisión', 'Rechazado'],
                datasets: [{ data: [pagosConsolidado, pagosRevision, pagosRechazado], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'] }]
            },
            options: commonOptions
        });

        new Chart(document.getElementById('programasChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Ordinario', 'Validación'],
                datasets: [{ data: [totalOrdinario, totalValidacion], backgroundColor: ['#3b82f6', '#8b5cf6'] }]
            },
            options: commonOptions
        });
    </script>
</body>
</html>
