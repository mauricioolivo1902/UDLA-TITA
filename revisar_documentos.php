<?php
// revisar_documentos.php
// Bandeja de revisión documental de Admisiones: lista los documentos
// subidos por los estudiantes, permite ver el PDF y Aprobarlo o Negarlo.
// Negar exige un motivo, que el estudiante verá en su panel.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

$documentos = new DocumentoRepository(Database::conexion());

// Etiquetas de todos los tipos de documento (Validación es el conjunto completo)
$etiquetas_docs = obtenerRequisitosPorPrograma('Validación');

$mensaje_exito = null;
$mensaje_error = null;

// ==========================================================
// PROCESAR ACCIÓN (POST): Aprobar o Negar un documento
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $documento_id = filter_input(INPUT_POST, 'documento_id', FILTER_SANITIZE_NUMBER_INT);
    $accion = trim($_POST['accion'] ?? '');
    $motivo = trim($_POST['motivo_rechazo'] ?? '');

    if (!$documento_id || !in_array($accion, ['Aprobado', 'Negado'], true)) {
        $mensaje_error = 'Acción inválida.';
    } elseif ($accion === 'Negado' && $motivo === '') {
        $mensaje_error = 'Para negar un documento debes indicar el motivo del rechazo.';
    } else {
        try {
            $revisado = $documentos->revisar((int) $documento_id, $accion, $accion === 'Negado' ? $motivo : null);

            if ($revisado) {
                $mensaje_exito = $accion === 'Aprobado'
                    ? 'Documento aprobado correctamente.'
                    : 'Documento negado. El estudiante verá el motivo en su panel y podrá volver a subirlo.';
            } else {
                $mensaje_error = 'El documento ya fue revisado o no existe.';
            }
        } catch (PDOException $e) {
            $mensaje_error = 'Error al procesar la revisión: ' . $e->getMessage();
        }
    }
}

// ==========================================================
// CARGAR LISTADO SEGÚN FILTROS
// ==========================================================
$filtro_estado = $_GET['estado'] ?? 'Pendiente';
$busqueda = trim($_GET['buscar'] ?? '');

try {
    $lista_documentos = $documentos->listarConEstudiante($filtro_estado, $busqueda);
    $total_pendientes = $documentos->contarPendientes();
} catch (PDOException $e) {
    $lista_documentos = [];
    $total_pendientes = 0;
    $mensaje_error = 'Error al cargar los documentos: ' . $e->getMessage();
}

