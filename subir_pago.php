<?php
// subir_pago.php
// El estudiante registra su pago y adjunta el comprobante (PDF o imagen).
// El pago queda "En Revisión" para el departamento Financiero; si un pago
// anterior fue rechazado, aquí se muestra el motivo antes de reintentar.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('estudiante');

const EXTENSIONES_COMPROBANTE = ['pdf', 'jpg', 'jpeg', 'png'];

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$pagos_repo  = new PagoRepository($pdo);
$expediente  = new Expediente();

$estudiante_id = (int) $_SESSION['estudiante_id'];
$mensaje_exito = null;
$mensaje_error = null;

$est = $estudiantes->porId($estudiante_id);
if (!$est) {
    die('No se encontró la ficha del estudiante asociada a esta cuenta.');
}

// ==========================================================
// PROCESAR REGISTRO DE PAGO (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha_pago      = trim($_POST['fecha_pago'] ?? '');
    $valor_registro  = filter_input(INPUT_POST, 'valor_registro', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $metodo_pago     = trim($_POST['metodo_pago'] ?? '');
    $num_transaccion = trim($_POST['num_transaccion'] ?? '');
    $archivo_subido  = $_FILES['comprobante_archivo'] ?? null;

    if ($fecha_pago === '' || !$valor_registro || $valor_registro <= 0 || $metodo_pago === '' || $num_transaccion === '') {
        $mensaje_error = 'Todos los campos son obligatorios y el valor debe ser mayor a cero.';
    } elseif (!Expediente::archivoRecibido($archivo_subido)) {
        $mensaje_error = 'No se adjuntó el comprobante o superó el límite de peso permitido.';
    } elseif (!in_array($expediente->extension($archivo_subido), EXTENSIONES_COMPROBANTE, true)) {
        $mensaje_error = 'Extensión no permitida. Solo PDF, JPG o PNG.';
    } else {
        $nombre_archivo = $expediente->guardarArchivo(
            $archivo_subido, $est['carpeta_fisica'], Expediente::PAGOS, 'comprobante'
        );

        if ($nombre_archivo === null) {
            $mensaje_error = 'Error al guardar el comprobante en el servidor.';
        } else {
            try {
                $pagos_repo->registrar($estudiante_id, $fecha_pago, (float) $valor_registro, $metodo_pago, $num_transaccion, $nombre_archivo);
                $mensaje_exito = 'Pago por ' . dinero($valor_registro) . ' registrado correctamente. Quedó en revisión del departamento Financiero.';
            } catch (PDOException $e) {
                $expediente->eliminarArchivo($est['carpeta_fisica'], Expediente::PAGOS, $nombre_archivo);
                $mensaje_error = 'Error de base de datos al registrar el pago: ' . $e->getMessage();
            }
        }
    }
}

$pagos = $pagos_repo->porEstudiante($estudiante_id, 'DESC');

// Pagos rechazados con motivo: se destacan como alerta al subir uno nuevo
$pagos_rechazados = array_filter($pagos, function ($p) {
    return $p['estado'] === 'Rechazado' && !empty($p['motivo_rechazo']);
});

$titulo_pagina = 'Subir Pago';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .pago-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .pago-row:last-child { border-bottom: none; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Subir Pago</h1>
                <p class="text-muted">Registra tu pago y adjunta el comprobante. Financiero lo revisará y aprobará.</p>
            </div>
        </header>

        <?php if ($mensaje_exito) mostrar_aviso('exito', $mensaje_exito); ?>
        <?php if ($mensaje_error) mostrar_aviso('error', $mensaje_error); ?>

        <?php if (!empty($pagos_rechazados)): ?>
            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #dc2626; color: #b91c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;">
                <strong style="display: block; margin-bottom: 8px;">⚠️ Tienes pago(s) rechazado(s) por el departamento Financiero:</strong>
                <?php foreach ($pagos_rechazados as $pr): ?>
                    <div style="font-size: 13px; padding: 4px 0;">
                        • Pago de <strong><?php echo dinero($pr['valor']); ?></strong> del <?php echo date('d/m/Y', strtotime($pr['fecha_pago'])); ?> (Ref: <?php echo e($pr['num_transaccion']); ?>) —
                        <strong>Motivo:</strong> <?php echo e($pr['motivo_rechazo']); ?>
                    </div>
                <?php endforeach; ?>
                <span style="display: block; margin-top: 8px; font-size: 13px;">Corrige lo indicado y registra un nuevo pago con su comprobante.</span>
            </div>
        <?php endif; ?>

        <section class="form-card card-white" style="padding: 25px; margin-bottom: 20px;">
            <h2 class="section-title" style="margin-bottom: 20px;">Datos del Pago</h2>
            <form action="subir_pago.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha del Pago *</label>
                        <input type="date" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Valor Pagado ($) *</label>
                        <div class="input-prefix"><span>$</span><input type="number" name="valor_registro" step="0.01" min="0.01" required></div>
                    </div>
                    <div class="form-group">
                        <label>Método de Pago *</label>
                        <select name="metodo_pago" required>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Depósito">Depósito</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>N° Comprobante / Transacción *</label>
                        <input type="text" name="num_transaccion" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Subir Comprobante (PDF o Imagen) *</label>
                    <input type="file" name="comprobante_archivo" accept=".pdf, .jpg, .jpeg, .png" required>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="btn-primary">Enviar a Revisión</button>
                </div>
            </form>
        </section>

        <section class="form-card card-white" style="padding: 25px;">
            <h2 class="section-title" style="margin-bottom: 15px;">Historial de mis pagos</h2>
            <?php if (empty($pagos)): ?>
                <p class="text-muted" style="font-size: 14px;">Aún no has registrado ningún pago.</p>
            <?php else: ?>
                <?php foreach ($pagos as $p): ?>
                    <?php
                    $chip = $p['estado'] === 'Consolidado' ? 'estado-aprobado' : ($p['estado'] === 'Rechazado' ? 'estado-negado' : 'estado-pendiente');
                    $label_estado = $p['estado'] === 'Consolidado' ? 'Aprobado' : ($p['estado'] === 'Rechazado' ? 'Rechazado' : 'En revisión');
                    ?>
                    <div class="pago-row">
                        <div>
                            <strong><?php echo dinero($p['valor']); ?></strong>
                            <span class="text-muted" style="font-size: 12px;"> | <?php echo e($p['metodo_pago']); ?> | Ref: <?php echo e($p['num_transaccion']); ?> | <?php echo date('d/m/Y', strtotime($p['fecha_pago'])); ?></span>
                            <?php if ($p['estado'] === 'Rechazado' && !empty($p['motivo_rechazo'])): ?>
                                <div style="font-size: 12px; color: #b91c1c; margin-top: 3px;">Motivo del rechazo: <?php echo e($p['motivo_rechazo']); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="estado-chip <?php echo $chip; ?>"><?php echo $label_estado; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
