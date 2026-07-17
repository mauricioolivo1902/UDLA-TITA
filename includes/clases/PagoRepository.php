<?php
// includes/clases/PagoRepository.php
// Persistencia de los pagos y su ciclo de revisión por Contabilidad.

class PagoRepository extends RepositorioBase
{
    public function porEstudiante(int $estudianteId, string $orden = 'ASC'): array
    {
        $orden = $orden === 'DESC' ? 'DESC' : 'ASC';
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pagos WHERE estudiante_id = :id ORDER BY fecha_pago $orden, id $orden"
        );
        $stmt->execute([':id' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $pagoId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, e.carpeta_fisica
             FROM pagos p JOIN estudiantes e ON p.estudiante_id = e.id
             WHERE p.id = :id"
        );
        $stmt->execute([':id' => $pagoId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    // Bandeja de consolidación de Contabilidad
    public function listarConEstudiante(string $estado): array
    {
        $sql = "SELECT p.*, e.nombres, e.apellidos, e.cedula, e.carpeta_fisica
                FROM pagos p
                JOIN estudiantes e ON p.estudiante_id = e.id ";
        $params = [];

        if ($estado !== 'Todos') {
            $sql .= "WHERE p.estado = :estado ";
            $params[':estado'] = $estado;
        }
        $sql .= "ORDER BY p.fecha_pago ASC, p.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorEstado(string $estado): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pagos WHERE estado = :estado");
        $stmt->execute([':estado' => $estado]);
        return (int) $stmt->fetchColumn();
    }

    // Solo los pagos consolidados cuentan para el saldo del estudiante
    public function totalConsolidado(int $estudianteId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT SUM(valor) FROM pagos WHERE estudiante_id = :id AND estado = 'Consolidado'"
        );
        $stmt->execute([':id' => $estudianteId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    // Fecha del último pago registrado (los rechazados no cuentan como pago).
    // Devuelve null si el estudiante nunca ha registrado un pago válido.
    public function fechaUltimoPago(int $estudianteId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT MAX(fecha_pago) FROM pagos WHERE estudiante_id = :id AND estado != 'Rechazado'"
        );
        $stmt->execute([':id' => $estudianteId]);
        $fecha = $stmt->fetchColumn();
        return $fecha ?: null;
    }

    public function tienePendientes(int $estudianteId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM pagos WHERE estudiante_id = :id AND estado = 'En Revisión'"
        );
        $stmt->execute([':id' => $estudianteId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function registrar(int $estudianteId, string $fecha, float $valor, string $metodo, string $transaccion, string $archivo): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO pagos (estudiante_id, fecha_pago, valor, metodo_pago, num_transaccion, archivo_comprobante, estado)
             VALUES (:eid, :fecha, :valor, :metodo, :transaccion, :archivo, 'En Revisión')"
        );
        $stmt->execute([
            ':eid' => $estudianteId, ':fecha' => $fecha, ':valor' => $valor,
            ':metodo' => $metodo, ':transaccion' => $transaccion, ':archivo' => $archivo,
        ]);
    }

    public function consolidar(int $pagoId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE pagos SET estado = 'Consolidado', motivo_rechazo = NULL WHERE id = :id"
        );
        $stmt->execute([':id' => $pagoId]);
    }

    // Al rechazar se guarda el motivo y se desvincula el comprobante
    // (el archivo físico lo elimina el servicio Expediente).
    public function rechazar(int $pagoId, string $motivo): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE pagos SET estado = 'Rechazado', motivo_rechazo = :motivo, archivo_comprobante = NULL WHERE id = :id"
        );
        $stmt->execute([':motivo' => $motivo, ':id' => $pagoId]);
    }
}
