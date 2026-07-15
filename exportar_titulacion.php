<?php
// exportar_titulacion.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('delegado');

// Limpiar buffer para evitar corrupciones de Excel
if (ob_get_length()) ob_clean();

$pdo = Database::conexion();

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_titulacion_" . date('Ymd') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\"><head><meta charset=\"utf-8\"></head><body>";

// Filtros
$filtro_docs = $_GET['f_docs'] ?? 'Todos';
$filtro_pagos = $_GET['f_pagos'] ?? 'Todos';
$filtro_carrera = $_GET['f_carrera'] ?? 'Todas';

$sql = "SELECT estudiantes.*,
        (SELECT COUNT(*) FROM documentos d WHERE d.estudiante_id = estudiantes.id AND d.estado = 'Aprobado') as docs_aprobados
        FROM estudiantes WHERE 1=1";
$params = [];

if ($filtro_carrera !== 'Todas') {
    $sql .= " AND carrera = :carrera";
    $params[':carrera'] = $filtro_carrera;
}

$sql .= " ORDER BY carrera ASC, apellidos ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' cellspacing='0'>";

// Cabecera Principal
echo "<tr><th colspan='7' style='background:#262f57; color:white; height:40px; font-size:16px;'>REPORTE PARA TITULACIÓN</th></tr>";

// Si se filtró por una sola carrera, la ponemos en la cabecera general
if ($filtro_carrera !== 'Todas') {
    echo "<tr><th colspan='7' style='background:#e0e7ff; color:#374151; font-weight:bold; height:30px; text-align:left;'>CARRERA EXPORTADA: " . htmlspecialchars($filtro_carrera) . "</th></tr>";
}

$carrera_actual = "";

foreach ($estudiantes as $est) {
    // Validaciones internas (solo cuentan los documentos APROBADOS por Admisiones)
    $docs_completos = (intval($est['docs_aprobados']) >= totalRequisitosPorPrograma($est['tipo_programa']));
    
    $deuda = floatval($est['costo_inscripcion']) + floatval($est['costo_matricula']) + floatval($est['costo_colegiatura']) + floatval($est['costo_ceremonia']) + floatval($est['costo_ingles'] ?? 0);
    $stmt_p = $pdo->prepare("SELECT SUM(valor) as total FROM pagos WHERE estudiante_id = :id AND estado = 'Consolidado'");
    $stmt_p->execute([':id' => $est['id']]);
    $pagado = floatval($stmt_p->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $pagos_completos = ($pagado >= $deuda && $deuda > 0);

    // Salto a la siguiente fila si no cumple los filtros de Docs/Pagos
    if (!(($filtro_docs === 'Todos' || ($filtro_docs === 'Si' && $docs_completos) || ($filtro_docs === 'No' && !$docs_completos)) &&
        ($filtro_pagos === 'Todos' || ($filtro_pagos === 'Si' && $pagos_completos) || ($filtro_pagos === 'No' && !$pagos_completos)))) continue;

    // Separadores de Carrera en la tabla
    if ($filtro_carrera === 'Todas' && $est['carrera'] !== $carrera_actual) {
        $carrera_actual = $est['carrera'];
        echo "<tr style='background:#e0e7ff;'><th colspan='7'>CARRERA: ".htmlspecialchars($carrera_actual)."</th></tr>";
        echo "<tr style='background:#f1f5f9;'><th>Apellidos y Nombres</th><th>Cédula</th><th>Fecha Nacimiento</th><th>Provincia</th><th>Tipo Colegio</th><th>Docs</th><th>Pagos</th></tr>";
    } elseif ($filtro_carrera !== 'Todas' && $carrera_actual === "") {
        $carrera_actual = $filtro_carrera;
        echo "<tr style='background:#f1f5f9;'><th>Apellidos y Nombres</th><th>Cédula</th><th>Fecha Nacimiento</th><th>Provincia</th><th>Tipo Colegio</th><th>Docs</th><th>Pagos</th></tr>";
    }

    echo "<tr>";
    echo "<td>".htmlspecialchars($est['apellidos']." ".$est['nombres'])."</td>";
    echo "<td style=\"mso-number-format:'\@'\">".$est['cedula']."</td>";
    echo "<td>".$est['fecha_nacimiento']."</td>";
    echo "<td>".htmlspecialchars($est['provincia'])."</td>";
    echo "<td>".htmlspecialchars($est['tipo_colegio'])."</td>";
    
    $color_docs = $docs_completos ? "color:#059669; font-weight:bold;" : "color:#dc2626;";
    echo "<td style='text-align:center; {$color_docs}'>".($docs_completos ? 'SI' : 'NO')."</td>";
    
    $color_pagos = $pagos_completos ? "color:#059669; font-weight:bold;" : "color:#dc2626;";
    echo "<td style='text-align:center; {$color_pagos}'>".($pagos_completos ? 'SI' : 'NO')."</td>";
    
    echo "</tr>";
}
echo "</table></body></html>";
?>