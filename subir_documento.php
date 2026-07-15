<?php
// subir_documento.php
// El estudiante sube sus requisitos en PDF. Cada documento queda "Pendiente"
// hasta que Admisiones lo revise; si fue "Negado" se muestra el motivo y
// puede volver a subirse (el archivo anterior se reemplaza).
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('estudiante');

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$documentos  = new DocumentoRepository($pdo);
$expediente  = new Expediente();

$estudiante_id = (int) $_SESSION['estudiante_id'];
$mensaje_exito = null;
$mensaje_error = null;

$est = $estudiantes->porId($estudiante_id);
if (!$est) {
    die('No se encontró la ficha del estudiante asociada a esta cuenta.');
}

$requisitos = obtenerRequisitosPorPrograma($est['tipo_programa']);

// ==========================================================
// PROCESAR SUBIDA (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo_documento = trim($_POST['tipo_documento'] ?? '');
    $archivo_subido = $_FILES['archivo_documento'] ?? null;
    $doc_actual     = null;

    if (!array_key_exists($tipo_documento, $requisitos)) {
        $mensaje_error = 'Tipo de documento inválido.';
    } elseif (!Expediente::archivoRecibido($archivo_subido)) {
        $mensaje_error = 'No se adjuntó el archivo o superó el límite de peso permitido.';
    } elseif ($expediente->extension($archivo_subido) !== 'pdf') {
        $mensaje_error = 'Solo se permiten archivos en formato PDF.';
    } else {
        // Solo se puede subir si no existe o si fue negado
        $doc_actual = $documentos->porTipo($estudiante_id, $tipo_documento);
        if ($doc_actual && in_array($doc_actual['estado'], ['Pendiente', 'Aprobado'], true)) {
            $mensaje_error = $doc_actual['estado'] === 'Aprobado'
                ? 'Este documento ya fue aprobado, no es necesario volver a subirlo.'
                : 'Este documento está en revisión por Admisiones. Espera el resultado antes de volver a subirlo.';
        }
    }

    if ($mensaje_error === null) {
        $nombre_archivo = $expediente->guardarArchivo(
            $archivo_subido, $est['carpeta_fisica'], Expediente::DOCUMENTOS, 'doc_' . $tipo_documento
        );

        if ($nombre_archivo === null) {
            $mensaje_error = 'Error al guardar el archivo en el servidor.';
        } else {
            try {
                if ($doc_actual) {
                    // Reemplazo de un documento negado: se descarta el archivo anterior
                    $expediente->eliminarArchivo($est['carpeta_fisica'], Expediente::DOCUMENTOS, (string) $doc_actual['archivo']);
                    $documentos->reemplazar((int) $doc_actual['id'], $nombre_archivo);
                } else {
                    $documentos->insertar($estudiante_id, $tipo_documento, $nombre_archivo);
                }
                $mensaje_exito = 'Documento "' . $requisitos[$tipo_documento] . '" subido correctamente. Quedó en revisión por Admisiones.';
            } catch (PDOException $e) {
                $expediente->eliminarArchivo($est['carpeta_fisica'], Expediente::DOCUMENTOS, $nombre_archivo);
                $mensaje_error = 'Error de base de datos al guardar el documento: ' . $e->getMessage();
            }
        }
    }
}

$docs_subidos  = $documentos->porEstudianteIndexados($estudiante_id);
$titulo_pagina = 'Subir Documentos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        /* Columnas fijas: documento | estado | acción. Así todas las filas
           quedan alineadas aunque el texto del estado o del botón varíe. */
        .doc-upload-row {
            display: grid;
            grid-template-columns: 1fr 120px 370px;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            border-bottom: 1px solid #edf2f7;
        }
        .doc-upload-row:last-child { border-bottom: none; }
        .doc-upload-row .estado-chip { width: 100%; text-align: center; }
        .doc-accion { display: flex; justify-content: flex-end; }

        @media (max-width: 992px) {
            .doc-upload-row { grid-template-columns: 1fr; }
            .doc-upload-row .estado-chip { width: auto; justify-self: start; }
            .doc-accion { justify-content: flex-start; }
        }

        .motivo-rechazo { font-size: 12px; color: #b91c1c; margin-top: 4px; background: #fef2f2; border: 1px solid #fecaca; padding: 6px 10px; border-radius: 6px; }

        .upload-form { display: flex; align-items: center; gap: 10px; }
        .upload-form input[type="file"] { font-size: 13px; width: 230px; }
        .btn-subir { background-color: var(--accent-navy, #262f57); color: white; border: none; border-radius: 6px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .btn-subir:hover { opacity: 0.9; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Subir Documentos</h1>
                <p class="text-muted">Sube en PDF cada uno de los requisitos de tu programa (<?php echo e($est['tipo_programa']); ?>). Admisiones los revisará.</p>
            </div>
        </header>

        <?php if ($mensaje_exito) mostrar_aviso('exito', $mensaje_exito); ?>
        <?php if ($mensaje_error) mostrar_aviso('error', $mensaje_error); ?>

        <section class="form-card card-white" style="padding: 0; overflow: hidden;">
            <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <h3 style="font-size: 14px; font-weight: 600; color: var(--text-dark);">
                    Requisitos de tu programa (<?php echo count($requisitos); ?> documentos)
                </h3>
            </div>

            <?php foreach ($requisitos as $key => $label): ?>
                <?php
                $doc = $docs_subidos[$key] ?? null;
                if (!$doc) {
                    $estado_label = 'No subido';   $estado_class = 'estado-nosubido';  $puede_subir = true;
                } elseif ($doc['estado'] === 'Aprobado') {
                    $estado_label = 'Aprobado';    $estado_class = 'estado-aprobado';  $puede_subir = false;
                } elseif ($doc['estado'] === 'Negado') {
                    $estado_label = 'Negado';      $estado_class = 'estado-negado';    $puede_subir = true;
                } else {
                    $estado_label = 'En revisión'; $estado_class = 'estado-pendiente'; $puede_subir = false;
                }
                ?>
                <div class="doc-upload-row">
                    <div>
                        <strong style="font-size: 14px; color: var(--text-dark);"><?php echo $label; ?></strong>
                        <?php if ($doc && $doc['estado'] === 'Negado' && !empty($doc['motivo_rechazo'])): ?>
                            <div class="motivo-rechazo">
                                <strong>Motivo del rechazo:</strong> <?php echo e($doc['motivo_rechazo']); ?>. Corrige el documento y vuelve a subirlo.
                            </div>
                        <?php endif; ?>
                    </div>

                    <span class="estado-chip <?php echo $estado_class; ?>"><?php echo $estado_label; ?></span>

                    <div class="doc-accion">
                        <?php if ($puede_subir): ?>
                            <form action="subir_documento.php" method="POST" enctype="multipart/form-data" class="upload-form">
                                <input type="hidden" name="tipo_documento" value="<?php echo $key; ?>">
                                <input type="file" name="archivo_documento" accept=".pdf" required>
                                <button type="submit" class="btn-subir">
                                    <?php echo ($doc && $doc['estado'] === 'Negado') ? 'Volver a subir' : 'Subir'; ?>
                                </button>
                            </form>
                        <?php elseif ($estado_label === 'En revisión'): ?>
                            <span class="text-muted" style="font-size: 13px;">Esperando revisión de Admisiones</span>
                        <?php else: ?>
                            <span style="font-size: 13px; color: #059669; font-weight: 600;">✓ Documento validado</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
