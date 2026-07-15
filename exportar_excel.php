<?php
// exportar_excel.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

// Limpieza de buffer: vital para evitar que espacios invisibles corrompan el Excel
if (ob_get_length()) {
    ob_clean();
}

$pdo = Database::conexion();
require_once 'includes/requisitos.php';

// Configurar cabeceras nativas para descarga de Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_estudiantes_" . date('Y_m_d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Renderizar BOM para Excel UTF-8
echo "\xEF\xBB\xBF";

// 2. ESTRUCTURA HTML BASE: Ayuda a Excel a interpretar la tabla correctamente y evitar el documento en blanco
echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
echo "<head><meta charset=\"utf-8\"></head>";
echo "<body>";

// 3. Recibir filtros generales
$search_nombre = trim($_GET['search_nombre'] ?? '');
$filter_programa = trim($_GET['filter_programa'] ?? '');
$filter_modulo = trim($_GET['filter_modulo'] ?? '');
$filter_carrera = trim($_GET['filter_carrera'] ?? '');
$filter_ingles = trim($_GET['filter_ingles'] ?? '');
$date_start = trim($_GET['date_start'] ?? '');
$date_end = trim($_GET['date_end'] ?? '');

// Filtros de Progreso
$filter_min_docs = trim($_GET['filter_min_docs'] ?? '');
$filter_min_pagos = trim($_GET['filter_min_pagos'] ?? '');

// 4. Detectar si viene del Generador a Medida o de Visualizar Listas Clásico
$es_reporte_medida = isset($_GET['col_nombres']) || isset($_GET['col_cedula']) || isset($_GET['col_carrera']);

if ($es_reporte_medida) {
    // Tomar TODAS las columnas que el usuario marcó
    // Académica
    $col_carrera = isset($_GET['col_carrera']);
    $col_programa = isset($_GET['col_programa']);
    $col_modulo = isset($_GET['col_modulo']);
    $col_moodle_user = isset($_GET['col_moodle_user']);
    $col_moodle_pass = isset($_GET['col_moodle_pass']);
    $col_fecha_inscripcion = isset($_GET['col_fecha_inscripcion']);
    $col_tipo_convenio = isset($_GET['col_tipo_convenio']);
    // Estudiante
    $col_cedula = isset($_GET['col_cedula']);
    $col_fecha_nacimiento = isset($_GET['col_fecha_nacimiento']);
    $col_telefono = isset($_GET['col_telefono']);
    $col_correo = isset($_GET['col_correo']);
    $col_provincia = isset($_GET['col_provincia']);
    $col_direccion = isset($_GET['col_direccion']);
    $col_colegio = isset($_GET['col_colegio']);
    $col_observaciones = isset($_GET['col_observaciones']);
    $col_progreso_docs = isset($_GET['col_progreso_docs']);
    // Pagos
    $col_costo_inscripcion = isset($_GET['col_costo_inscripcion']);
    $col_costo_matricula = isset($_GET['col_costo_matricula']);
    $col_costo_colegiatura = isset($_GET['col_costo_colegiatura']);
    $col_costo_ceremonia = isset($_GET['col_costo_ceremonia']);
    $col_mod_ingles = isset($_GET['col_mod_ingles']);
    $col_costo_ingles = isset($_GET['col_costo_ingles']);
    $col_progreso_pagos = isset($_GET['col_progreso_pagos']);
} else {
    // Comportamiento Clásico
    $col_carrera = true; $col_programa = true; $col_modulo = false; $col_moodle_user = false; 
    $col_moodle_pass = false; $col_fecha_inscripcion = false; $col_tipo_convenio = false;
    $col_cedula = true; $col_fecha_nacimiento = false; $col_telefono = false; $col_correo = false; 
    $col_provincia = false; $col_direccion = false; $col_colegio = false; $col_observaciones = false; 
    $col_progreso_docs = true;
    $col_costo_inscripcion = false; $col_costo_matricula = false; $col_costo_colegiatura = false; 
    $col_costo_ceremonia = false; $col_mod_ingles = false; $col_costo_ingles = false; 
    $col_progreso_pagos = true;
}

