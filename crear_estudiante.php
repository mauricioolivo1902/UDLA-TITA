<?php
// crear_estudiante.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

// Los módulos disponibles los administra el Canciller (Gestión de Módulos)
try {
    $modulos_disponibles = (new ModuloRepository(Database::conexion()))->todos();
} catch (PDOException $e) {
    $modulos_disponibles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Crear Estudiante'; require __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h1 class="text-accent">Crear Nuevo Estudiante</h1>
            <p class="text-muted">Completa la información para generar el expediente y la carpeta del estudiante.</p>
        </header>

        <?php require __DIR__ . '/includes/partials/mensajes_flash.php'; ?>

        <?php if(isset($_SESSION['credenciales_generadas'])): $cred = $_SESSION['credenciales_generadas']; ?>
            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; padding: 20px 25px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="color: #1d4ed8; font-size: 16px; margin-bottom: 12px;">🔑 Credenciales generadas (Sistema NEO y Moodle)</h3>
                <p style="font-size: 14px; color: #1e40af; margin-bottom: 12px;">
                    Entrega estas credenciales al estudiante <strong><?php echo htmlspecialchars($cred['nombre']); ?></strong>.
                    También quedan guardadas en su ficha.
                </p>
                <div style="display: flex; gap: 30px; flex-wrap: wrap; font-size: 15px;">
                    <div>
                        <span style="color: #64748b; font-size: 13px; display: block;">Usuario Moodle / Sistema</span>
                        <strong style="font-family: monospace; font-size: 17px; color: #0f172a;"><?php echo htmlspecialchars($cred['usuario']); ?></strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 13px; display: block;">Contraseña Moodle / Sistema</span>
                        <strong style="font-family: monospace; font-size: 17px; color: #0f172a;"><?php echo htmlspecialchars($cred['contrasena']); ?></strong>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['credenciales_generadas']); ?>
        <?php endif; ?>

        <section class="form-card card-white">
            <form action="procesar_estudiante.php" method="POST" class="neo-form">
                
                <h2 class="section-title">Sección Académica</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="carrera">Carrera *</label>
                        <select id="carrera" name="carrera" required>
                            <option value="">Seleccione una carrera...</option>
                            <option value="Comunicación Digital">Comunicación Digital</option>
                            <option value="Seguridad y PRL">Seguridad y PRL</option>
                            <option value="Educación Básica">Educación Básica</option>
                            <option value="Administración">Administración</option>
                            <option value="Locución">Locución</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo_programa">Tipo de Programa *</label>
                        <select id="tipo_programa" name="tipo_programa" required>
                            <option value="">Seleccione el programa...</option>
                            <option value="Ordinario">Ordinario</option>
                            <option value="Validación">Validación</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="modulo">Módulo *</label>
                        <select id="modulo" name="modulo" required>
                            <option value="">Seleccione el módulo...</option>
                            <?php foreach ($modulos_disponibles as $mod): ?>
                                <option value="<?php echo e($mod['nombre']); ?>"><?php echo e($mod['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_inscripcion">Fecha de Inscripción *</label>
                        <input type="date" id="fecha_inscripcion" name="fecha_inscripcion" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo_convenio">Tipo de Convenio (Opcional)</label>
                        <input type="text" id="tipo_convenio" name="tipo_convenio" placeholder="Ej. Beca deportiva, Convenio Institucional...">
                    </div>
                </div>

                <hr class="divider">

                <h2 class="section-title">Sección Estudiante</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="apellidos">Apellidos *</label>
                        <input type="text" id="apellidos" name="apellidos" placeholder="Ej. Pérez Gómez" required>
                    </div>
                    <div class="form-group">
                        <label for="nombres">Nombres *</label>
                        <input type="text" id="nombres" name="nombres" placeholder="Ej. Juan Carlos" required>
                    </div>
                    <div class="form-group">
                        <label for="cedula">Cédula *</label>
                        <input type="text" id="cedula" name="cedula" pattern="[0-9]{10}" title="Debe contener exactamente 10 dígitos numéricos" placeholder="10 dígitos" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Número de Celular/Teléfono *</label>
                        <input type="text" id="telefono" name="telefono" pattern="[0-9]{10}" title="Debe contener exactamente 10 dígitos numéricos" placeholder="10 dígitos" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo Electrónico *</label>
                        <input type="email" id="correo" name="correo" placeholder="correo@ejemplo.com" required>
                    </div>
                    <div class="form-group">
                        <label for="provincia">Provincia *</label>
                        <input type="text" id="provincia" name="provincia" required>
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección / Sector *</label>
                        <input type="text" id="direccion" name="direccion" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo_colegio">Tipo de Colegio *</label>
                        <select id="tipo_colegio" name="tipo_colegio" required>
                            <option value="">Seleccione el tipo...</option>
                            <option value="Fiscal">Fiscal</option>
                            <option value="Fiscomisional">Fiscomisional</option>
                            <option value="Particular">Particular</option>
                            <option value="Municipal">Municipal</option>
                        </select>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="observaciones">Observaciones (Opcional)</label>
                    <textarea id="observaciones" name="observaciones" rows="3" placeholder="Añade cualquier detalle relevante aquí..."></textarea>
                </div>

                <hr class="divider">

                <h2 class="section-title">Sección Pagos (USD)</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="costo_inscripcion">Costo Inscripción *</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="costo_inscripcion" name="costo_inscripcion" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="costo_matricula">Costo Matrícula *</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="costo_matricula" name="costo_matricula" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="costo_colegiatura">Costo Colegiatura *</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="costo_colegiatura" name="costo_colegiatura" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="costo_ceremonia">Costo Ceremonia *</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="costo_ceremonia" name="costo_ceremonia" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalidad_ingles" class="text-muted">Modalidad de Inglés (Se llena posteriormente)</label>
                        <select id="modalidad_ingles" name="modalidad_ingles" disabled>
                            <option value="">N/A</option>
                            <option value="Curso">Curso</option>
                            <option value="Examen">Examen</option>
                            <option value="Homologación">Homologación</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="costo_ingles" class="text-muted">Costo Inglés (Se llena posteriormente)</label>
                        <div class="input-prefix disabled-prefix">
                            <span>$</span>
                            <input type="number" id="costo_ingles" name="costo_ingles" placeholder="N/A" disabled>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.location.href='dashboard.php'">Atrás</button>
                    <button type="submit" class="btn-primary">Guardar Estudiante</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>