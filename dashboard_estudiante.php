<?php
// dashboard_estudiante.php
// Panel del estudiante: progreso de documentación y pagos (calculado por
// ProgresoEstudiante) y datos personales en solo lectura.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('estudiante');

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$documentos  = new DocumentoRepository($pdo);
$pagos_repo  = new PagoRepository($pdo);
$progreso    = new ProgresoEstudiante($documentos, $pagos_repo);

$estudiante_id = (int) $_SESSION['estudiante_id'];

$est = $estudiantes->porId($estudiante_id);
if (!$est) {
    die('No se encontró la ficha del estudiante asociada a esta cuenta.');
}

$requisitos     = obtenerRequisitosPorPrograma($est['tipo_programa']);
$docs_subidos   = $documentos->porEstudianteIndexados($estudiante_id);
$avance_docs    = $progreso->progresoDocumentos($est);
$avance_pagos   = $progreso->progresoPagos($est);
$pagos          = $pagos_repo->porEstudiante($estudiante_id);

// Estado visual de cada documento requerido
function estadoDocumento(array $docs_subidos, string $key): array
{
    if (!isset($docs_subidos[$key])) {
        return ['label' => 'No subido', 'class' => 'estado-nosubido'];
    }
    switch ($docs_subidos[$key]['estado']) {
        case 'Aprobado':  return ['label' => 'Aprobado',    'class' => 'estado-aprobado'];
        case 'Negado':    return ['label' => 'Negado',      'class' => 'estado-negado'];
        default:          return ['label' => 'En revisión', 'class' => 'estado-pendiente'];
    }
}