// 5. Recrear consulta con filtros directos de BD
$sql = "SELECT * FROM estudiantes WHERE 1=1";
$params = [];

if ($search_nombre !== '') {
    $sql .= " AND (nombres LIKE :search OR apellidos LIKE :search)";
    $params[':search'] = '%' . $search_nombre . '%';
}
if ($filter_programa !== '') {
    $sql .= " AND tipo_programa = :programa";
    $params[':programa'] = $filter_programa;
}
if ($filter_modulo !== '') {
    $sql .= " AND modulo = :modulo";
    $params[':modulo'] = $filter_modulo;
}
if ($filter_carrera !== '') {
    $sql .= " AND carrera = :carrera";
    $params[':carrera'] = $filter_carrera;
}
if ($filter_ingles !== '') {
    $sql .= " AND modalidad_ingles = :ingles";
    $params[':ingles'] = $filter_ingles;
}
if ($date_start !== '') {
    $sql .= " AND fecha_inscripcion >= :start";
    $params[':start'] = $date_start;
}
if ($date_end !== '') {
    $sql .= " AND fecha_inscripcion <= :end";
    $params[':end'] = $date_end;
}
// ORDENAMIENTO ALFABÉTICO REQUERIDO
$sql .= " ORDER BY apellidos ASC, nombres ASC"; 

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al extraer info de la DB para Excel: " . $e->getMessage());
}

// 6. Preparar cálculos requeridos para filtros o columnas
$requiere_docs = $col_progreso_docs || $filter_min_docs !== '';
$requiere_pagos = $col_progreso_pagos || $filter_min_pagos !== '';

// LÓGICA ACTUALIZADA: Solo sumar pagos que estén en estado "Consolidado"
if ($requiere_pagos) {
    $stmt_pagos = $pdo->prepare("SELECT SUM(valor) as total_pagado FROM pagos WHERE estudiante_id = :id AND estado = 'Consolidado'");
}

// LÓGICA NUEVA: los documentos que cuentan son los APROBADOS por Admisiones
if ($requiere_docs) {
    $stmt_docs = $pdo->prepare("SELECT COUNT(*) FROM documentos WHERE estudiante_id = :id AND estado = 'Aprobado'");
}

// ================= RENDERIZADO DE LA TABLA =================

// Calcular cantidad de columnas
$num_columnas = 2 + $col_carrera + $col_programa + $col_modulo + $col_moodle_user + $col_moodle_pass + 
                $col_fecha_inscripcion + $col_tipo_convenio + $col_cedula + $col_fecha_nacimiento + 
                $col_telefono + $col_correo + $col_provincia + $col_direccion + $col_colegio + 
                $col_observaciones + $col_progreso_docs + $col_costo_inscripcion + $col_costo_matricula + 
                $col_costo_colegiatura + $col_costo_ceremonia + $col_mod_ingles + $col_costo_ingles + $col_progreso_pagos;

echo "<table border='1' cellpadding='4' cellspacing='0' style='font-family: Arial, sans-serif;'>";

// Filas de Cabecera
echo "<tr><th colspan='{$num_columnas}' style='background-color:#262f57; color:#ffffff; font-size:16px; font-weight:bold; height:40px;'>Reporte Personalizado de Estudiantes - Instituto Tecnológico RTV</th></tr>";

// Resumen de los filtros
$filtros_texto = [];
if($search_nombre) $filtros_texto[] = "Búsqueda: '{$search_nombre}'";
if($filter_programa) $filtros_texto[] = "Programa: {$filter_programa}";
if($filter_modulo) $filtros_texto[] = "Módulo: {$filter_modulo}";
if($filter_carrera) $filtros_texto[] = "Carrera: {$filter_carrera}";
if($filter_ingles) $filtros_texto[] = "Mod. Inglés: {$filter_ingles}";
if($date_start || $date_end) $filtros_texto[] = "Fechas: " . ($date_start ?: "Inicio") . " a " . ($date_end ?: "Hoy");
if($filter_min_docs !== '') $filtros_texto[] = "Docs Mínimo: {$filter_min_docs}%";
if($filter_min_pagos !== '') $filtros_texto[] = "Pagos Mínimo: {$filter_min_pagos}%";

