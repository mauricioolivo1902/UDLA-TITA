<?php
// visualizar_listas.php
// Directorio de estudiantes para Admisiones y Delegado, con filtros
// combinables resueltos por EstudianteRepository::buscarConFiltros().
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol(['admisiones', 'delegado']);

$filtros = [
    'nombre'   => trim($_GET['search_nombre'] ?? ''),
    'programa' => trim($_GET['filter_programa'] ?? ''),
    'carrera'  => trim($_GET['filter_carrera'] ?? ''),
    'modulo'   => trim($_GET['filter_modulo'] ?? ''),
    'desde'    => trim($_GET['date_start'] ?? ''),
    'hasta'    => trim($_GET['date_end'] ?? ''),
];
$busqueda_activa = isset($_GET['filtrar']);

try {
    $estudiantes = (new EstudianteRepository(Database::conexion()))->buscarConFiltros($filtros);
    $modulos_disponibles = (new ModuloRepository(Database::conexion()))->todos();
} catch (PDOException $e) {
    $error_db = 'Error al cargar la lista: ' . $e->getMessage();
    $estudiantes = [];
    $modulos_disponibles = [];
}

// Contadores del encabezado
$total_estudiantes = count($estudiantes);
$total_ordinario  = count(array_filter($estudiantes, fn ($e) => $e['tipo_programa'] === 'Ordinario'));
$total_validacion = count(array_filter($estudiantes, fn ($e) => $e['tipo_programa'] === 'Validación'));

