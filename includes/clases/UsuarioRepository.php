<?php
// includes/clases/UsuarioRepository.php
// Persistencia de las cuentas de acceso al sistema (todas con hash).

class UsuarioRepository extends RepositorioBase
{
    public function porUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = :username AND activo = 1");
        $stmt->execute([':username' => $username]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function usernameExiste(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = :u");
        $stmt->execute([':u' => $username]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crearCuentaEstudiante(string $username, string $contrasenaPlana, string $nombreVisible, int $estudianteId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (username, password_hash, rol, nombre_visible, estudiante_id)
             VALUES (:username, :hash, 'estudiante', :nombre, :estudiante_id)"
        );
        $stmt->execute([
            ':username' => $username,
            ':hash' => password_hash($contrasenaPlana, PASSWORD_DEFAULT),
            ':nombre' => $nombreVisible,
            ':estudiante_id' => $estudianteId,
        ]);
    }

    // Mantiene sincronizada la cuenta del estudiante cuando Admisiones
    // edita sus credenciales Moodle (usuario y clave son los mismos).
    public function actualizarCuentaEstudiante(int $estudianteId, string $username, string $contrasenaPlana, string $nombreVisible): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios
             SET username = :username, password_hash = :hash, nombre_visible = :nombre
             WHERE estudiante_id = :estudiante_id AND rol = 'estudiante'"
        );
        $stmt->execute([
            ':username' => $username,
            ':hash' => password_hash($contrasenaPlana, PASSWORD_DEFAULT),
            ':nombre' => $nombreVisible,
            ':estudiante_id' => $estudianteId,
        ]);
    }
}
