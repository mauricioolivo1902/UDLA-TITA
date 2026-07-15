<?php
// ver_estudiante.php
// Ficha integral del estudiante para Admisiones y Delegado: estado
// financiero (con historial y comprobantes), documentación y datos
// personales. Los porcentajes los calcula ProgresoEstudiante.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol(['admisiones', 'delegado']);

$estudiante_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$estudiante_id) {
    header('Location: visualizar_listas.php');
    exit;
}

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$documentos  = new DocumentoRepository($pdo);
$pagos_repo  = new PagoRepository($pdo);
$progreso    = new ProgresoEstudiante($documentos, $pagos_repo);

try {
    $est = $estudiantes->porId((int) $estudiante_id);
    if (!$est) {
        header('Location: visualizar_listas.php');
        exit;
    }

    $requisitos   = obtenerRequisitosPorPrograma($est['tipo_programa']);
    $docs_subidos = $documentos->porEstudianteIndexados((int) $estudiante_id);

    $avance_docs  = $progreso->progresoDocumentos($est);
    $avance_pagos = $progreso->progresoPagos($est);

    $tiene_pagos_pendientes = $pagos_repo->tienePendientes((int) $estudiante_id);
    $historial_pagos        = $pagos_repo->porEstudiante((int) $estudiante_id, 'DESC');
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

$pagina_activa = 'visualizar_listas.php';
$titulo_pagina = 'Perfil de Estudiante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        /* AJUSTES DE DISEÑO COMPACTO */
        .main-content { padding: 20px 30px; }

        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        @media (max-width: 992px) { .profile-layout { grid-template-columns: 1fr; } }

        .form-card.compact-card {
            padding: 15px 20px;
            margin-bottom: 0;
        }

        .section-title { font-size: 14px; margin-bottom: 12px; }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12.5px;
            align-items: center;
        }
        .info-item:last-child { border-bottom: none; padding-bottom: 0; }
        .info-item:first-of-type { padding-top: 0; }

        .info-label { font-weight: 500; color: var(--text-muted); }
        .info-value { color: var(--text-dark); font-weight: 600; text-align: right; }

        /* PASTILLAS DE ESTADO */
        .pill-status {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pill-delivered { background-color: #d1fae5; color: #059669; }
        .pill-pending { background-color: #fee2e2; color: #dc2626; }
        .pill-review { background-color: #fef3c7; color: #d97706; }

        /* COLUMNAS FIJAS: enlace al archivo + estado, para que las pastillas
           queden alineadas aunque el texto varíe (ej. "En Revisión" es más ancho) */
        .doc-actions { display: grid; grid-template-columns: 60px 104px; align-items: center; gap: 8px; justify-items: center; }
        .doc-actions .pill-status { width: 100%; }
        .pago-actions { display: grid; grid-template-columns: auto 104px; align-items: center; gap: 10px; }
        .pago-actions .pill-status { width: 100%; }
        .link-archivo { font-size: 10px; font-weight: 700; color: #3b82f6; text-decoration: none; white-space: nowrap; }

        .progress-container { background: #e2e8f0; border-radius: 10px; height: 6px; overflow: hidden; margin-top: 5px; }
        .progress-bar { height: 100%; transition: width 0.3s; }

        /* CABECERA FLEXIBLE */
        .header-profile {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 10px;
        }
        .header-info { display: flex; flex-direction: column; gap: 4px; }
        .header-actions { display: flex; gap: 10px; }

        /* HEADER INTERACTIVO CONTABLE */
        .clickable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .clickable-header:hover { opacity: 0.8; }
        .icon-chevron { transition: transform 0.3s ease; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="header-profile">
            <div class="header-info">
                <a href="javascript:history.back();" class="text-muted" style="text-decoration:none; font-size:12px; font-weight:600; display:inline-block; margin-bottom: 5px;">← Volver al listado</a>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <h1 class="text-accent" style="margin:0; font-size: 22px; line-height: 1;"><?php echo e($est['apellidos'] . ' ' . $est['nombres']); ?></h1>
                    <span class="badge-status <?php echo $est['tipo_programa'] === 'Validación' ? 'bg-green-light text-success' : 'bg-yellow-light text-warning'; ?>" style="font-size:11px; font-weight:700; padding:4px 10px;">
                        Programa: <?php echo e($est['tipo_programa']); ?>
                    </span>
                </div>

                <p class="text-muted" style="margin:0; font-size: 13px;">Ficha Académica e Institucional Integrada</p>
            </div>

            <div class="header-actions">
                <a href="editar_estudiante.php?id=<?php echo $est['id']; ?>" class="btn-primary" style="font-size: 13px; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: 600; width: auto;">
                    Editar Información
                </a>
            </div>
        </header>

        <?php require __DIR__ . '/includes/partials/mensajes_flash.php'; ?>

        <?php if ($tiene_pagos_pendientes): ?>
            <div style="background-color: #fef3c7; color: #d97706; padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; display: flex; align-items: center; gap: 12px; border-left: 4px solid #f59e0b; font-size: 13.5px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <div>
                    <strong>Atención:</strong> Este estudiante tiene pagos "En Revisión" pendientes de conciliación por parte de Contabilidad. El saldo no se actualizará hasta que sean aprobados.
                </div>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            <div style="display: flex; flex-direction: column; gap: 15px;">

                <section class="form-card card-white compact-card">
                    <div class="clickable-header" onclick="togglePagos()">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h3 class="section-title" style="margin:0;">Estado Financiero Contable</h3>
                            <svg id="icon-pagos" class="icon-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                        <span style="font-weight:700; color:var(--text-accent); font-size: 14px;"><?php echo $avance_pagos['porcentaje']; ?>%</span>
                    </div>

                    <div class="progress-container" style="margin-bottom: 15px;">
                        <div class="progress-bar" style="width: <?php echo $avance_pagos['porcentaje']; ?>%; background: #3b82f6;"></div>
                    </div>

                    <div class="info-item"><span class="info-label">Costo Total Contratado</span><span class="info-value"><?php echo dinero($avance_pagos['deuda']); ?></span></div>
                    <div class="info-item"><span class="info-label">Total Abonado (Consolidado)</span><span class="info-value" style="color:#059669;"><?php echo dinero($avance_pagos['pagado']); ?></span></div>

                    <div style="background:#f8fafc; padding:8px 12px; border-radius:6px; margin-top:8px; display: flex; justify-content: space-between; align-items: center;">
                        <span class="info-label" style="color:var(--text-dark);">Saldo Pendiente</span>
                        <span class="info-value" style="color:<?php echo $avance_pagos['saldo'] > 0 ? '#dc2626' : '#059669'; ?>; font-size: 14px;">
                            <?php echo dinero($avance_pagos['saldo']); ?>
                        </span>
                    </div>

                    <div id="historial-pagos" style="display: none; margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 12px;">
                        <h4 style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; text-transform: uppercase; font-weight: 600;">Historial de Transacciones</h4>
                        <?php if (empty($historial_pagos)): ?>
                            <p style="font-size: 12px; color: var(--text-muted);">No existen registros de pago.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php foreach ($historial_pagos as $pago): ?>
                                    <?php
                                    $pill_class = 'pill-review';
                                    if ($pago['estado'] == 'Consolidado') $pill_class = 'pill-delivered';
                                    if ($pago['estado'] == 'Rechazado') $pill_class = 'pill-pending';
                                    ?>
                                    <div style="background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong style="font-size: 13px; color: var(--text-dark); display: block;"><?php echo dinero($pago['valor']); ?> - <?php echo e($pago['metodo_pago']); ?></strong>
                                            <span style="font-size: 11px; color: var(--text-muted);">Ref: <?php echo e($pago['num_transaccion']); ?> | <?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></span>
                                        </div>
                                        <div class="pago-actions">
                                            <?php if (!empty($pago['archivo_comprobante'])): ?>
                                                <a href="expedientes/<?php echo e($est['carpeta_fisica']); ?>/PAGOS/<?php echo e($pago['archivo_comprobante']); ?>" target="_blank" class="link-archivo">VER COMPROBANTE</a>
                                            <?php else: ?>
                                                <span></span>
                                            <?php endif; ?>
                                            <span class="pill-status <?php echo $pill_class; ?>"><?php echo e($pago['estado']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="form-card card-white compact-card">
                    <h3 class="section-title">Información Personal y de Contacto</h3>
                    <div class="info-item"><span class="info-label">Cédula de Identidad</span><span class="info-value"><?php echo e($est['cedula']); ?></span></div>
                    <div class="info-item"><span class="info-label">Fecha de Nacimiento</span><span class="info-value"><?php echo e($est['fecha_nacimiento'] ?? 'N/A'); ?></span></div>
                    <div class="info-item"><span class="info-label">Teléfono Celular</span><span class="info-value"><?php echo e($est['telefono']); ?></span></div>
                    <div class="info-item"><span class="info-label">Correo Electrónico</span><span class="info-value"><?php echo e($est['correo']); ?></span></div>
                    <div class="info-item"><span class="info-label">Provincia</span><span class="info-value"><?php echo e($est['provincia'] ?? 'N/A'); ?></span></div>
                    <div class="info-item"><span class="info-label">Dirección Domiciliaria</span><span class="info-value"><?php echo e($est['direccion'] ?? 'N/A'); ?></span></div>
                    <div class="info-item"><span class="info-label">Tipo de Colegio</span><span class="info-value"><?php echo e($est['tipo_colegio'] ?? 'N/A'); ?></span></div>
                </section>

                <section class="form-card card-white compact-card">
                    <h3 class="section-title">Datos Académicos del Programa</h3>
                    <div class="info-item"><span class="info-label">Carrera Institucional</span><span class="info-value" style="color:var(--text-accent);"><?php echo e($est['carrera']); ?></span></div>
                    <div class="info-item"><span class="info-label">Módulo de Ingreso</span><span class="info-value"><?php echo e($est['modulo'] ?? 'N/A'); ?></span></div>
                    <div class="info-item"><span class="info-label">Usuario Moodle</span><span class="info-value"><?php echo e($est['moodle_user'] ?? 'No Asignado'); ?></span></div>
                    <div class="info-item"><span class="info-label">Contraseña Moodle</span><span class="info-value"><?php echo e($est['moodle_pass'] ?? 'No Asignado'); ?></span></div>
                    <div class="info-item"><span class="info-label">Tipo de Convenio</span><span class="info-value"><?php echo e($est['tipo_convenio'] ?? 'Ninguno'); ?></span></div>
                    <div class="info-item"><span class="info-label">Observaciones</span><span class="info-value"><?php echo e($est['observaciones'] ?? 'Sin observaciones'); ?></span></div>
                </section>

                <?php if (!empty($est['numero_acta'])): ?>
                <section class="form-card card-white compact-card" style="border-left: 4px solid #8b5cf6;">
                    <h3 class="section-title" style="color:#6d28d9;">Estado de Titulación (Senescyt)</h3>
                    <div class="info-item"><span class="info-label">Número de Acta</span><span class="info-value"><?php echo e($est['numero_acta']); ?></span></div>
                    <div class="info-item"><span class="info-label">Número de Registro</span><span class="info-value"><?php echo e($est['numero_registro']); ?></span></div>
                    <div class="info-item"><span class="info-label">Fecha de Registro Oficial</span><span class="info-value"><?php echo date('d/m/Y', strtotime($est['fecha_registro_titulo'])); ?></span></div>
                </section>
                <?php endif; ?>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <section class="form-card card-white compact-card" style="height: 100%;">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:10px;">
                        <h3 class="section-title" style="margin:0;">Ficha de Documentación</h3>
                        <span style="font-weight:700; color:var(--text-accent); font-size: 14px;"><?php echo $avance_docs['porcentaje']; ?>%</span>
                    </div>
                    <div class="progress-container" style="margin-bottom: 15px;">
                        <div class="progress-bar" style="width: <?php echo $avance_docs['porcentaje']; ?>%; background: #10b981;"></div>
                    </div>

                    <div style="display: flex; flex-direction: column;">
                        <?php foreach ($requisitos as $key => $label): ?>
                            <?php
                            $doc = $docs_subidos[$key] ?? null;
                            if (!$doc) {
                                $pill = 'pill-pending';   $texto = 'No subido';
                            } elseif ($doc['estado'] === 'Aprobado') {
                                $pill = 'pill-delivered'; $texto = 'Aprobado';
                            } elseif ($doc['estado'] === 'Negado') {
                                $pill = 'pill-pending';   $texto = 'Negado';
                            } else {
                                $pill = 'pill-review';    $texto = 'En Revisión';
                            }
                            ?>
                            <div class="info-item">
                                <span class="info-label" style="color:var(--text-dark);"><?php echo $label; ?></span>
                                <div class="doc-actions">
                                    <?php if ($doc): ?>
                                        <a href="expedientes/<?php echo e($est['carpeta_fisica']); ?>/DOCUMENTOS/<?php echo e($doc['archivo']); ?>" target="_blank" class="link-archivo">VER PDF</a>
                                    <?php else: ?>
                                        <span></span>
                                    <?php endif; ?>
                                    <span class="pill-status <?php echo $pill; ?>"><?php echo $texto; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        function togglePagos() {
            const container = document.getElementById('historial-pagos');
            const icon = document.getElementById('icon-pagos');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                container.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</body>
</html>
