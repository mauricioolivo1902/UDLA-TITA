<?php
// includes/clases/DocumentoRepository.php
// Persistencia de los documentos de expediente subidos por los estudiantes.

class DocumentoRepository extends RepositorioBase
{
    // Documentos de un estudiante indexados por tipo, para cruzarlos
    // con el catálogo de requisitos de su programa.
    public function porEstudianteIndexados(int $estudianteId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documentos WHERE estudiante_id = :id");
        $stmt->execute([':id' => $estudianteId]);

        $indexados = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
            $indexados[$doc['tipo_documento']] = $doc;
        }
        return $indexados;
    }

    public function porTipo(int $estudianteId, string $tipo): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM documentos WHERE estudiante_id = :id AND tipo_documento = :tipo"
        );
        $stmt->execute([':id' => $estudianteId, ':tipo' => $tipo]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    // Bandeja de revisión de Admisiones: documentos con los datos del estudiante
    public function listarConEstudiante(string $estado, string $busqueda): array
    {
        $sql = "SELECT d.*, e.nombres, e.apellidos, e.cedula, e.tipo_programa, e.carpeta_fisica
                FROM documentos d
                JOIN estudiantes e ON d.estudiante_id = e.id
                WHERE 1=1 ";
        $params = [];

        if ($estado !== 'Todos') {
            $sql .= " AND d.estado = :estado ";
            $params[':estado'] = $estado;
        }
        if ($busqueda !== '') {
            $sql .= " AND (e.nombres LIKE :b1 OR e.apellidos LIKE :b2 OR e.cedula LIKE :b3) ";
            $termino = '%' . $busqueda . '%';
            $params[':b1'] = $termino;
            $params[':b2'] = $termino;
            $params[':b3'] = $termino;
        }
        $sql .= " ORDER BY d.fecha_subida ASC, d.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPendientes(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM documentos WHERE estado = 'Pendiente'")->fetchColumn();
    }

    public function contarAprobados(int $estudianteId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM documentos WHERE estudiante_id = :id AND estado = 'Aprobado'"
        );
        $stmt->execute([':id' => $estudianteId]);
        return (int) $stmt->fetchColumn();
    }

    public function insertar(int $estudianteId, string $tipo, string $archivo): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO documentos (estudiante_id, tipo_documento, archivo) VALUES (:eid, :tipo, :archivo)"
        );
        $stmt->execute([':eid' => $estudianteId, ':tipo' => $tipo, ':archivo' => $archivo]);
    }

    // Un documento negado se reemplaza con un archivo nuevo y vuelve a revisión
    public function reemplazar(int $documentoId, string $archivoNuevo): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE documentos
             SET archivo = :archivo, estado = 'Pendiente', motivo_rechazo = NULL,
                 fecha_subida = NOW(), fecha_revision = NULL
             WHERE id = :id"
        );
        $stmt->execute([':archivo' => $archivoNuevo, ':id' => $documentoId]);
    }

    // Aprueba o niega un documento pendiente. Devuelve false si ya fue revisado.
    public function revisar(int $documentoId, string $estado, ?string $motivo): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE documentos
             SET estado = :estado, motivo_rechazo = :motivo,
                 fecha_revision = NOW()
             WHERE id = :id AND estado = 'Pendiente'"
        );
        $stmt->execute([':estado' => $estado, ':motivo' => $motivo, ':id' => $documentoId]);
        return $stmt->rowCount() > 0;
    }
}
