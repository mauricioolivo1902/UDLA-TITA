<?php
// exportar_contabilidad.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('contabilidad');

$pdo = Database::conexion();

// Configurar cabeceras nativas para descarga de Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_financiero_" . date('Y_m_d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Renderizar BOM para que Excel lea las tildes (UTF-8) correctamente
echo "\xEF\xBB\xBF";

try {
    // 1. Obtener todos los estudiantes ordenados por Carrera y luego Alfabéticamente
    $sql_estudiantes = "SELECT * FROM estudiantes ORDER BY carrera ASC, apellidos ASC, nombres ASC";
    $stmt_est = $pdo->query($sql_estudiantes);
    $estudiantes = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener SOLO LOS PAGOS CONSOLIDADOS ordenados por fecha para desglosarlos
    // CAMBIO APLICADO: Se añadió WHERE estado = 'Consolidado'
    $sql_pagos = "SELECT estudiante_id, valor, fecha_pago FROM pagos WHERE estado = 'Consolidado' ORDER BY fecha_pago ASC";
    $stmt_pagos = $pdo->query($sql_pagos);
    $todos_los_pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// 3. Agrupar pagos por estudiante y descubrir el número MÁXIMO de pagos
// Esto es vital para saber cuántas columnas de "PAGO X" dibujar en el Excel
$pagos_agrupados = [];
$max_pagos = 0;

foreach ($todos_los_pagos as $pago) {
    $eid = $pago['estudiante_id'];
    if (!isset($pagos_agrupados[$eid])) {
        $pagos_agrupados[$eid] = [];
    }
    $pagos_agrupados[$eid][] = floatval($pago['valor']);
    
    if (count($pagos_agrupados[$eid]) > $max_pagos) {
        $max_pagos = count($pagos_agrupados[$eid]);
    }
}

// Si nadie ha pagado nada aún, aseguramos al menos 1 columna para que la tabla no se rompa
if ($max_pagos == 0) $max_pagos = 1;

// ================= RENDERIZADO DEL EXCEL =================

// Cantidad de columnas: Estudiante (1) + Modalidad (1) + Total a Cobrar (1) + Pagos dinámicos ($max_pagos) + Saldo Pendiente (1) + Saldo Abonado (1)
$total_columnas = 5 + $max_pagos;

echo "<table border='1' cellpadding='5' cellspacing='0' style='font-family: Arial, sans-serif;'>";

// Cabecera Principal
echo "<tr><th colspan='{$total_columnas}' style='background-color:#10b981; color:#ffffff; font-size:18px; font-weight:bold; height:50px;'>Reporte Financiero General - Instituto Tecnológico RTV</th></tr>";

$carrera_actual = "";

foreach ($estudiantes as $est) {
    
    // --- SEPARADOR DE CARRERAS ---
    // Si la carrera cambia, dibujamos un encabezado nuevo
    $carrera_estudiante = empty($est['carrera']) ? "Sin Carrera Asignada" : $est['carrera'];
    
    if ($carrera_estudiante !== $carrera_actual) {
        $carrera_actual = $carrera_estudiante;
        
        // Fila del nombre de la carrera
        echo "<tr><td colspan='{$total_columnas}' style='background-color:#262f57; color:#ffffff; font-size:14px; font-weight:bold; text-align:center; height:30px; text-transform:uppercase;'>----- {$carrera_actual} -----</td></tr>";
        
        // Fila de los títulos de las columnas
        echo "<tr style='background-color:#e0e7ff; font-weight:bold; text-align:center;'>";
        echo "<th>NOMBRE DEL ESTUDIANTE</th>";
        echo "<th>MODALIDAD</th>";
        echo "<th>VALOR A COBRAR</th>";
        
        // Columnas dinámicas de pagos
        for ($i = 1; $i <= $max_pagos; $i++) {
            echo "<th>PAGO {$i}</th>";
        }
        
        echo "<th>SALDO PENDIENTE</th>";
        echo "<th>SALDO ABONADO</th>";
        echo "</tr>";
    }

    // --- CÁLCULOS FINANCIEROS DEL ESTUDIANTE ---
    
    // 1. Valor Total a Cobrar
    $total_a_cobrar = floatval($est['costo_inscripcion']) + 
                      floatval($est['costo_matricula']) + 
                      floatval($est['costo_colegiatura']) + 
                      floatval($est['costo_ceremonia']) + 
                      floatval($est['costo_ingles'] ?? 0);
                      
    // 2. Pagos Realizados (Solo los consolidados gracias a la consulta SQL)
    $eid = $est['id'];
    $pagos_del_estudiante = $pagos_agrupados[$eid] ?? [];
    
    // 3. Saldo Abonado
    $saldo_abonado = array_sum($pagos_del_estudiante);
    
    // 4. Saldo Pendiente
    $saldo_pendiente = $total_a_cobrar - $saldo_abonado;
    if ($saldo_pendiente < 0) $saldo_pendiente = 0; // Evitar saldos negativos si pagó de más

    // --- DIBUJAR FILA DEL ESTUDIANTE ---
    echo "<tr>";
    
    // Nombre completo
    echo "<td>" . htmlspecialchars($est['apellidos'] . " " . $est['nombres']) . "</td>";
    
    // Modalidad
    echo "<td style='text-align:center;'>" . htmlspecialchars($est['tipo_programa']) . "</td>";
    
    // Valor a Cobrar
    echo "<td style=\"mso-number-format:'0.00'; text-align:right;\">" . number_format($total_a_cobrar, 2, '.', '') . "</td>";
    
    // Imprimir los pagos 1 por 1
    for ($i = 0; $i < $max_pagos; $i++) {
        if (isset($pagos_del_estudiante[$i])) {
            echo "<td style=\"mso-number-format:'0.00'; text-align:right;\">" . number_format($pagos_del_estudiante[$i], 2, '.', '') . "</td>";
        } else {
            // Si no tiene este pago, dejamos la celda vacía o en cero
            echo "<td style='text-align:center; color:#9ca3af;'>-</td>";
        }
    }
    
    // Saldo Pendiente
    $color_pendiente = $saldo_pendiente > 0 ? "color:#dc2626; font-weight:bold;" : "color:#059669;";
    echo "<td style=\"mso-number-format:'0.00'; text-align:right; {$color_pendiente}\">" . number_format($saldo_pendiente, 2, '.', '') . "</td>";
    
    // Saldo Abonado
    echo "<td style=\"mso-number-format:'0.00'; text-align:right; font-weight:bold;\">" . number_format($saldo_abonado, 2, '.', '') . "</td>";
    
    echo "</tr>";
}

echo "</table>";
?>