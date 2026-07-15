<?php
// includes/clases/EstudianteRepository.php
// Toda la persistencia de la entidad "estudiante" vive aquí.
// Ninguna página escribe SQL de estudiantes directamente.

class EstudianteRepository extends RepositorioBase
{
    public function porId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM estudiantes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function porCedula(string $cedula): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM estudiantes WHERE cedula = :cedula");
        $stmt->execute([':cedula' => $cedula]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    // Búsqueda rápida por nombre, apellido o cédula (bandejas de revisión)
    public function buscarPorTexto(string $texto, int $limite = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM estudiantes
             WHERE nombres LIKE :b1 OR apellidos LIKE :b2 OR cedula LIKE :b3
             ORDER BY apellidos ASC LIMIT $limite"
        );
        $termino = '%' . $texto . '%';
        $stmt->execute([':b1' => $termino, ':b2' => $termino, ':b3' => $termino]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listados con filtros combinables (directorio y exportaciones).
    // Filtros soportados: nombre, programa, carrera, modulo, desde, hasta.
    public function buscarConFiltros(array $filtros): array
    {
        $sql = "SELECT * FROM estudiantes WHERE 1=1";
        $params = [];

        if (!empty($filtros['nombre'])) {
            $sql .= " AND (nombres LIKE :nombre_n OR apellidos LIKE :nombre_a)";
            $params[':nombre_n'] = '%' . $filtros['nombre'] . '%';
            $params[':nombre_a'] = '%' . $filtros['nombre'] . '%';
        }
        foreach (['programa' => 'tipo_programa', 'carrera' => 'carrera', 'modulo' => 'modulo'] as $filtro => $columna) {
            if (!empty($filtros[$filtro])) {
                $sql .= " AND $columna = :$filtro";
                $params[":$filtro"] = $filtros[$filtro];
            }
        }
        if (!empty($filtros['desde'])) {
            $sql .= " AND fecha_inscripcion >= :desde";
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $sql .= " AND fecha_inscripcion <= :hasta";
            $params[':hasta'] = $filtros['hasta'];
        }

        $sql .= " ORDER BY apellidos ASC, nombres ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Estudiantes de una carrera (o todos), para los reportes del Delegado
    public function porCarrera(string $carrera): array
    {
        return $this->buscarConFiltros($carrera !== 'Todas' ? ['carrera' => $carrera] : []);
    }

    // Módulos registrados en el sistema, para poblar los filtros
    public function modulosDisponibles(): array
    {
        return $this->pdo
            ->query("SELECT DISTINCT modulo FROM estudiantes WHERE modulo IS NOT NULL AND modulo != '' ORDER BY modulo DESC")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function crear(array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO estudiantes (
                carrera, tipo_programa, modulo, fecha_inscripcion, tipo_convenio,
                apellidos, nombres, cedula, fecha_nacimiento, telefono, correo,
                provincia, direccion, tipo_colegio, observaciones, carpeta_fisica,
                costo_inscripcion, costo_matricula, costo_colegiatura, costo_ceremonia, costo_ingles
            ) VALUES (
                :carrera, :tipo_programa, :modulo, :fecha_inscripcion, :tipo_convenio,
                :apellidos, :nombres, :cedula, :fecha_nacimiento, :telefono, :correo,
                :provincia, :direccion, :tipo_colegio, :observaciones, :carpeta_fisica,
                :costo_inscripcion, :costo_matricula, :costo_colegiatura, :costo_ceremonia, :costo_ingles
            )"
        );
        $stmt->execute([
            ':carrera' => $d['carrera'],                     ':tipo_programa' => $d['tipo_programa'],
            ':modulo' => $d['modulo'],                       ':fecha_inscripcion' => $d['fecha_inscripcion'],
            ':tipo_convenio' => $d['tipo_convenio'],         ':apellidos' => $d['apellidos'],
            ':nombres' => $d['nombres'],                     ':cedula' => $d['cedula'],
            ':fecha_nacimiento' => $d['fecha_nacimiento'],   ':telefono' => $d['telefono'],
            ':correo' => $d['correo'],                       ':provincia' => $d['provincia'],
            ':direccion' => $d['direccion'],                 ':tipo_colegio' => $d['tipo_colegio'],
            ':observaciones' => $d['observaciones'],         ':carpeta_fisica' => $d['carpeta_fisica'],
            ':costo_inscripcion' => $d['costo_inscripcion'], ':costo_matricula' => $d['costo_matricula'],
            ':costo_colegiatura' => $d['costo_colegiatura'], ':costo_ceremonia' => $d['costo_ceremonia'],
            ':costo_ingles' => $d['costo_ingles'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE estudiantes SET
                apellidos = :apellidos, nombres = :nombres, cedula = :cedula,
                correo = :correo, telefono = :telefono, direccion = :direccion,
                carrera = :carrera, tipo_programa = :tipo_programa, modulo = :modulo,
                moodle_user = :moodle_user, moodle_pass = :moodle_pass,
                fecha_inscripcion = :fecha_inscripcion, tipo_convenio = :tipo_convenio,
                fecha_nacimiento = :fecha_nacimiento, provincia = :provincia,
                tipo_colegio = :tipo_colegio, observaciones = :observaciones,
                costo_inscripcion = :costo_inscripcion, costo_matricula = :costo_matricula,
                costo_colegiatura = :costo_colegiatura, costo_ceremonia = :costo_ceremonia,
                modalidad_ingles = :modalidad_ingles, costo_ingles = :costo_ingles
             WHERE id = :id"
        );
        $stmt->execute([
            ':apellidos' => $d['apellidos'],                 ':nombres' => $d['nombres'],
            ':cedula' => $d['cedula'],                       ':correo' => $d['correo'],
            ':telefono' => $d['telefono'],                   ':direccion' => $d['direccion'],
            ':carrera' => $d['carrera'],                     ':tipo_programa' => $d['tipo_programa'],
            ':modulo' => $d['modulo'],                       ':moodle_user' => $d['moodle_user'],
            ':moodle_pass' => $d['moodle_pass'],             ':fecha_inscripcion' => $d['fecha_inscripcion'],
            ':tipo_convenio' => $d['tipo_convenio'],         ':fecha_nacimiento' => $d['fecha_nacimiento'],
            ':provincia' => $d['provincia'],                 ':tipo_colegio' => $d['tipo_colegio'],
            ':observaciones' => $d['observaciones'],         ':costo_inscripcion' => $d['costo_inscripcion'],
            ':costo_matricula' => $d['costo_matricula'],     ':costo_colegiatura' => $d['costo_colegiatura'],
            ':costo_ceremonia' => $d['costo_ceremonia'],     ':modalidad_ingles' => $d['modalidad_ingles'],
            ':costo_ingles' => $d['costo_ingles'],           ':id' => $id,
        ]);
    }

    public function guardarCredencialesMoodle(int $id, string $usuario, string $contrasena): void
    {
        $stmt = $this->pdo->prepare("UPDATE estudiantes SET moodle_user = :mu, moodle_pass = :mc WHERE id = :id");
        $stmt->execute([':mu' => $usuario, ':mc' => $contrasena, ':id' => $id]);
    }

    public function registrarTitulacion(int $id, string $acta, string $registro, string $fecha): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE estudiantes
             SET numero_acta = :acta, numero_registro = :registro, fecha_registro_titulo = :fecha
             WHERE id = :id"
        );
        $stmt->execute([':acta' => $acta, ':registro' => $registro, ':fecha' => $fecha, ':id' => $id]);
    }

    // Titulados oficialmente: acta Y registro con contenido real
    public function titulados(string $carrera = 'Todas', string $modulo = 'Todos'): array
    {
        $sql = "SELECT * FROM estudiantes
                WHERE TRIM(COALESCE(numero_acta, '')) != ''
                  AND TRIM(COALESCE(numero_registro, '')) != ''";
        $params = [];
        if ($carrera !== 'Todas') {
            $sql .= " AND carrera = :carrera";
            $params[':carrera'] = $carrera;
        }
        if ($modulo !== 'Todos') {
            $sql .= " AND modulo = :modulo";
            $params[':modulo'] = $modulo;
        }
        $sql .= " ORDER BY carrera ASC, apellidos ASC, nombres ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
