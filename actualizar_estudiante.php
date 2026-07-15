<?php
// actualizar_estudiante.php
// Controlador de edición de estudiantes. Actualiza la ficha y, si cambian
// las credenciales Moodle, sincroniza la cuenta de acceso al sistema.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: visualizar_listas.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

$campos_texto = [
    'apellidos', 'nombres', 'cedula', 'telefono', 'direccion',
    'carrera', 'tipo_programa', 'modulo', 'moodle_user', 'moodle_pass',
    'fecha_inscripcion', 'tipo_convenio', 'fecha_nacimiento', 'provincia',
    'tipo_colegio', 'observaciones', 'modalidad_ingles',
];
$datos = [];
foreach ($campos_texto as $campo) {
    $datos[$campo] = trim($_POST[$campo] ?? '');
}
$datos['correo'] = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);

foreach (['costo_inscripcion', 'costo_matricula', 'costo_colegiatura', 'costo_ceremonia', 'costo_ingles'] as $costo) {
    $datos[$costo] = filter_input(INPUT_POST, $costo, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0.00;
}

$pdo = Database::conexion();

try {
    (new EstudianteRepository($pdo))->actualizar((int) $id, $datos);

    // Usuario y clave del sistema son los mismos que los de Moodle
    if ($datos['moodle_user'] !== '' && $datos['moodle_pass'] !== '') {
        (new UsuarioRepository($pdo))->actualizarCuentaEstudiante(
            (int) $id,
            $datos['moodle_user'],
            $datos['moodle_pass'],
            $datos['apellidos'] . ' ' . $datos['nombres']
        );
    }

    flash('exito', "Datos de {$datos['nombres']} {$datos['apellidos']} actualizados correctamente.");
    header('Location: ver_estudiante.php?id=' . $id);
    exit;
} catch (PDOException $e) {
    flash('error', 'Error al actualizar: ' . $e->getMessage());
    header('Location: editar_estudiante.php?id=' . $id);
    exit;
}
