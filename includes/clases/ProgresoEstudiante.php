<?php
// includes/clases/ProgresoEstudiante.php
// Reglas de negocio del avance del estudiante, usadas por el panel del
// estudiante, la ficha de Admisiones y los reportes del Delegado.
// Definiciones institucionales:
//   - Documentación completa = todos los requisitos del programa APROBADOS.
//   - Pagos completos = suma de pagos CONSOLIDADOS cubre el costo total (> 0).

class ProgresoEstudiante
{
    public function __construct(
        private DocumentoRepository $documentos,
        private PagoRepository $pagos
    ) {
    }

    // Costo total contratado: suma de los cinco rubros del estudiante
    public function deudaTotal(array $estudiante): float
    {
        return (float) $estudiante['costo_inscripcion']
             + (float) $estudiante['costo_matricula']
             + (float) $estudiante['costo_colegiatura']
             + (float) $estudiante['costo_ceremonia']
             + (float) ($estudiante['costo_ingles'] ?? 0);
    }

    // ['aprobados', 'requeridos', 'porcentaje', 'completo']
    public function progresoDocumentos(array $estudiante): array
    {
        $requeridos = totalRequisitosPorPrograma($estudiante['tipo_programa']);
        $aprobados  = $this->documentos->contarAprobados((int) $estudiante['id']);

        return [
            'aprobados'  => $aprobados,
            'requeridos' => $requeridos,
            'porcentaje' => $requeridos > 0 ? (int) round(($aprobados / $requeridos) * 100) : 0,
            'completo'   => $aprobados >= $requeridos && $requeridos > 0,
        ];
    }

    // ['pagado', 'deuda', 'saldo', 'porcentaje', 'completo']
    public function progresoPagos(array $estudiante): array
    {
        $deuda  = $this->deudaTotal($estudiante);
        $pagado = $this->pagos->totalConsolidado((int) $estudiante['id']);

        return [
            'pagado'     => $pagado,
            'deuda'      => $deuda,
            'saldo'      => $deuda - $pagado,
            'porcentaje' => $deuda > 0 ? (int) round(min(100, ($pagado / $deuda) * 100)) : 0,
            'completo'   => $pagado >= $deuda && $deuda > 0,
        ];
    }
}
