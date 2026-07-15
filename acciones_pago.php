<?php
// acciones_pago.php
// Controlador de Contabilidad: consolida o rechaza un pago en revisión.
// El rechazo exige motivo (visible para el estudiante) y elimina el
// comprobante físico del expediente.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('contabilidad');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pagos_por_consolidar.php');
    exit;
}

$pago_id = filter_input(INPUT_POST, 'pago_id', FILTER_SANITIZE_NUMBER_INT);
$accion  = trim($_POST['accion'] ?? '');
$motivo  = trim($_POST['motivo_rechazo'] ?? '');

if (!$pago_id || !in_array($accion, ['Consolidado', 'Rechazado'], true)) {
    flash('error', 'Acción inválida.');
    header('Location: pagos_por_consolidar.php');
    exit;
}

if ($accion === 'Rechazado' && $motivo === '') {
    flash('error', 'Para rechazar un pago debes indicar el motivo del rechazo.');
    header('Location: pagos_por_consolidar.php');
    exit;
}

$pagos = new PagoRepository(Database::conexion());

try {
    if ($accion === 'Consolidado') {
        $pagos->consolidar((int) $pago_id);
        flash('exito', 'Pago aprobado correctamente. El saldo del estudiante ha sido actualizado.');
    } else {
        // El comprobante rechazado se elimina físicamente del expediente
        $pago = $pagos->porId((int) $pago_id);
        if ($pago && !empty($pago['archivo_comprobante'])) {
            (new Expediente())->eliminarArchivo($pago['carpeta_fisica'], Expediente::PAGOS, $pago['archivo_comprobante']);
        }

        $pagos->rechazar((int) $pago_id, $motivo);
        flash('error', 'Pago rechazado. El estudiante verá el motivo en su panel y podrá subir un nuevo comprobante.');
    }
} catch (PDOException $e) {
    flash('error', 'Error al procesar la acción: ' . $e->getMessage());
}

header('Location: pagos_por_consolidar.php');
exit;
