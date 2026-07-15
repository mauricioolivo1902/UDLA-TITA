<?php
// procesar_estudiante.php
// Controlador de creación de estudiantes. Orquesta tres piezas:
//   1. Expediente        -> carpeta física con DOCUMENTOS/ y PAGOS/
//   2. EstudianteRepository / UsuarioRepository -> persistencia
//   3. CredencialesEstudiante -> usuario y clave (sistema NEO y Moodle)
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crear_estudiante.php');
    exit;
}

// Sanear entradas del formulario
$campos_texto = [
    'carrera', 'tipo_programa', 'modulo', 'fecha_inscripcion', 'tipo_convenio',
    'apellidos', 'nombres', 'cedula', 'fecha_nacimiento', 'telefono',
    'provincia', 'direccion', 'tipo_colegio', 'observaciones',
];
$datos = [];
foreach ($campos_texto as $campo) {
    $datos[$campo] = trim($_POST[$campo] ?? '');
}
$datos['correo'] = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);

foreach (['costo_inscripcion', 'costo_matricula', 'costo_colegiatura', 'costo_ceremonia', 'costo_ingles'] as $costo) {
    $datos[$costo] = filter_input(INPUT_POST, $costo, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0.00;
}

if ($datos['apellidos'] === '' || $datos['nombres'] === '' || $datos['cedula'] === '') {
    flash('error', 'Apellidos, nombres y cédula son obligatorios.');
    header('Location: crear_estudiante.php');
    exit;
}

$pdo         = Database::conexion();
$estudiantes = new EstudianteRepository($pdo);
$usuarios    = new UsuarioRepository($pdo);
$expediente  = new Expediente();

try {
    // La cédula identifica de forma única a la persona
    $existente = $estudiantes->porCedula($datos['cedula']);
    if ($existente) {
        flash('error', "El número de cédula ({$datos['cedula']}) ya se encuentra registrado a nombre de: "
            . $existente['apellidos'] . ' ' . $existente['nombres'] . '. Por favor, verifique.');
        header('Location: crear_estudiante.php');
        exit;
    }

    $datos['carpeta_fisica'] = Expediente::nombreCarpeta($datos['apellidos'], $datos['nombres']);
    $expediente->crearCarpetas($datos['carpeta_fisica']);

    // Estudiante + cuenta de acceso se crean juntos o no se crea ninguno
    $pdo->beginTransaction();

    $estudiante_id = $estudiantes->crear($datos);

    $credenciales = (new CredencialesEstudiante($usuarios))
        ->generar($datos['apellidos'], $datos['nombres'], $datos['cedula']);

    $nombre_completo = $datos['apellidos'] . ' ' . $datos['nombres'];
    $usuarios->crearCuentaEstudiante($credenciales['usuario'], $credenciales['contrasena'], $nombre_completo, $estudiante_id);
    $estudiantes->guardarCredencialesMoodle($estudiante_id, $credenciales['usuario'], $credenciales['contrasena']);

    $pdo->commit();

    flash('exito', "Estudiante {$datos['nombres']} {$datos['apellidos']} creado con éxito. Carpeta de expedientes generada.");
    $_SESSION['credenciales_generadas'] = [
        'nombre'     => $nombre_completo,
        'usuario'    => $credenciales['usuario'],
        'contrasena' => $credenciales['contrasena'],
    ];
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Error grave al guardar en BD: ' . $e->getMessage());
}

header('Location: crear_estudiante.php');
exit;
