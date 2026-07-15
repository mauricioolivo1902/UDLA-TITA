<?php
// exportar_titulados.php
// Exporta a Excel el listado de estudiantes 100% titulados (con acta y
// registro Senescyt). Lo invoca el botón "Exportar Titulados" de la pestaña
// Aprobados en registro_senescyt.php, con filtro opcional por carrera.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('delegado');

// Limpiar buffer para evitar corrupciones de Excel
if (ob_get_length()) ob_clean();

$pdo = Database::conexion();

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=titulados_senescyt_" . date('Ymd') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\"><head><meta charset=\"utf-8\"></head><body>";

// Mismos filtros que la pantalla de registro_senescyt.php
$filtro_carrera = $_GET['f_carrera'] ?? 'Todas';
$filtro_modulo  = $_GET['f_modulo'] ?? 'Todos';

try {
    $titulados = (new EstudianteRepository($pdo))->titulados($filtro_carrera, $filtro_modulo);
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

echo "<table border='1' cellpadding='5' cellspacing='0'>";

// Cabecera Principal
echo "<tr><th colspan='6' style='background:#6d28d9; color:white; height:40px; font-size:16px;'>ESTUDIANTES TITULADOS - REGISTRO SENESCYT</th></tr>";

if ($filtro_carrera !== 'Todas' || $filtro_modulo !== 'Todos') {
    $detalle_filtros = [];
    if ($filtro_carrera !== 'Todas') $detalle_filtros[] = 'CARRERA: ' . htmlspecialchars($filtro_carrera);
    if ($filtro_modulo !== 'Todos')  $detalle_filtros[] = 'MÓDULO: ' . htmlspecialchars($filtro_modulo);
    echo "<tr><th colspan='6' style='background:#ede9fe; color:#374151; font-weight:bold; height:30px; text-align:left;'>" . implode(' | ', $detalle_filtros) . "</th></tr>";
}

if (empty($titulados)) {
    echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No existen estudiantes titulados para el filtro seleccionado.</td></tr>";
} else {
    $carrera_actual = "";

    foreach ($titulados as $est) {
        // Separadores de Carrera en la tabla (mismo patrón que exportar_titulacion.php)
        if ($est['carrera'] !== $carrera_actual) {
            $carrera_actual = $est['carrera'];
            if ($filtro_carrera === 'Todas') {
                echo "<tr style='background:#ede9fe;'><th colspan='6'>CARRERA: " . htmlspecialchars($carrera_actual) . "</th></tr>";
            }
            echo "<tr style='background:#f1f5f9;'><th>Apellidos y Nombres</th><th>Cédula</th><th>Carrera</th><th>Número de Acta</th><th>Registro Senescyt</th><th>Fecha de Registro</th></tr>";
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($est['apellidos'] . " " . $est['nombres']) . "</td>";
        echo "<td style=\"mso-number-format:'\@'\">" . htmlspecialchars($est['cedula']) . "</td>";
        echo "<td>" . htmlspecialchars($est['carrera']) . "</td>";
        echo "<td style=\"mso-number-format:'\@'\">" . htmlspecialchars($est['numero_acta']) . "</td>";
        echo "<td style=\"mso-number-format:'\@'\">" . htmlspecialchars($est['numero_registro']) . "</td>";
        echo "<td>" . (!empty($est['fecha_registro_titulo']) ? date('d/m/Y', strtotime($est['fecha_registro_titulo'])) : 'N/A') . "</td>";
        echo "</tr>";
    }
}

echo "</table></body></html>";
?>