$titulo_pagina = 'Revisar Documentos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .doc-data-grid { display: flex; align-items: center; gap: 15px; margin-top: 8px; font-size: 13px; color: var(--text-muted); flex-wrap: wrap; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Revisión de Documentos</h1>
                <p class="text-muted">Revisa los documentos subidos por los estudiantes y apruébalos o niégalos.</p>
            </div>
        </header>

        <?php if ($mensaje_exito) mostrar_aviso('exito', $mensaje_exito); ?>
        <?php if ($mensaje_error) mostrar_aviso('error', $mensaje_error); ?>

        <section class="form-card card-white" style="padding: 20px; margin-bottom: 20px;">
            <form action="revisar_documentos.php" method="GET" class="search-box-custom">
                <input type="hidden" name="estado" value="<?php echo e($filtro_estado); ?>">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="buscar" style="font-weight: 600;">Buscar por Nombre, Apellido o Cédula (opcional)</label>
                    <input type="text" id="buscar" name="buscar" value="<?php echo e($busqueda); ?>" placeholder="Deja vacío para ver todos" style="height: 42px;">
                </div>
                <button type="submit" class="btn-primary" style="height: 42px; margin: 0; padding: 0 25px;">Buscar</button>
            </form>
        </section>

        <div class="filter-tabs">
            <a href="revisar_documentos.php?estado=Pendiente&buscar=<?php echo urlencode($busqueda); ?>" class="filter-tab tab-ambar <?php echo $filtro_estado === 'Pendiente' ? 'active' : ''; ?>">Pendientes (<?php echo $total_pendientes; ?>)</a>
            <a href="revisar_documentos.php?estado=Aprobado&buscar=<?php echo urlencode($busqueda); ?>" class="filter-tab tab-verde <?php echo $filtro_estado === 'Aprobado' ? 'active' : ''; ?>">Aprobados</a>
            <a href="revisar_documentos.php?estado=Negado&buscar=<?php echo urlencode($busqueda); ?>" class="filter-tab tab-rojo <?php echo $filtro_estado === 'Negado' ? 'active' : ''; ?>">Negados</a>
            <a href="revisar_documentos.php?estado=Todos&buscar=<?php echo urlencode($busqueda); ?>" class="filter-tab tab-morado <?php echo $filtro_estado === 'Todos' ? 'active' : ''; ?>">Ver Todos</a>
        </div>

        <section class="students-list">
            <?php if (empty($lista_documentos)): ?>
                <div class="form-card text-center" style="padding: 40px; color: var(--text-muted);">
                    <p>No hay documentos en estado: <?php echo e($filtro_estado); ?>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($lista_documentos as $doc): ?>
                    <?php
                    $chip_class = $doc['estado'] === 'Aprobado' ? 'estado-aprobado' : ($doc['estado'] === 'Negado' ? 'estado-negado' : 'estado-pendiente');
                    $avatar_class = ($doc['estudiante_id'] % 2 == 0) ? 'bg-purple-light' : 'bg-blue-light';
                    $etiqueta_doc = $etiquetas_docs[$doc['tipo_documento']] ?? $doc['tipo_documento'];
                    ?>
                    <div class="list-row" style="flex-direction: column; padding: 0; overflow: hidden; margin-bottom: 15px;">

                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; width: 100%; flex-wrap: wrap; gap: 10px;">
                            <div class="row-info-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                <div class="avatar <?php echo $avatar_class; ?> text-accent">
                                    <?php echo obtenerIniciales($doc['apellidos'], $doc['nombres']); ?>
                                </div>
                                <div class="details">
                                    <h3 class="student-name-list"><?php echo e($doc['apellidos'] . ' ' . $doc['nombres']); ?></h3>
                                    <div class="doc-data-grid">
                                        <strong style="color: var(--text-dark);">📄 <?php echo e($etiqueta_doc); ?></strong>
                                        <span>CI: <?php echo e($doc['cedula']); ?></span>
                                        <span>Subido: <?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></span>
                                        <span class="estado-chip <?php echo $chip_class; ?>"><?php echo e($doc['estado']); ?></span>
                                        <?php if ($doc['estado'] === 'Negado' && !empty($doc['motivo_rechazo'])): ?>
                                            <span style="color: #b91c1c;">Motivo: <?php echo e($doc['motivo_rechazo']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row-actions" style="display: flex; gap: 8px;">
                                <a href="expedientes/<?php echo e($doc['carpeta_fisica']); ?>/DOCUMENTOS/<?php echo e($doc['archivo']); ?>" target="_blank" class="btn-action btn-view" title="Ver PDF">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                                <?php if ($doc['estado'] === 'Pendiente'): ?>
                                    <form action="revisar_documentos.php?estado=<?php echo urlencode($filtro_estado); ?>&buscar=<?php echo urlencode($busqueda); ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="documento_id" value="<?php echo $doc['id']; ?>">
                                        <input type="hidden" name="accion" value="Aprobado">
                                        <button type="submit" class="btn-action" title="Aprobar" style="background-color: #d1fae5; color: #059669; border:none;">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </button>
                                    </form>
                                    <button type="button" class="btn-action" title="Negar" style="background-color: #fee2e2; color: #dc2626; border:none;" onclick="toggleRechazo(<?php echo $doc['id']; ?>)">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($doc['estado'] === 'Pendiente'): ?>
                            <div id="rechazo-<?php echo $doc['id']; ?>" class="rechazo-form-container">
                                <form action="revisar_documentos.php?estado=<?php echo urlencode($filtro_estado); ?>&buscar=<?php echo urlencode($busqueda); ?>" method="POST">
                                    <input type="hidden" name="documento_id" value="<?php echo $doc['id']; ?>">
                                    <input type="hidden" name="accion" value="Negado">
                                    <label style="display:block; font-weight: 600; font-size: 13px; color: #b91c1c; margin-bottom: 8px;">
                                        Motivo del rechazo * (el estudiante lo verá en su panel)
                                    </label>
                                    <textarea name="motivo_rechazo" rows="2" required placeholder="Ej. El documento no es legible, vuelva a escanearlo en mejor calidad."></textarea>
                                    <div style="margin-top: 10px;">
                                        <button type="button" class="btn-secondary" style="display: block; width: 100%; margin: 0 0 8px 0;" onclick="toggleRechazo(<?php echo $doc['id']; ?>)">Cancelar</button>
                                        <button type="submit" class="btn-primary" style="background-color: #dc2626; border-color: #dc2626; margin-top: 0;">Confirmar Rechazo</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script src="js/rechazo.js"></script>
</body>
</html>