$carreras = ['Comunicación Digital', 'Seguridad y PRL', 'Educación Básica', 'Administración', 'Locución'];
$titulo_pagina = 'Visualizar Listas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        @media (min-width: 992px) {
            .filters-grid-custom {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }
            .btn-filter-container {
                grid-column: span 2;
                display: flex;
                align-items: flex-end;
            }
        }
        .student-number {
            font-weight: 600;
            color: var(--text-muted);
            font-size: 15px;
            margin-right: 15px;
            min-width: 25px;
            text-align: right;
        }
        .row-info-wrapper { display: flex; align-items: center; }
        .student-data-grid { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
        .col-programa { min-width: 95px; text-align: center; }
        .col-modulo { min-width: 95px; text-align: center; }
        .col-carrera { min-width: 180px; }

        @media (max-width: 768px) {
            .student-data-grid { flex-wrap: wrap; gap: 8px; }
            .col-carrera { min-width: 100%; }
        }
        .empty-state { margin-top: 20px; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Directorio de Estudiantes</h1>
                <p class="text-muted">Filtra, visualiza y gestiona los perfiles de los aspirantes.</p>
            </div>
        </header>

        <?php if (isset($error_db)) mostrar_aviso('error', $error_db); ?>

        <section class="filters-card form-card">
            <form action="visualizar_listas.php" method="GET" class="filters-grid-custom">
                <input type="hidden" name="filtrar" value="1">

                <div class="form-group mb-0">
                    <label for="search_nombre">Buscar por Nombre/Apellido</label>
                    <input type="text" id="search_nombre" name="search_nombre" value="<?php echo e($filtros['nombre']); ?>" placeholder="Ej. Pérez...">
                </div>
                <div class="form-group mb-0">
                    <label for="filter_programa">Programa</label>
                    <select id="filter_programa" name="filter_programa">
                        <option value="">Todos</option>
                        <option value="Ordinario" <?php echo $filtros['programa'] === 'Ordinario' ? 'selected' : ''; ?>>Ordinario</option>
                        <option value="Validación" <?php echo $filtros['programa'] === 'Validación' ? 'selected' : ''; ?>>Validación</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label for="filter_modulo">Módulo</label>
                    <select id="filter_modulo" name="filter_modulo">
                        <option value="">Todos</option>
                        <?php foreach ($modulos_disponibles as $mod): ?>
                            <option value="<?php echo e($mod['nombre']); ?>" <?php echo $filtros['modulo'] === $mod['nombre'] ? 'selected' : ''; ?>><?php echo e($mod['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label for="filter_carrera">Carrera</label>
                    <select id="filter_carrera" name="filter_carrera">
                        <option value="">Todas</option>
                        <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo e($carrera); ?>" <?php echo $filtros['carrera'] === $carrera ? 'selected' : ''; ?>><?php echo e($carrera); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label for="date_start">Desde</label>
                    <input type="date" id="date_start" name="date_start" value="<?php echo e($filtros['desde']); ?>">
                </div>
                <div class="form-group mb-0">
                    <label for="date_end">Hasta</label>
                    <input type="date" id="date_end" name="date_end" value="<?php echo e($filtros['hasta']); ?>">
                </div>
                <div class="form-group mb-0 btn-filter-container">
                    <button type="submit" class="btn-primary" style="width: 100%; height: 42px;">Filtrar</button>
                    <?php if ($busqueda_activa): ?>
                        <a href="visualizar_listas.php" class="btn-secondary" style="margin-left: 10px; height: 42px; display: flex; align-items: center; justify-content: center; padding: 0 20px; text-decoration: none;">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <div class="form-card text-muted" style="padding: 12px 20px; margin-bottom: 20px; font-weight: 500; font-size: 14px; text-align: center;">
            Total: <?php echo $total_estudiantes; ?> &nbsp;|&nbsp; Ordinario: <?php echo $total_ordinario; ?> &nbsp;|&nbsp; Validación: <?php echo $total_validacion; ?>
        </div>

        <?php if (!$busqueda_activa): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8; margin-bottom: 15px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                <h3 style="color: var(--text-dark); font-weight: 600;">Aplica filtros para comenzar</h3>
                <p style="margin-top: 5px;">Utiliza las opciones superiores para visualizar el listado de estudiantes.</p>
            </div>
        <?php else: ?>
            <section class="students-list">
                <?php if (empty($estudiantes)): ?>
                    <div class="form-card text-center" style="padding: 40px; color: var(--text-muted);">
                        <p>No se encontraron estudiantes con los filtros aplicados.</p>
                    </div>
                <?php else: ?>
                    <?php $numero_lista = 1; ?>
                    <?php foreach ($estudiantes as $estudiante): ?>
                        <?php
                        $badge_class = $estudiante['tipo_programa'] === 'Validación' ? 'bg-green-light text-success' : 'bg-yellow-light text-warning';
                        $avatar_class = ($estudiante['id'] % 2 == 0) ? 'bg-purple-light' : 'bg-blue-light';
                        ?>
                        <div class="list-row">
                            <div class="row-info-wrapper">
                                <span class="student-number"><?php echo $numero_lista++; ?>.</span>
                                <div class="row-info">
                                    <div class="avatar <?php echo $avatar_class; ?> text-accent">
                                        <?php echo obtenerIniciales($estudiante['apellidos'], $estudiante['nombres']); ?>
                                    </div>
                                    <div class="details">
                                        <h3 class="student-name-list">
                                            <?php echo e($estudiante['apellidos'] . ' ' . $estudiante['nombres']); ?>
                                        </h3>
                                        <div class="student-data-grid">
                                            <span class="badge-status col-programa <?php echo $badge_class; ?>">
                                                <?php echo e($estudiante['tipo_programa']); ?>
                                            </span>
                                            <span class="badge-status col-modulo" style="background-color: #f1f5f9; color: var(--text-dark); font-weight: 600;">
                                                Módulo <?php echo e($estudiante['modulo'] ?? 'N/A'); ?>
                                            </span>
                                            <span class="badge-status col-carrera bg-ice-blue text-muted">
                                                <?php echo e($estudiante['carrera']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a href="ver_estudiante.php?id=<?php echo $estudiante['id']; ?>" class="btn-action btn-view" title="Ver Perfil">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <span>Ver</span>
                                </a>
                                <a href="editar_estudiante.php?id=<?php echo $estudiante['id']; ?>" class="btn-action btn-edit" title="Editar Información">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
