<?php
// exportar_listas.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Exportar Listas'; require __DIR__ . '/includes/partials/head.php'; ?>
    <style>


        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-dark);
            cursor: pointer;
        }
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .sub-section-title {
            margin-top: 25px; 
            color: var(--text-dark); 
            border-bottom: 1px solid #e2e8f0; 
            padding-bottom: 8px;
            font-size: 16px;
        }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h1 class="text-accent">Generador de Reportes a Medida</h1>
            <p class="text-muted">Aplica filtros y selecciona los datos específicos que necesitas exportar a Excel.</p>
        </header>

        <section class="form-container">
            <form action="exportar_excel.php" method="GET" class="neo-form">
                
                <div class="form-card">
                    <h3 class="section-title">1. Aplicar Filtros (Opcional)</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="filter_programa">Programa</label>
                            <select id="filter_programa" name="filter_programa">
                                <option value="">Todos</option>
                                <option value="Ordinario">Ordinario</option>
                                <option value="Validación">Validación</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_modulo">Módulo</label>
                            <select id="filter_modulo" name="filter_modulo">
                                <option value="">Todos</option>
                                <option value="2026-1">2026-1</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_carrera">Carrera</label>
                            <select id="filter_carrera" name="filter_carrera">
                                <option value="">Todas</option>
                                <option value="Comunicación Digital">Comunicación Digital</option>
                                <option value="Seguridad y PRL">Seguridad y PRL</option>
                                <option value="Educación Básica">Educación Básica</option>
                                <option value="Administración">Administración</option>
                                <option value="Locución">Locución</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Modalidad Inglés</label>
                            <select name="filter_ingles">
                                <option value="">Todas</option>
                                <option value="Curso">Curso</option>
                                <option value="Examen">Examen</option>
                                <option value="Homologación">Homologación</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_start">Inscritos Desde</label>
                            <input type="date" id="date_start" name="date_start">
                        </div>
                        <div class="form-group">
                            <label for="date_end">Inscritos Hasta</label>
                            <input type="date" id="date_end" name="date_end">
                        </div>
                        <div class="form-group">
                            <label>Progreso Mínimo Docs. (%)</label>
                            <input type="number" name="filter_min_docs" min="0" max="100" placeholder="Ej. 100">
                        </div>
                        <div class="form-group">
                            <label>Progreso Mínimo Pagos (%)</label>
                            <input type="number" name="filter_min_pagos" min="0" max="100" placeholder="Ej. 50">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="section-title">2. Seleccionar Datos a Exportar</h3>
                    <p class="text-muted" style="font-size: 13px; margin-top: -10px;">Los apellidos y nombres se incluirán obligatoriamente para identificar al estudiante.</p>
                    
                    <h4 class="sub-section-title">Sección Académica</h4>
                    <div class="checkbox-grid">
                        <label class="checkbox-label"><input type="checkbox" name="col_carrera" value="1"> Carrera</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_programa" value="1"> Tipo de Programa</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_modulo" value="1"> Módulo</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_moodle_user" value="1"> Usuario Moodle</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_moodle_pass" value="1"> Contraseña Moodle</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_fecha_inscripcion" value="1"> Fecha de Inscripción</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_tipo_convenio" value="1"> Tipo de Convenio</label>
                    </div>

                    <h4 class="sub-section-title">Sección Estudiante</h4>
                    <div class="checkbox-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" checked disabled> Apellidos y Nombres
                            <input type="hidden" name="col_nombres" value="1">
                        </label>
                        <label class="checkbox-label"><input type="checkbox" name="col_cedula" value="1"> Cédula</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_fecha_nacimiento" value="1"> Fecha de Nacimiento</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_telefono" value="1"> Teléfono</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_correo" value="1"> Correo Electrónico</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_provincia" value="1"> Provincia</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_direccion" value="1"> Dirección / Sector</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_colegio" value="1"> Tipo de Colegio</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_observaciones" value="1"> Observaciones</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_progreso_docs" value="1"> % Documentación</label>
                    </div>

                    <h4 class="sub-section-title">Sección Pagos (USD)</h4>
                    <div class="checkbox-grid">
                        <label class="checkbox-label"><input type="checkbox" name="col_costo_inscripcion" value="1"> Costo Inscripción</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_costo_matricula" value="1"> Costo Matrícula</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_costo_colegiatura" value="1"> Costo Colegiatura</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_costo_ceremonia" value="1"> Costo Ceremonia</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_mod_ingles" value="1"> Modalidad de Inglés</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_costo_ingles" value="1"> Costo Inglés</label>
                        <label class="checkbox-label"><input type="checkbox" name="col_progreso_pagos" value="1"> % Pagos Realizados</label>
                    </div>
                </div>

                <div class="form-actions mt-30">
                    <button type="submit" class="btn-success" style="background-color: var(--success-text); color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; width: 100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> 
                        Descargar Reporte en Excel
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>