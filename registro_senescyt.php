<?php
// registro_senescyt.php
// Módulo del Delegado para la titulación oficial. Clasifica a los
// estudiantes en tres bandejas:
//   - Incompletos:  aún no cumplen documentación y pagos.
//   - Para Aprobar: cumplen todo (o tienen una titulación a medias, para
//                   que nunca queden en un estado intermedio invisible).
//   - Aprobados:    ya tienen acta y registro Senescyt.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('delegado');

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$progreso    = new ProgresoEstudiante(new DocumentoRepository($pdo), new PagoRepository($pdo));

$tab_activa     = $_GET['tab'] ?? 'Pendientes';
$filtro_carrera = $_GET['f_carrera'] ?? 'Todas';
$filtro_modulo  = $_GET['f_modulo'] ?? 'Todos';

// Los enlaces de pestañas y exportación conservan ambos filtros
$query_filtros = 'f_carrera=' . urlencode($filtro_carrera) . '&f_modulo=' . urlencode($filtro_modulo);

$pendientes   = [];
$para_aprobar = [];
$aprobados    = [];

try {
    $modulos_disponibles = $estudiantes->modulosDisponibles();

    $filtros = [];
    if ($filtro_carrera !== 'Todas') $filtros['carrera'] = $filtro_carrera;
    if ($filtro_modulo !== 'Todos')  $filtros['modulo']  = $filtro_modulo;

    foreach ($estudiantes->buscarConFiltros($filtros) as $est) {
        $acta     = trim((string) ($est['numero_acta'] ?? ''));
        $registro = trim((string) ($est['numero_registro'] ?? ''));

        // Titulado oficialmente: ambos campos con contenido real
        if ($acta !== '' && $registro !== '') {
            $aprobados[] = $est;
            continue;
        }

        $est['status_docs']  = $progreso->progresoDocumentos($est)['completo'];
        $est['status_pagos'] = $progreso->progresoPagos($est)['completo'];

        // Una titulación a medias (solo acta o solo registro) va a "Para
        // Aprobar" aunque falten requisitos, para que pueda completarse.
        $tiene_intento = ($acta !== '' || $registro !== '');

        if (($est['status_docs'] && $est['status_pagos']) || $tiene_intento) {
            $para_aprobar[] = $est;
        } else {
            $pendientes[] = $est;
        }
    }
} catch (PDOException $e) {
    $error_db = 'Error al cargar datos: ' . $e->getMessage();
}

$listas_por_tab = [
    'Pendientes'   => $pendientes,
    'Para Aprobar' => $para_aprobar,
    'Aprobados'    => $aprobados,
];
$lista_mostrar = $listas_por_tab[$tab_activa] ?? [];