$titulo_pagina = 'Mi Progreso';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .progress-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 992px) { .progress-grid { grid-template-columns: 1fr; } }

        .progress-card { padding: 18px 20px; }
        .progress-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .progress-title h2 { font-size: 15px; }
        .progress-pct { font-size: 18px; font-weight: 700; }
        .progress-bar-track { background: #e2e8f0; border-radius: 10px; height: 9px; overflow: hidden; margin-bottom: 10px; }
        .progress-bar-fill { height: 100%; border-radius: 10px; transition: width 0.4s; }
        .fill-docs { background: #3b82f6; }
        .fill-pagos { background: #10b981; }
        .progress-resumen { font-size: 12px; margin-bottom: 10px; }

        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .estado-chip { font-size: 11px; padding: 2px 10px; }

        .motivo-rechazo { font-size: 12px; color: #b91c1c; margin-top: 3px; }

        /* Botón de acción de las tarjetas: ancho propio y texto centrado */
        .btn-card {
            display: inline-block;
            width: auto;
            text-align: center;
            text-decoration: none;
            padding: 9px 22px;
            font-size: 13px;
            margin-top: 0;
        }

        .datos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        @media (max-width: 992px) { .datos-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 600px) { .datos-grid { grid-template-columns: 1fr; } }
        .dato-item span { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 3px; }
        .dato-item strong { font-size: 14px; color: var(--text-dark); font-weight: 600; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="greeting">
                <h1 class="text-accent">Hola, <?php echo e($est['nombres']); ?></h1>
                <p class="text-muted"><?php echo e($est['carrera']); ?> | Programa <?php echo e($est['tipo_programa']); ?> | Módulo <?php echo e($est['modulo']); ?></p>
            </div>
        </header>

        <div class="progress-grid">

            <!-- TARJETA DOCUMENTACIÓN -->
            <section class="form-card card-white progress-card">
                <div class="progress-title">
                    <h2 class="text-accent">📄 Mi Documentación</h2>
                    <span class="progress-pct" style="color: #3b82f6;"><?php echo $avance_docs['porcentaje']; ?>%</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill fill-docs" style="width: <?php echo $avance_docs['porcentaje']; ?>%;"></div>
                </div>
                <p class="text-muted progress-resumen">
                    <?php echo $avance_docs['aprobados']; ?> de <?php echo $avance_docs['requeridos']; ?> documentos aprobados
                </p>

                <?php foreach ($requisitos as $key => $label): $estado = estadoDocumento($docs_subidos, $key); ?>
                    <div class="detail-row">
                        <div>
                            <?php echo $label; ?>
                            <?php if ($estado['label'] === 'Negado' && !empty($docs_subidos[$key]['motivo_rechazo'])): ?>
                                <div class="motivo-rechazo">Motivo: <?php echo e($docs_subidos[$key]['motivo_rechazo']); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="estado-chip <?php echo $estado['class']; ?>"><?php echo $estado['label']; ?></span>
                    </div>
                <?php endforeach; ?>

                <div style="text-align: right; margin-top: 15px;">
                    <a href="subir_documento.php" class="btn-primary btn-card">Subir documentos</a>
                </div>
            </section>

            <!-- TARJETA PAGOS -->
            <section class="form-card card-white progress-card">
                <div class="progress-title">
                    <h2 class="text-accent">💵 Mis Pagos</h2>
                    <span class="progress-pct" style="color: #10b981;"><?php echo $avance_pagos['porcentaje']; ?>%</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill fill-pagos" style="width: <?php echo $avance_pagos['porcentaje']; ?>%;"></div>
                </div>
                <p class="text-muted progress-resumen">
                    <?php echo dinero($avance_pagos['pagado']); ?> aprobados de <?php echo dinero($avance_pagos['deuda']); ?> del costo total
                </p>

                <?php if (empty($pagos)): ?>
                    <p class="text-muted" style="font-size: 14px; padding: 15px 0;">Aún no has registrado ningún pago.</p>
                <?php else: ?>
                    <?php foreach ($pagos as $p): ?>
                        <?php
                        $chip = $p['estado'] === 'Consolidado' ? 'estado-aprobado' : ($p['estado'] === 'Rechazado' ? 'estado-negado' : 'estado-pendiente');
                        $label_estado = $p['estado'] === 'Consolidado' ? 'Aprobado' : ($p['estado'] === 'Rechazado' ? 'Rechazado' : 'En revisión');
                        ?>
                        <div class="detail-row">
                            <div>
                                <strong style="font-weight: 600;"><?php echo dinero($p['valor']); ?></strong>
                                <span class="text-muted" style="font-size: 12px;"> | <?php echo e($p['metodo_pago']); ?> | Ref: <?php echo e($p['num_transaccion']); ?> | <?php echo date('d/m/Y', strtotime($p['fecha_pago'])); ?></span>
                                <?php if ($p['estado'] === 'Rechazado' && !empty($p['motivo_rechazo'])): ?>
                                    <div class="motivo-rechazo">Motivo: <?php echo e($p['motivo_rechazo']); ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="estado-chip <?php echo $chip; ?>"><?php echo $label_estado; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div style="text-align: right; margin-top: 15px;">
                    <a href="subir_pago.php" class="btn-primary btn-card" style="background-color: #10b981;">Subir un pago</a>
                </div>
            </section>
        </div>

        <!-- DATOS PERSONALES (SOLO LECTURA) -->
        <section class="form-card card-white" style="padding: 25px;">
            <h2 class="text-accent" style="font-size: 18px; margin-bottom: 20px;">👤 Mis Datos Personales</h2>
            <div class="datos-grid">
                <div class="dato-item"><span>Apellidos</span><strong><?php echo e($est['apellidos']); ?></strong></div>
                <div class="dato-item"><span>Nombres</span><strong><?php echo e($est['nombres']); ?></strong></div>
                <div class="dato-item"><span>Cédula</span><strong><?php echo e($est['cedula']); ?></strong></div>
                <div class="dato-item"><span>Fecha de Nacimiento</span><strong><?php echo e($est['fecha_nacimiento']); ?></strong></div>
                <div class="dato-item"><span>Teléfono</span><strong><?php echo e($est['telefono']); ?></strong></div>
                <div class="dato-item"><span>Correo</span><strong><?php echo e($est['correo']); ?></strong></div>
                <div class="dato-item"><span>Provincia</span><strong><?php echo e($est['provincia']); ?></strong></div>
                <div class="dato-item"><span>Dirección</span><strong><?php echo e($est['direccion']); ?></strong></div>
                <div class="dato-item"><span>Tipo de Colegio</span><strong><?php echo e($est['tipo_colegio']); ?></strong></div>
                <div class="dato-item"><span>Carrera</span><strong><?php echo e($est['carrera']); ?></strong></div>
                <div class="dato-item"><span>Tipo de Programa</span><strong><?php echo e($est['tipo_programa']); ?></strong></div>
                <div class="dato-item"><span>Fecha de Inscripción</span><strong><?php echo e($est['fecha_inscripcion']); ?></strong></div>
            </div>
            <p class="text-muted" style="font-size: 12px; margin-top: 20px;">
                Si algún dato es incorrecto, comunícate con la Secretaría de Admisiones para su corrección.
            </p>
        </section>
    </main>
</body>
</html>
