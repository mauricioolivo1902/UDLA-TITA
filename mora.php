<?php
// mora.php
// Bandeja de MORA para Admisiones: estudiantes sin pagos registrados en el
// último mes. Regla institucional:
//   - Con pagos: entra en mora si su último pago válido tiene 1 mes o más
//     (ej. último pago 15/06/2026 y hoy 15/07/2026 -> aparece en mora).
//   - Sin pagos: entra en mora si su inscripción tiene 1 mes o más.
//   - Un estudiante con la deuda totalmente cubierta nunca aparece,
//     aunque su último pago sea antiguo (ya no debe nada).
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$pagos_repo  = new PagoRepository($pdo);
$progreso    = new ProgresoEstudiante(new DocumentoRepository($pdo), $pagos_repo);

$filtro_modulo  = $_GET['f_modulo'] ?? 'Todos';
$filtro_carrera = $_GET['f_carrera'] ?? 'Todas';

// Un estudiante está en mora si su fecha de referencia (último pago válido,
// o su inscripción si nunca ha pagado) es de hace 1 mes o más.
$fecha_limite = date('Y-m-d', strtotime('-1 month'));

$en_mora = [];

try {
    $modulos_disponibles = (new ModuloRepository($pdo))->todos();

    $filtros = [];
    if ($filtro_carrera !== 'Todas') $filtros['carrera'] = $filtro_carrera;
    if ($filtro_modulo !== 'Todos')  $filtros['modulo']  = $filtro_modulo;

    foreach ($estudiantes->buscarConFiltros($filtros) as $est) {
        $avance_pagos = $progreso->progresoPagos($est);
        if ($avance_pagos['completo']) {
            continue; // deuda cubierta: no hay mora posible
        }

        $ultimo_pago = $pagos_repo->fechaUltimoPago((int) $est['id']);
        $referencia  = $ultimo_pago
                     ?: ($est['fecha_inscripcion'] ?: substr((string) $est['fecha_registro'], 0, 10));

        if ($referencia !== '' && $referencia <= $fecha_limite) {
            $est['ultimo_pago']  = $ultimo_pago;
            $est['dias_en_mora'] = (int) floor((time() - strtotime($referencia)) / 86400);
            $est['saldo']        = $avance_pagos['saldo'];
            $en_mora[] = $est;
        }
    }

    // Los casos más graves primero
    usort($en_mora, fn ($a, $b) => $b['dias_en_mora'] <=> $a['dias_en_mora']);
} catch (PDOException $e) {
    $error_db = 'Error al calcular la mora: ' . $e->getMessage();
    $modulos_disponibles = [];
}

$carreras = ['Comunicación Digital', 'Seguridad y PRL', 'Educación Básica', 'Administración', 'Locución'];
$titulo_pagina = 'Estudiantes en Mora';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .mora-data-grid { display: flex; align-items: center; gap: 12px; margin-top: 8px; font-size: 13px; flex-wrap: wrap; }
        .col-ci { min-width: 105px; color: var(--text-muted); font-weight: 500; }
        .col-carrera-m { min-width: 170px; }
        .col-modulo-m { min-width: 110px; text-align: center; }
        .col-ultimo { min-width: 175px; text-align: center; }
        .badge-dias { background: #fee2e2; color: #dc2626; font-weight: 700; }
        .badge-saldo { background: #fef3c7; color: #d97706; font-weight: 700; }

        .filtros-mora { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin: 0; }
        .filtros-mora .campo { flex: 1; min-width: 200px; }
        .filtros-mora label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; color: var(--text-dark); }
        .filtros-mora select { width: 100%; height: 42px; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; }

        .resumen-mora { background: #fef2f2; border-left: 4px solid #dc2626; color: #b91c1c; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Estudiantes en Mora</h1>
                <p class="text-muted">Estudiantes con deuda pendiente y sin pagos registrados en el último mes.</p>
            </div>
        </header>

        <?php if (isset($error_db)) mostrar_aviso('error', $error_db); ?>

        <section class="form-card card-white" style="padding: 20px; margin-bottom: 20px;">
            <form action="mora.php" method="GET" class="filtros-mora">
                <div class="campo">
                    <label>Filtrar por Módulo</label>
                    <select name="f_modulo">
                        <option value="Todos">Todos los Módulos</option>
                        <?php foreach ($modulos_disponibles as $mod): ?>
                            <option value="<?php echo e($mod['nombre']); ?>" <?php if ($filtro_modulo === $mod['nombre']) echo 'selected'; ?>>Módulo <?php echo e($mod['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label>Filtrar por Carrera</label>
                    <select name="f_carrera">
                        <option value="Todas">Todas las Carreras</option>
                        <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo e($carrera); ?>" <?php if ($filtro_carrera === $carrera) echo 'selected'; ?>><?php echo e($carrera); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; height: 42px;">
                    <button type="submit" class="btn-primary" style="height: 100%; margin: 0; padding: 0 24px; width: auto;">Aplicar Filtros</button>
                    <a href="mora.php" class="btn-secondary" style="height: 100%; display: inline-flex; align-items: center; padding: 0 20px; text-decoration: none;">Limpiar</a>
                </div>
            </form>
        </section>

        <div class="resumen-mora">
            ⚠️ <?php echo count($en_mora); ?> estudiante(s) en mora con los filtros aplicados
        </div>

        <section class="students-list">
            <?php if (empty($en_mora)): ?>
                <div class="form-card text-center" style="padding: 40px; color: var(--text-muted);">
                    <p>🎉 No hay estudiantes en mora para los filtros seleccionados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($en_mora as $est): ?>
                    <?php $avatar_class = ($est['id'] % 2 == 0) ? 'bg-purple-light' : 'bg-blue-light'; ?>
                    <div class="list-row">
                        <div class="row-info-wrapper">
                            <div class="avatar <?php echo $avatar_class; ?> text-accent">
                                <?php echo obtenerIniciales($est['apellidos'], $est['nombres']); ?>
                            </div>
                            <div class="details">
                                <h3 class="student-name-list"><?php echo e($est['apellidos'] . ' ' . $est['nombres']); ?></h3>
                                <div class="mora-data-grid">
                                    <span class="col-ci">CI: <?php echo e($est['cedula']); ?></span>
                                    <span class="badge-status col-carrera-m bg-ice-blue text-muted"><?php echo e($est['carrera']); ?></span>
                                    <span class="badge-status col-modulo-m" style="background:#f1f5f9; color:var(--text-dark); font-weight:600;">Módulo <?php echo e($est['modulo'] ?? 'N/A'); ?></span>
                                    <span class="badge-status col-ultimo <?php echo $est['ultimo_pago'] ? 'bg-ice-blue text-muted' : 'badge-dias'; ?>">
                                        <?php echo $est['ultimo_pago'] ? 'Último pago: ' . date('d/m/Y', strtotime($est['ultimo_pago'])) : 'Sin pagos registrados'; ?>
                                    </span>
                                    <span class="badge-status badge-dias"><?php echo $est['dias_en_mora']; ?> días en mora</span>
                                    <span class="badge-status badge-saldo">Saldo: <?php echo dinero($est['saldo']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row-actions">
                            <a href="ver_estudiante.php?id=<?php echo $est['id']; ?>" class="btn-action btn-view" title="Ver Ficha">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <span>Ver</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
