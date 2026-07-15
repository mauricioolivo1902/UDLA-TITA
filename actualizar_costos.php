<?php
// actualizar_costos.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

$pdo = Database::conexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = filter_input(INPUT_POST, 'estudiante_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Recibimos los valores monetarios
    $inscripcion = filter_input(INPUT_POST, 'costo_inscripcion', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $matricula = filter_input(INPUT_POST, 'costo_matricula', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $colegiatura = filter_input(INPUT_POST, 'costo_colegiatura', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $ceremonia = filter_input(INPUT_POST, 'costo_ceremonia', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $ingles = filter_input(INPUT_POST, 'costo_ingles', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    try {
        $sql = "UPDATE estudiantes SET 
                costo_inscripcion = :ins, 
                costo_matricula = :mat, 
                costo_colegiatura = :col, 
                costo_ceremonia = :cer,
                costo_ingles = :ing
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ins' => $inscripcion,
            ':mat' => $matricula,
            ':col' => $colegiatura,
            ':cer' => $ceremonia,
            ':ing' => $ingles,
            ':id' => $id
        ]);

        flash('exito', 'Los costos del estudiante han sido actualizados en la base de datos.');
    } catch (PDOException $e) {
        flash('error', 'Error financiero: ' . $e->getMessage());
    }

    header("Location: ver_estudiante.php?id=" . $id);
    exit;
}