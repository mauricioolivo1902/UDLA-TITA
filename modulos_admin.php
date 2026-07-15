<?php
// modulos_admin.php
// CRUD de módulos académicos para el Canciller: crear, listar, renombrar y
// eliminar (con doble confirmación). Un módulo en uso por estudiantes no
// puede eliminarse, para no dejar fichas huérfanas.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admin');

$modulos_repo = new ModuloRepository(Database::conexion());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['modulo_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');

    try {
        if ($accion === 'crear') {
            if ($nombre === '') {
                flash('error', 'El nombre del módulo no puede estar vacío.');
            } elseif ($modulos_repo->nombreExiste($nombre)) {
                flash('error', "El módulo \"$nombre\" ya existe.");
            } else {
                $modulos_repo->crear($nombre);
                flash('exito', "Módulo \"$nombre\" creado correctamente.");
            }
        } elseif ($accion === 'renombrar' && $id) {
            if ($nombre === '') {
                flash('error', 'El nuevo nombre no puede estar vacío.');
            } elseif ($modulos_repo->nombreExiste($nombre)) {
                flash('error', "Ya existe un módulo llamado \"$nombre\".");
            } else {
                $modulos_repo->renombrar($id, $nombre);
                flash('exito', "Módulo renombrado a \"$nombre\". Los estudiantes asignados fueron actualizados.");
            }
        } elseif ($accion === 'eliminar' && $id) {
            $modulo = $modulos_repo->porId($id);
            if (!$modulo) {
                flash('error', 'El módulo no existe.');
            } elseif ($modulos_repo->estudiantesQueLoUsan($modulo['nombre']) > 0) {
                flash('error', "No se puede eliminar el módulo \"{$modulo['nombre']}\" porque tiene estudiantes asignados.");
            } else {
                $modulos_repo->eliminar($id);
                flash('exito', "Módulo \"{$modulo['nombre']}\" eliminado correctamente.");
            }
        }
    } catch (PDOException $e) {
        flash('error', 'Error de base de datos: ' . $e->getMessage());
    }

    header('Location: modulos_admin.php');
    exit;
}

try {
    $modulos = $modulos_repo->todos();
} catch (PDOException $e) {
    $modulos = [];
    $error_db = 'Error al cargar los módulos: ' . $e->getMessage()
              . ' — ¿Ejecutaste el SQL de creación de la tabla "modulos" en phpMyAdmin?';
}

$titulo_pagina = 'Gestión de Módulos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .modulo-row { display: grid; grid-template-columns: 1fr 220px 200px; align-items: center; gap: 15px; padding: 14px 20px; border-bottom: 1px solid #edf2f7; }
        .modulo-row:last-child { border-bottom: none; }
        .modulo-nombre { font-size: 15px; font-weight: 600; color: var(--text-dark); }
        .modulo-uso { font-size: 12px; color: var(--text-muted); }
        .acciones-modulo { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-mini { border: none; border-radius: 6px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-renombrar { background: #e0e7ff; color: #3730a3; }
        .btn-eliminar { background: #fee2e2; color: #dc2626; }

        .form-renombrar { display: none; grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; }
        .form-renombrar.active { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-renombrar input[type="text"] { flex: 1; min-width: 180px; height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }

        .form-crear { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .form-crear input[type="text"] { height: 42px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 250px; }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">

    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Gestión de Módulos</h1>
                <p class="text-muted">Crea, renombra o elimina los módulos académicos disponibles para Admisiones.</p>
            </div>
        </header>

        <?php require __DIR__ . '/includes/partials/mensajes_flash.php'; ?>
        <?php if (isset($error_db)) mostrar_aviso('error', $error_db); ?>

        <!-- CREAR -->
        <section class="form-card card-white" style="padding: 20px; margin-bottom: 20px;">
            <h3 style="font-size: 14px; font-weight: 600; color: var(--text-dark); margin-bottom: 12px;">Crear nuevo módulo</h3>
            <form action="modulos_admin.php" method="POST" class="form-crear">
                <input type="hidden" name="accion" value="crear">
                <input type="text" name="nombre" placeholder="Ej. 2026-2" required maxlength="50">
                <button type="submit" class="btn-primary" style="height: 42px; width: auto; margin: 0; padding: 0 25px;">Crear Módulo</button>
            </form>
        </section>

        <!-- LISTAR -->
        <section class="form-card card-white" style="padding: 0; overflow: hidden;">
            <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <h3 style="font-size: 14px; font-weight: 600; color: var(--text-dark);">Módulos existentes (<?php echo count($modulos); ?>)</h3>
            </div>

            <?php if (empty($modulos)): ?>
                <p class="text-muted" style="padding: 30px; text-align: center;">No hay módulos registrados. Crea el primero con el formulario superior.</p>
            <?php else: ?>
                <?php foreach ($modulos as $mod): ?>
                    <?php $en_uso = $modulos_repo->estudiantesQueLoUsan($mod['nombre']); ?>
                    <div class="modulo-row">
                        <div>
                            <span class="modulo-nombre">📚 Módulo <?php echo e($mod['nombre']); ?></span>
                        </div>
                        <span class="modulo-uso"><?php echo $en_uso; ?> estudiante(s) asignado(s)</span>
                        <div class="acciones-modulo">
                            <button type="button" class="btn-mini btn-renombrar" onclick="toggleRenombrar(<?php echo $mod['id']; ?>)">Modificar Nombre</button>
                            <form action="modulos_admin.php" method="POST" style="display:inline;" onsubmit="return confirmarDobleEliminacion('<?php echo e($mod['nombre']); ?>');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="modulo_id" value="<?php echo $mod['id']; ?>">
                                <button type="submit" class="btn-mini btn-eliminar">Eliminar</button>
                            </form>
                        </div>

                        <div id="renombrar-<?php echo $mod['id']; ?>" class="form-renombrar">
                            <form action="modulos_admin.php" method="POST" style="display: flex; gap: 10px; align-items: center; flex: 1; flex-wrap: wrap; margin: 0;">
                                <input type="hidden" name="accion" value="renombrar">
                                <input type="hidden" name="modulo_id" value="<?php echo $mod['id']; ?>">
                                <label style="font-size: 13px; font-weight: 600; color: var(--text-dark);">Nuevo nombre:</label>
                                <input type="text" name="nombre" value="<?php echo e($mod['nombre']); ?>" required maxlength="50">
                                <button type="submit" class="btn-mini btn-renombrar">Guardar</button>
                                <button type="button" class="btn-mini" style="background:#f1f5f9; color:var(--text-muted);" onclick="toggleRenombrar(<?php echo $mod['id']; ?>)">Cancelar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function toggleRenombrar(id) {
            const form = document.getElementById('renombrar-' + id);
            const estabaActivo = form.classList.contains('active');
            document.querySelectorAll('.form-renombrar').forEach(el => el.classList.remove('active'));
            if (!estabaActivo) { form.classList.add('active'); }
        }

        // Eliminar exige DOS confirmaciones seguidas, según el flujo institucional
        function confirmarDobleEliminacion(nombre) {
            const pregunta = '¿Estás seguro de que deseas borrar el módulo ' + nombre + '?';
            return confirm(pregunta) && confirm(pregunta);
        }
    </script>
</body>
</html>
