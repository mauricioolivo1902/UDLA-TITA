<?php
// includes/clases/MetricasCanciller.php
// Consultas estadísticas del panel gerencial del Canciller.
// Todos los métodos aceptan un módulo opcional (null = todos los módulos),
// de modo que el dashboard puede comparar periodos y módulos sin duplicar SQL.

class MetricasCanciller extends RepositorioBase
{
    public function modulosDisponibles(): array
    {
        return $this->pdo
            ->query("SELECT DISTINCT modulo FROM estudiantes WHERE modulo IS NOT NULL AND modulo != '' ORDER BY modulo DESC")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    // ['total' => n, 'ordinario' => n, 'validacion' => n]
    public function totalesEstudiantes(?string $modulo): array
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN tipo_programa = 'Ordinario' THEN 1 ELSE 0 END) as ordinario,
                    SUM(CASE WHEN tipo_programa = 'Validación' THEN 1 ELSE 0 END) as validacion
             FROM estudiantes e $where"
        );
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total'      => (int) ($fila['total'] ?? 0),
            'ordinario'  => (int) ($fila['ordinario'] ?? 0),
            'validacion' => (int) ($fila['validacion'] ?? 0),
        ];
    }

    public function nuevosEstudiantesEntre(?string $modulo, string $desde, string $hasta): int
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM estudiantes e $where AND fecha_registro BETWEEN :desde AND :hasta"
        );
        $stmt->execute(array_merge($params, [':desde' => $desde, ':hasta' => $hasta]));
        return (int) $stmt->fetchColumn();
    }

    // [['carrera' => ..., 'cantidad' => n], ...] ordenado de mayor a menor
    public function rankingCarreras(?string $modulo): array
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT e.carrera, COUNT(*) as cantidad FROM estudiantes e $where GROUP BY e.carrera ORDER BY cantidad DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ingresosConsolidadosEntre(?string $modulo, string $desde, string $hasta): float
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT SUM(p.valor) FROM pagos p JOIN estudiantes e ON p.estudiante_id = e.id
             $where AND p.estado = 'Consolidado' AND p.fecha_pago BETWEEN :desde AND :hasta"
        );
        $stmt->execute(array_merge($params, [':desde' => $desde, ':hasta' => $hasta]));
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    // Suma de los costos contratados por todos los estudiantes del módulo
    public function proyeccionIngresos(?string $modulo): float
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT SUM(costo_inscripcion + costo_matricula + costo_colegiatura + costo_ceremonia + IFNULL(costo_ingles, 0))
             FROM estudiantes e $where"
        );
        $stmt->execute($params);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    // ['Consolidado' => $, 'En Revisión' => $, 'Rechazado' => $] del periodo
    public function cobrosPorEstadoEntre(?string $modulo, string $desde, string $hasta): array
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT p.estado, SUM(p.valor) as total FROM pagos p JOIN estudiantes e ON p.estudiante_id = e.id
             $where AND p.fecha_pago BETWEEN :desde AND :hasta GROUP BY p.estado"
        );
        $stmt->execute(array_merge($params, [':desde' => $desde, ':hasta' => $hasta]));
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Ingresos consolidados por mes (índices 1..12) de un año
    public function ingresosMensuales(?string $modulo, string $anio): array
    {
        [$where, $params] = $this->filtroModulo($modulo);
        $stmt = $this->pdo->prepare(
            "SELECT MONTH(p.fecha_pago) as mes, SUM(p.valor) as total
             FROM pagos p JOIN estudiantes e ON p.estudiante_id = e.id
             $where AND p.estado = 'Consolidado' AND YEAR(p.fecha_pago) = :anio
             GROUP BY mes"
        );
        $stmt->execute(array_merge($params, [':anio' => $anio]));
        $por_mes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $serie = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $serie[] = (float) ($por_mes[$mes] ?? 0);
        }
        return $serie;
    }

    // WHERE reutilizable: null significa "todos los módulos"
    private function filtroModulo(?string $modulo): array
    {
        if ($modulo === null || $modulo === '' || $modulo === 'todos') {
            return ['WHERE 1=1', []];
        }
        return ['WHERE 1=1 AND e.modulo = :modulo', [':modulo' => $modulo]];
    }
}
