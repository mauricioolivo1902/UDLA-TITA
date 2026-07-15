<?php
// reporte_titulacion.php
// Reporte del Delegado: filtra estudiantes por carrera y por estado de
// documentación/pagos para identificar aptos al proceso de grado.
// "Completo" usa las definiciones institucionales de ProgresoEstudiante.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('delegado');

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$progreso    = new ProgresoEstudiante(new DocumentoRepository($pdo), new PagoRepository($pdo));

$filtro_docs    = $_GET['f_docs'] ?? 'Todos';
$filtro_pagos   = $_GET['f_pagos'] ?? 'Todos';
$filtro_carrera = $_GET['f_carrera'] ?? 'Todas';
$busqueda_activa = isset($_GET['filtrar']);

$estudiantes_filtrados = [];

if ($busqueda_activa) {
    try {
        foreach ($estudiantes->porCarrera($filtro_carrera) as $est) {
            $docs_completos  = $progreso->progresoDocumentos($est)['completo'];
            $pagos_completos = $progreso->progresoPagos($est)['completo'];

            $pasa_docs  = ($filtro_docs === 'Todos')  || ($filtro_docs === 'Si' && $docs_completos)  || ($filtro_docs === 'No' && !$docs_completos);
            $pasa_pagos = ($filtro_pagos === 'Todos') || ($filtro_pagos === 'Si' && $pagos_completos) || ($filtro_pagos === 'No' && !$pagos_completos);

            if ($pasa_docs && $pasa_pagos) {
                $est['status_docs']  = $docs_completos;
                $est['status_pagos'] = $pagos_completos;
                $estudiantes_filtrados[] = $est;
            }
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

$carreras = ['Comunicación Digital', 'Seguridad y PRL', 'Educación Básica', 'Administración', 'Locución'];
$titulo_pagina = 'Reporte para Titulación';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr) auto;
            gap: 15px;
            align-items: start;
        }

        .action-group { display: flex; flex-direction: column; }
        .buttons-container { display: flex; gap: 10px; height: 42px; }

        @media (max-width: 992px) {
            .filters-grid { grid-template-columns: 1fr 1fr; }
            .action-group { grid-column: span 2; }
        }
        @media (max-width: 768px) {
            .filters-grid { grid-template-columns: 1fr; }
            .action-group { grid-column: span 1; }
        }

        .badge-ok { background: #d1fae5; color: #059669; }
        .badge-pend { background: #fee2e2; color: #dc2626; }
        .student-data-grid { display: flex; gap: 15px; margin-top: 5px; font-size: 13px; flex-wrap: wrap; }
        .col-stat { min-width: 140px; text-align: center; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Reporte para Titulación</h1>
                <p class="text-muted">Filtra estudiantes aptos para el proceso de grado.</p>
            </div>
        </header>

        <section class="filter-bar form-card">
            <form action="reporte_titulacion.php" method="GET" class="filters-grid">
                <input type="hidden" name="filtrar" value="1">

                <div class="form-group mb-0">
                    <label>Carrera</label>
                    <select name="f_carrera" style="height: 42px;">
                        <option value="Todas">Todas</option>
                        <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo e($carrera); ?>" <?php if ($filtro_carrera === $carrera) echo 'selected'; ?>><?php echo e($carrera); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Documentación Completa</label>
                    <select name="f_docs" style="height: 42px;">
                        <option value="Todos">Todos</option>
                        <option value="Si" <?php if ($filtro_docs === 'Si') echo 'selected'; ?>>Sí</option>
                        <option value="No" <?php if ($filtro_docs === 'No') echo 'selected'; ?>>No</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Pagos Completos</label>
                    <select name="f_pagos" style="height: 42px;">
                        <option value="Todos">Todos</option>
                        <option value="Si" <?php if ($filtro_pagos === 'Si') echo 'selected'; ?>>Sí</option>
                        <option value="No" <?php if ($filtro_pagos === 'No') echo 'selected'; ?>>No</option>
                    </select>
                </div>

                <div class="form-group mb-0 action-group">
                    <label style="visibility: hidden;">Acciones</label>
                    <div class="buttons-container">
                        <button type="submit" class="btn-primary" style="margin: 0; padding: 0 20px; white-space: nowrap; height: 100%; width: auto;">
                            Filtrar Lista
                        </button>

                        <?php if ($busqueda_activa && !empty($estudiantes_filtrados)): ?>
                            <a href="exportar_titulacion.php?<?php echo http_build_query($_GET); ?>" class="btn-success" style="margin: 0; background: #10b981; color: white; padding: 0 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; height: 100%;">
                                Exportar Excel
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </section>

        <section class="students-list">
            <?php if (!$busqueda_activa): ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8; margin-bottom: 15px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    <h3 style="color: var(--text-dark); font-weight: 600;">Aplica filtros para comenzar</h3>
                    <p style="margin-top: 5px;">Selecciona la Carrera, Estado de Documentación y Pagos para visualizar resultados.</p>
                </div>
            <?php elseif (empty($estudiantes_filtrados)): ?>
                <div class="empty-state">
                    <p>No se encontraron estudiantes con los filtros aplicados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($estudiantes_filtrados as $est): ?>
                <div class="list-row">
                    <div class="row-info-wrapper">
                        <div class="avatar bg-blue-light text-accent"><?php echo obtenerIniciales($est['apellidos'], $est['nombres']); ?></div>
                        <div class="details">
                            <h3 class="student-name-list"><?php echo e($est['apellidos'] . ' ' . $est['nombres']); ?></h3>
                            <div class="student-data-grid">
                                <span class="badge-status col-stat <?php echo $est['status_docs'] ? 'badge-ok' : 'badge-pend'; ?>">
                                    Docs: <?php echo $est['status_docs'] ? 'COMPLETO' : 'PENDIENTE'; ?>
                                </span>
                                <span class="badge-status col-stat <?php echo $est['status_pagos'] ? 'badge-ok' : 'badge-pend'; ?>">
                                    Pagos: <?php echo $est['status_pagos'] ? 'COMPLETO' : 'PENDIENTE'; ?>
                                </span>
                                <span class="badge-status bg-ice-blue text-muted">CI: <?php echo e($est['cedula']); ?></span>
                                <span class="badge-status bg-ice-blue text-muted"><?php echo e($est['carrera']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
