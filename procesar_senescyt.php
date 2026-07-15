<?php
// procesar_senescyt.php
// Controlador de titulación: guarda acta, registro Senescyt y fecha.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('delegado');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro_senescyt.php');
    exit;
}

$estudiante_id   = filter_input(INPUT_POST, 'estudiante_id', FILTER_SANITIZE_NUMBER_INT);
$numero_acta     = trim($_POST['numero_acta'] ?? '');
$numero_registro = trim($_POST['numero_registro'] ?? '');
$fecha_registro  = trim($_POST['fecha_registro_titulo'] ?? '');

if (!$estudiante_id || $numero_acta === '' || $numero_registro === '' || $fecha_registro === '') {
    flash('error', 'Todos los campos (Acta, Registro y Fecha) son obligatorios para titular a un estudiante.');
    header('Location: registro_senescyt.php?tab=Para Aprobar');
    exit;
}

try {
    (new EstudianteRepository(Database::conexion()))
        ->registrarTitulacion((int) $estudiante_id, $numero_acta, $numero_registro, $fecha_registro);

    flash('exito', "¡Estudiante Titulado con Éxito! El Acta {$numero_acta} y el Registro {$numero_registro} han sido guardados oficialmente.");
    header('Location: registro_senescyt.php?tab=Aprobados');
    exit;
} catch (PDOException $e) {
    flash('error', 'Error de Base de Datos al intentar guardar: ' . $e->getMessage());
    header('Location: registro_senescyt.php?tab=Para Aprobar');
    exit;
}