$carreras = ['Comunicación Digital', 'Seguridad y PRL', 'Educación Básica', 'Administración', 'Locución'];
$titulo_pagina = 'Registro Senescyt';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .badge-ok { background-color: #d1fae5; color: #059669; }
        .badge-fail { background-color: #fee2e2; color: #dc2626; }
        .badge-titulado { background-color: #ede9fe; color: #6d28d9; font-weight: 700; }

        .student-data-grid {
            display: flex; align-items: center; gap: 15px; margin-top: 8px; font-size: 13px; flex-wrap: wrap;
        }
        .col-ci { min-width: 100px; color: var(--text-muted); font-weight: 500; }
        .col-stat { min-width: 150px; text-align: center; }
        .col-acta { min-width: 140px; text-align: center; }
        .col-reg { min-width: 170px; text-align: center; }
        .col-fecha { min-width: 100px; text-align: center; }
        .col-carrera { color: var(--text-muted); }

        .senescyt-form-container { display: none; background-color: #f8fafc; padding: 20px; border-top: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; }
        .senescyt-form-container.active { display: block; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Registro Senescyt</h1>
                <p class="text-muted">Aprobación final de estudiantes y registro de actas de grado.</p>
            </div>
        </header>

        <?php require __DIR__ . '/includes/partials/mensajes_flash.php'; ?>
        <?php if (isset($error_db)) mostrar_aviso('error', $error_db); ?>

        <section class="form-card card-white" style="margin-bottom: 20px; padding: 15px 20px;">
            <form action="registro_senescyt.php" method="GET" style="display: flex; gap: 15px; align-items: flex-end; margin: 0; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="<?php echo e($tab_activa); ?>">

                <div style="flex: 1; min-width: 220px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; color: var(--text-dark);">Filtrar por Carrera</label>
                    <select name="f_carrera" style="width: 100%; height: 42px; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px;">
                        <option value="Todas">Todas las Carreras</option>
                        <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo e($carrera); ?>" <?php if ($filtro_carrera === $carrera) echo 'selected'; ?>><?php echo e($carrera); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 220px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; color: var(--text-dark);">Filtrar por Módulo</label>
                    <select name="f_modulo" style="width: 100%; height: 42px; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px;">
                        <option value="Todos">Todos los Módulos</option>
                        <?php foreach ($modulos_disponibles as $modulo): ?>
                            <option value="<?php echo e($modulo); ?>" <?php if ($filtro_modulo === $modulo) echo 'selected'; ?>>Módulo <?php echo e($modulo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; height: 42px;">
                    <button type="submit" class="btn-primary" style="height: 100%; margin: 0; padding: 0 24px; width: auto;">Aplicar Filtro</button>

                    <?php if ($tab_activa === 'Aprobados' && !empty($aprobados)): ?>
                        <a href="exportar_titulados.php?<?php echo $query_filtros; ?>" class="btn-success" style="height: 100%; display: inline-flex; align-items: center; justify-content: center; background: #10b981; color: white; padding: 0 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
                            Exportar Titulados
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <div class="filter-tabs">
            <a href="registro_senescyt.php?tab=Pendientes&<?php echo $query_filtros; ?>" class="filter-tab tab-rojo <?php echo $tab_activa === 'Pendientes' ? 'active' : ''; ?>">
                Incompletos (<?php echo count($pendientes); ?>)
            </a>
            <a href="registro_senescyt.php?tab=Para Aprobar&<?php echo $query_filtros; ?>" class="filter-tab tab-ambar <?php echo $tab_activa === 'Para Aprobar' ? 'active' : ''; ?>">
                Para Aprobar (<?php echo count($para_aprobar); ?>)
            </a>
            <a href="registro_senescyt.php?tab=Aprobados&<?php echo $query_filtros; ?>" class="filter-tab tab-verde <?php echo $tab_activa === 'Aprobados' ? 'active' : ''; ?>">
                100% Titulados (<?php echo count($aprobados); ?>)
            </a>
        </div>

        <section class="students-list">
            <?php if (empty($lista_mostrar)): ?>
                <div class="form-card text-center" style="padding: 40px; color: var(--text-muted);">
                    <p>No hay estudiantes en la lista de <?php echo e($tab_activa); ?> para la carrera seleccionada.</p>
                </div>
            <?php else: ?>
                <?php foreach ($lista_mostrar as $est): ?>
                    <?php $avatar_class = ($est['id'] % 2 == 0) ? 'bg-purple-light' : 'bg-blue-light'; ?>

                    <div class="list-row" style="flex-direction: column; padding: 0; overflow: hidden; margin-bottom: 15px;">

                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; width: 100%;">
                            <div class="row-info-wrapper">
                                <div class="avatar <?php echo $avatar_class; ?> text-accent">
                                    <?php echo obtenerIniciales($est['apellidos'], $est['nombres']); ?>
                                </div>
                                <div class="details">
                                    <h3 class="student-name-list">
                                        <?php echo e($est['apellidos'] . ' ' . $est['nombres']); ?>
                                    </h3>

                                    <div class="student-data-grid">
                                        <span class="col-ci">CI: <?php echo e($est['cedula']); ?></span>

                                        <?php if ($tab_activa === 'Aprobados'): ?>
                                            <span class="badge-status col-acta badge-titulado">
                                                Actas: <?php echo e($est['numero_acta']); ?>
                                            </span>
                                            <span class="badge-status col-reg badge-titulado">
                                                Reg: <?php echo e($est['numero_registro']); ?>
                                            </span>
                                            <span class="badge-status col-fecha bg-ice-blue text-muted">
                                                <?php echo !empty($est['fecha_registro_titulo']) ? date('d/m/Y', strtotime($est['fecha_registro_titulo'])) : 'N/A'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status col-stat <?php echo $est['status_docs'] ? 'badge-ok' : 'badge-fail'; ?>">
                                                Docs: <?php echo $est['status_docs'] ? 'COMPLETOS' : 'INCOMPLETOS'; ?>
                                            </span>
                                            <span class="badge-status col-stat <?php echo $est['status_pagos'] ? 'badge-ok' : 'badge-fail'; ?>">
                                                Pagos: <?php echo $est['status_pagos'] ? 'COMPLETOS' : 'INCOMPLETOS'; ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="col-carrera">| <?php echo e($est['carrera']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row-actions">
                                <?php if ($tab_activa === 'Para Aprobar'): ?>
                                    <button type="button" class="btn-action" style="background-color: #f59e0b; color: white; border:none; padding: 8px 16px; width: auto;" onclick="toggleForm(<?php echo $est['id']; ?>)">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px; vertical-align:middle;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Aprobar y Registrar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($tab_activa === 'Para Aprobar'): ?>
                            <div id="form-<?php echo $est['id']; ?>" class="senescyt-form-container">
                                <form action="procesar_senescyt.php" method="POST">
                                    <input type="hidden" name="estudiante_id" value="<?php echo $est['id']; ?>">

                                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                                        <div class="form-group" style="flex: 1; min-width: 200px;">
                                            <label>Número de Acta *</label>
                                            <input type="text" name="numero_acta" required value="<?php echo e($est['numero_acta'] ?? ''); ?>" placeholder="Ej. ACT-2026-001">
                                        </div>
                                        <div class="form-group" style="flex: 1; min-width: 200px;">
                                            <label>Número de Registro Senescyt *</label>
                                            <input type="text" name="numero_registro" required value="<?php echo e($est['numero_registro'] ?? ''); ?>" placeholder="Ej. 1025-2026-123456">
                                        </div>
                                        <div class="form-group" style="flex: 1; min-width: 200px;">
                                            <label>Fecha de Registro *</label>
                                            <?php $fecha_def = !empty($est['fecha_registro_titulo']) ? date('Y-m-d', strtotime($est['fecha_registro_titulo'])) : date('Y-m-d'); ?>
                                            <input type="date" name="fecha_registro_titulo" required value="<?php echo $fecha_def; ?>">
                                        </div>
                                    </div>

                                    <div style="text-align: right;">
                                        <button type="button" class="btn-secondary" style="margin-right: 10px;" onclick="toggleForm(<?php echo $est['id']; ?>)">Cancelar</button>
                                        <button type="submit" class="btn-primary" style="background-color: #10b981; border-color: #10b981; width: auto;">Guardar y Titular Oficialmente</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </section>
    </main>

    <script>
        function toggleForm(id) {
            const formContainer = document.getElementById('form-' + id);
            if (formContainer.classList.contains('active')) {
                formContainer.classList.remove('active');
            } else {
                document.querySelectorAll('.senescyt-form-container').forEach(el => el.classList.remove('active'));
                formContainer.classList.add('active');
            }
        }
    </script>
</body>
</html>