$filtros_mostrar = empty($filtros_texto) ? "Ninguno (Lista Completa)" : implode(" | ", $filtros_texto);
echo "<tr><th colspan='{$num_columnas}' style='background-color:#e0e7ff; text-align:left; height:30px; font-size:12px;'><strong>Filtros aplicados:</strong> {$filtros_mostrar}</th></tr>";

// Títulos de columnas dinámicos
echo "<tr style='background-color:#c7d2fe; font-weight:bold;'>";
// Fijos
echo "<th>Apellidos</th><th>Nombres</th>";
// Opcionales Académicos
if ($col_carrera) echo "<th>Carrera</th>";
if ($col_programa) echo "<th>Programa</th>";
if ($col_modulo) echo "<th>Módulo</th>";
if ($col_moodle_user) echo "<th>Usuario Moodle</th>";
if ($col_moodle_pass) echo "<th>Contraseña Moodle</th>";
if ($col_fecha_inscripcion) echo "<th>Fecha Inscripción</th>";
if ($col_tipo_convenio) echo "<th>Convenio</th>";
// Opcionales Estudiante
if ($col_cedula) echo "<th>Cédula</th>";
if ($col_fecha_nacimiento) echo "<th>Fecha Nacimiento</th>";
if ($col_telefono) echo "<th>Teléfono</th>";
if ($col_correo) echo "<th>Correo Electrónico</th>";
if ($col_provincia) echo "<th>Provincia</th>";
if ($col_direccion) echo "<th>Dirección</th>";
if ($col_colegio) echo "<th>Tipo Colegio</th>";
if ($col_observaciones) echo "<th>Observaciones</th>";
if ($col_progreso_docs) echo "<th>Progreso Docs. (%)</th>";
// Opcionales Pagos
if ($col_costo_inscripcion) echo "<th>Costo Inscripción</th>";
if ($col_costo_matricula) echo "<th>Costo Matrícula</th>";
if ($col_costo_colegiatura) echo "<th>Costo Colegiatura</th>";
if ($col_costo_ceremonia) echo "<th>Costo Ceremonia</th>";
if ($col_mod_ingles) echo "<th>Modalidad Inglés</th>";
if ($col_costo_ingles) echo "<th>Costo Inglés</th>";
if ($col_progreso_pagos) echo "<th>Progreso Pagos (%)</th>";
echo "</tr>";

