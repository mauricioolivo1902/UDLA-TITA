<?php
// editar_estudiante.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

$pdo = Database::conexion();

// Los módulos disponibles los administra el Canciller (Gestión de Módulos)
try {
    $modulos_disponibles = (new ModuloRepository($pdo))->todos();
} catch (PDOException $e) {
    $modulos_disponibles = [];
}

// 1. Obtener el ID del estudiante a editar
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    header("Location: visualizar_listas.php");
    exit;
}

// 2. Cargar los datos actuales del estudiante
try {
    $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante) {
        die("Estudiante no encontrado.");
    }
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Editar Estudiante'; require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .main-content { padding-bottom: 40px; }

        /* Ajuste de márgenes para el formulario */
        .form-container {
            padding: 20px;
        }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h1 class="text-accent">Editar Información</h1>
            <p class="text-muted">Actualiza los datos de <strong><?php echo e($estudiante['apellidos'] . ' ' . $estudiante['nombres']); ?></strong></p>
        </header>

        <?php require __DIR__ . '/includes/partials/mensajes_flash.php'; ?>

        <section class="form-container">
            <form action="actualizar_estudiante.php" method="POST" class="neo-form">
                <input type="hidden" name="id" value="<?php echo $estudiante['id']; ?>">

                <div class="form-card">
                    <h3 class="section-title">Datos Académicos</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Carrera *</label>
                            <select name="carrera" required>
                                <option value="Comunicación Digital" <?php echo $estudiante['carrera'] == 'Comunicación Digital' ? 'selected' : ''; ?>>Comunicación Digital</option>
                                <option value="Seguridad y PRL" <?php echo $estudiante['carrera'] == 'Seguridad y PRL' ? 'selected' : ''; ?>>Seguridad y PRL</option>
                                <option value="Educación Básica" <?php echo $estudiante['carrera'] == 'Educación Básica' ? 'selected' : ''; ?>>Educación Básica</option>
                                <option value="Administración" <?php echo $estudiante['carrera'] == 'Administración' ? 'selected' : ''; ?>>Administración</option>
                                <option value="Locución" <?php echo $estudiante['carrera'] == 'Locución' ? 'selected' : ''; ?>>Locución</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Programa *</label>
                            <select name="tipo_programa" required>
                                <option value="Ordinario" <?php echo $estudiante['tipo_programa'] == 'Ordinario' ? 'selected' : ''; ?>>Ordinario</option>
                                <option value="Validación" <?php echo $estudiante['tipo_programa'] == 'Validación' ? 'selected' : ''; ?>>Validación</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Módulo *</label>
                            <select name="modulo" required>
                                <?php foreach ($modulos_disponibles as $mod): ?>
                                    <option value="<?php echo e($mod['nombre']); ?>" <?php echo ($estudiante['modulo'] ?? '') === $mod['nombre'] ? 'selected' : ''; ?>><?php echo e($mod['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Usuario Moodle (Opcional)</label>
                            <input type="text" name="moodle_user" value="<?php echo htmlspecialchars($estudiante['moodle_user'] ?? ''); ?>" placeholder="Ej. jperez">
                        </div>
                        <div class="form-group">
                            <label>Contraseña Moodle (Opcional)</label>
                            <input type="text" name="moodle_pass" value="<?php echo htmlspecialchars($estudiante['moodle_pass'] ?? ''); ?>" placeholder="Ej. Pass123*">
                        </div>
                        <div class="form-group">
                            <label>Fecha de Inscripción *</label>
                            <input type="date" name="fecha_inscripcion" value="<?php echo htmlspecialchars($estudiante['fecha_inscripcion'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Convenio (Opcional)</label>
                            <input type="text" name="tipo_convenio" value="<?php echo htmlspecialchars($estudiante['tipo_convenio'] ?? ''); ?>" placeholder="Ej. Beca deportiva...">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="section-title">Datos del Estudiante</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Apellidos *</label>
                            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($estudiante['apellidos']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nombres *</label>
                            <input type="text" name="nombres" value="<?php echo htmlspecialchars($estudiante['nombres']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Cédula *</label>
                            <input type="text" name="cedula" value="<?php echo htmlspecialchars($estudiante['cedula']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Nacimiento *</label>
                            <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($estudiante['fecha_nacimiento'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono *</label>
                            <input type="text" name="telefono" value="<?php echo htmlspecialchars($estudiante['telefono']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico *</label>
                            <input type="email" name="correo" value="<?php echo htmlspecialchars($estudiante['correo']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Provincia *</label>
                            <input type="text" name="provincia" value="<?php echo htmlspecialchars($estudiante['provincia'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Dirección / Sector</label>
                            <input type="text" name="direccion" value="<?php echo htmlspecialchars($estudiante['direccion']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Colegio *</label>
                            <select name="tipo_colegio" required>
                                <option value="">Seleccione...</option>
                                <option value="Fiscal" <?php echo ($estudiante['tipo_colegio'] ?? '') == 'Fiscal' ? 'selected' : ''; ?>>Fiscal</option>
                                <option value="Fiscomisional" <?php echo ($estudiante['tipo_colegio'] ?? '') == 'Fiscomisional' ? 'selected' : ''; ?>>Fiscomisional</option>
                                <option value="Particular" <?php echo ($estudiante['tipo_colegio'] ?? '') == 'Particular' ? 'selected' : ''; ?>>Particular</option>
                                <option value="Municipal" <?php echo ($estudiante['tipo_colegio'] ?? '') == 'Municipal' ? 'selected' : ''; ?>>Municipal</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group full-width" style="margin-top: 15px;">
                        <label>Observaciones (Opcional)</label>
                        <textarea name="observaciones" rows="3"><?php echo htmlspecialchars($estudiante['observaciones'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="section-title">Sección Pagos (USD)</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Costo Inscripción *</label>
                            <div class="input-prefix"><span>$</span>
                                <input type="number" name="costo_inscripcion" step="0.01" min="0" value="<?php echo htmlspecialchars($estudiante['costo_inscripcion'] ?? '0.00'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Costo Matrícula *</label>
                            <div class="input-prefix"><span>$</span>
                                <input type="number" name="costo_matricula" step="0.01" min="0" value="<?php echo htmlspecialchars($estudiante['costo_matricula'] ?? '0.00'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Costo Colegiatura *</label>
                            <div class="input-prefix"><span>$</span>
                                <input type="number" name="costo_colegiatura" step="0.01" min="0" value="<?php echo htmlspecialchars($estudiante['costo_colegiatura'] ?? '0.00'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Costo Ceremonia *</label>
                            <div class="input-prefix"><span>$</span>
                                <input type="number" name="costo_ceremonia" step="0.01" min="0" value="<?php echo htmlspecialchars($estudiante['costo_ceremonia'] ?? '0.00'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Modalidad de Inglés</label>
                            <select name="modalidad_ingles">
                                <option value="">Seleccione Modalidad...</option>
                                <option value="Curso" <?php echo ($estudiante['modalidad_ingles'] ?? '') == 'Curso' ? 'selected' : ''; ?>>Curso</option>
                                <option value="Examen" <?php echo ($estudiante['modalidad_ingles'] ?? '') == 'Examen' ? 'selected' : ''; ?>>Examen</option>
                                <option value="Homologación" <?php echo ($estudiante['modalidad_ingles'] ?? '') == 'Homologación' ? 'selected' : ''; ?>>Homologación</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Costo Inglés</label>
                            <div class="input-prefix"><span>$</span>
                                <input type="number" name="costo_ingles" step="0.01" min="0" value="<?php echo htmlspecialchars($estudiante['costo_ingles'] ?? '0.00'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions mt-30">
                    <button type="button" class="btn-secondary" onclick="history.back()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>