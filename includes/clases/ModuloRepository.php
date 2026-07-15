<?php
// includes/clases/ModuloRepository.php
// Persistencia de los módulos académicos (ej. 2026-1) que administra el
// Canciller. El nombre del módulo se guarda como texto en estudiantes.modulo,
// por eso renombrar propaga el cambio y eliminar exige que no esté en uso.

class ModuloRepository extends RepositorioBase
{
    public function todos(): array
    {
        return $this->pdo
            ->query("SELECT * FROM modulos ORDER BY nombre DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM modulos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function nombreExiste(string $nombre): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM modulos WHERE nombre = :n");
        $stmt->execute([':n' => $nombre]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(string $nombre): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO modulos (nombre) VALUES (:n)");
        $stmt->execute([':n' => $nombre]);
    }

    // Renombrar propaga el nuevo nombre a los estudiantes que ya lo usan,
    // para no dejar fichas apuntando a un módulo inexistente.
    public function renombrar(int $id, string $nombreNuevo): void
    {
        $modulo = $this->porId($id);
        if (!$modulo) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE modulos SET nombre = :n WHERE id = :id");
            $stmt->execute([':n' => $nombreNuevo, ':id' => $id]);

            $stmt = $this->pdo->prepare("UPDATE estudiantes SET modulo = :nuevo WHERE modulo = :viejo");
            $stmt->execute([':nuevo' => $nombreNuevo, ':viejo' => $modulo['nombre']]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function estudiantesQueLoUsan(string $nombre): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM estudiantes WHERE modulo = :n");
        $stmt->execute([':n' => $nombre]);
        return (int) $stmt->fetchColumn();
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM modulos WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}