// Generar filas de datos dinámicos
foreach ($estudiantes as $est) {
    
    // --- EVALUAR PROGRESOS PARA FILTRAR ---
    $porcentaje_docs = 0;
    if ($requiere_docs) {
        $total_docs_requeridos = totalRequisitosPorPrograma($est['tipo_programa']);

        $stmt_docs->execute([':id' => $est['id']]);
        $docs_aprobados = (int) $stmt_docs->fetchColumn();
        $porcentaje_docs = $total_docs_requeridos > 0 ? ($docs_aprobados / $total_docs_requeridos) * 100 : 0;

        // Filtro restrictivo
        if ($filter_min_docs !== '' && round($porcentaje_docs) < floatval($filter_min_docs)) {
            continue; // Salta a este estudiante
        }
    }
    
    $porcentaje_pagos = 0;
    if ($requiere_pagos) {
        $deuda_total = floatval($est['costo_inscripcion']) + floatval($est['costo_matricula']) + 
                       floatval($est['costo_colegiatura']) + floatval($est['costo_ceremonia']) + floatval($est['costo_ingles'] ?? 0);
        $stmt_pagos->execute([':id' => $est['id']]);
        $res_pago = $stmt_pagos->fetch(PDO::FETCH_ASSOC);
        $total_pagado = floatval($res_pago['total_pagado'] ?? 0);
        
        $porcentaje_pagos = $deuda_total > 0 ? ($total_pagado / $deuda_total) * 100 : (($total_pagado > 0) ? 100 : 0);
        $porcentaje_pagos = $porcentaje_pagos > 100 ? 100 : $porcentaje_pagos;
        
        // Filtro restrictivo
        if ($filter_min_pagos !== '' && round($porcentaje_pagos) < floatval($filter_min_pagos)) {
            continue; // Salta a este estudiante
        }
    }

    // --- IMPRIMIR LA FILA SI PASÓ LOS FILTROS ---
    echo "<tr>";
    // Fijos
    echo "<td>" . htmlspecialchars($est['apellidos']) . "</td>";
    echo "<td>" . htmlspecialchars($est['nombres']) . "</td>";
    
    // Académicos
    if ($col_carrera) echo "<td>" . htmlspecialchars($est['carrera']) . "</td>";
    if ($col_programa) echo "<td>" . htmlspecialchars($est['tipo_programa']) . "</td>";
    if ($col_modulo) echo "<td>" . htmlspecialchars($est['modulo'] ?? 'N/A') . "</td>";
    if ($col_moodle_user) echo "<td>" . htmlspecialchars($est['moodle_user'] ?? '') . "</td>";
    if ($col_moodle_pass) echo "<td>" . htmlspecialchars($est['moodle_pass'] ?? '') . "</td>";
    if ($col_fecha_inscripcion) echo "<td>" . htmlspecialchars($est['fecha_inscripcion'] ?? '') . "</td>";
    if ($col_tipo_convenio) echo "<td>" . htmlspecialchars($est['tipo_convenio'] ?? '') . "</td>";

    // Estudiante
    if ($col_cedula) echo "<td style=\"mso-number-format:'\@'\">" . htmlspecialchars($est['cedula']) . "</td>";
    if ($col_fecha_nacimiento) echo "<td>" . htmlspecialchars($est['fecha_nacimiento'] ?? '') . "</td>";
    if ($col_telefono) echo "<td style=\"mso-number-format:'\@'\">" . htmlspecialchars($est['telefono']) . "</td>";
    if ($col_correo) echo "<td>" . htmlspecialchars($est['correo']) . "</td>";
    if ($col_provincia) echo "<td>" . htmlspecialchars($est['provincia']) . "</td>";
    if ($col_direccion) echo "<td>" . htmlspecialchars($est['direccion']) . "</td>";
    if ($col_colegio) echo "<td>" . htmlspecialchars($est['tipo_colegio']) . "</td>";
    if ($col_observaciones) echo "<td>" . htmlspecialchars($est['observaciones']) . "</td>";
    if ($col_progreso_docs) echo "<td style='text-align:center;'>" . round($porcentaje_docs) . "%</td>";

    // Pagos
    if ($col_costo_inscripcion) echo "<td>$" . htmlspecialchars($est['costo_inscripcion']) . "</td>";
    if ($col_costo_matricula) echo "<td>$" . htmlspecialchars($est['costo_matricula']) . "</td>";
    if ($col_costo_colegiatura) echo "<td>$" . htmlspecialchars($est['costo_colegiatura']) . "</td>";
    if ($col_costo_ceremonia) echo "<td>$" . htmlspecialchars($est['costo_ceremonia']) . "</td>";
    if ($col_mod_ingles) echo "<td>" . htmlspecialchars($est['modalidad_ingles'] ?? 'N/A') . "</td>";
    if ($col_costo_ingles) echo "<td>$" . htmlspecialchars($est['costo_ingles'] ?? '0.00') . "</td>";
    if ($col_progreso_pagos) echo "<td style='text-align:center;'>" . round($porcentaje_pagos) . "%</td>";

    echo "</tr>";
}

echo "</table>";
echo "</body></html>";
?>