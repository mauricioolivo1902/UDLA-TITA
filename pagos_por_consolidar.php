<?php
// pagos_por_consolidar.php
// Bandeja de Contabilidad: revisa los pagos subidos por los estudiantes,
// permite ver el comprobante y Aprobarlos (Consolidar) o Rechazarlos con
// motivo. Las acciones las procesa acciones_pago.php.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('contabilidad');

$pagos_repo = new PagoRepository(Database::conexion());

$filtro_estado = $_GET['estado'] ?? 'En Revisión';

try {
    $pagos = $pagos_repo->listarConEstudiante($filtro_estado);
} catch (PDOException $e) {
    $pagos = [];
    $error_db = 'Error al cargar los pagos: ' . $e->getMessage();
}

$titulo_pagina = 'Consolidación de Pagos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .payment-data-grid { display: flex; align-items: center; gap: 15px; margin-top: 8px; flex-wrap: wrap; }
        .col-valor { min-width: 80px; text-align: center; }
        .col-ref { min-width: 250px; font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .col-fecha { min-width: 90px; font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .col-estado { min-width: 110px; text-align: center; }

        @media (max-width: 768px) {
            .col-ref { min-width: 100%; }
        }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Consolidación de Pagos</h1>
                <p class="text-muted">Revisa, aprueba o rechaza los pagos subidos por los estudiantes.</p>
            </div>
        </header>

        <?php require __DIR__ . '/includes/partials/mensajes_flash.php'; ?>
        <?php if (isset($error_db)) mostrar_aviso('error', $error_db); ?>

        <div class="filter-tabs">
            <a href="pagos_por_consolidar.php?estado=En Revisión" class="filter-tab tab-ambar <?php echo $filtro_estado === 'En Revisión' ? 'active' : ''; ?>">Pendientes</a>
            <a href="pagos_por_consolidar.php?estado=Consolidado" class="filter-tab tab-verde <?php echo $filtro_estado === 'Consolidado' ? 'active' : ''; ?>">Aprobados</a>
            <a href="pagos_por_consolidar.php?estado=Rechazado" class="filter-tab tab-rojo <?php echo $filtro_estado === 'Rechazado' ? 'active' : ''; ?>">Rechazados</a>
            <a href="pagos_por_consolidar.php?estado=Todos" class="filter-tab tab-morado <?php echo $filtro_estado === 'Todos' ? 'active' : ''; ?>">Ver Todos</a>
        </div>

        <section class="students-list">
            <?php if (empty($pagos)): ?>
                <div class="form-card text-center" style="padding: 40px; color: var(--text-muted);">
                    <p>No hay pagos en estado: <?php echo e($filtro_estado); ?>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pagos as $pago): ?>
                    <?php
                    $status_class = ($pago['estado'] === 'En Revisión') ? 'estado-pendiente' : (($pago['estado'] === 'Consolidado') ? 'estado-aprobado' : 'estado-negado');
                    $avatar_class = ($pago['estudiante_id'] % 2 == 0) ? 'bg-purple-light' : 'bg-blue-light';
                    ?>
                    <div class="list-row" style="flex-direction: column; padding: 0; overflow: hidden;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; width: 100%; flex-wrap: wrap; gap: 10px;">
                            <div class="row-info-wrapper">
                                <div class="avatar <?php echo $avatar_class; ?> text-accent">
                                    <?php echo obtenerIniciales($pago['apellidos'], $pago['nombres']); ?>
                                </div>
                                <div class="details">
                                    <h3 class="student-name-list"><?php echo e($pago['apellidos'] . ' ' . $pago['nombres']); ?></h3>
                                    <div class="payment-data-grid">
                                        <span class="badge-status col-valor" style="background-color: #e0e7ff; color: #3730a3; font-weight: 700; font-size: 14px;"><?php echo dinero($pago['valor']); ?></span>
                                        <span class="col-ref"><?php echo e($pago['metodo_pago']); ?> | Ref: <?php echo e($pago['num_transaccion']); ?></span>
                                        <span class="col-fecha"><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></span>
                                        <span class="estado-chip col-estado <?php echo $status_class; ?>"><?php echo e($pago['estado']); ?></span>
                                        <?php if ($pago['estado'] === 'Rechazado' && !empty($pago['motivo_rechazo'])): ?>
                                            <span style="color: #b91c1c; font-size: 13px;">Motivo: <?php echo e($pago['motivo_rechazo']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row-actions">
                                <?php if (!empty($pago['archivo_comprobante'])): ?>
                                    <a href="expedientes/<?php echo e($pago['carpeta_fisica']); ?>/PAGOS/<?php echo e($pago['archivo_comprobante']); ?>" target="_blank" class="btn-action btn-view" title="Ver comprobante"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                                <?php endif; ?>
                                <?php if ($pago['estado'] === 'En Revisión'): ?>
                                    <form action="acciones_pago.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                        <input type="hidden" name="accion" value="Consolidado">
                                        <button type="submit" class="btn-action" title="Aprobar" style="background-color: #d1fae5; color: #059669; border:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></button>
                                    </form>
                                    <button type="button" class="btn-action" title="Rechazar" style="background-color: #fee2e2; color: #dc2626; border:none;" onclick="toggleRechazo(<?php echo $pago['id']; ?>)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($pago['estado'] === 'En Revisión'): ?>
                            <div id="rechazo-<?php echo $pago['id']; ?>" class="rechazo-form-container">
                                <form action="acciones_pago.php" method="POST">
                                    <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                    <input type="hidden" name="accion" value="Rechazado">
                                    <label style="display:block; font-weight: 600; font-size: 13px; color: #b91c1c; margin-bottom: 8px;">
                                        Motivo del rechazo * (el estudiante lo verá en su panel)
                                    </label>
                                    <textarea name="motivo_rechazo" rows="2" required placeholder="Ej. El comprobante no es legible o el valor no coincide con la transferencia recibida."></textarea>
                                    <div style="margin-top: 10px;">
                                        <button type="button" class="btn-secondary" style="display: block; width: 100%; margin: 0 0 8px 0;" onclick="toggleRechazo(<?php echo $pago['id']; ?>)">Cancelar</button>
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
