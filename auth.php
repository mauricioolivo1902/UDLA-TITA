<?php
// auth.php
// Controlador de inicio de sesión: valida las credenciales contra la
// tabla `usuarios` (hash) y arma la sesión según el rol.
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$usuario  = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');

try {
    $usuarios = new UsuarioRepository(Database::conexion());
    $cuenta = $usuarios->porUsername($usuario);

    if ($cuenta && password_verify($password, $cuenta['password_hash'])) {
        // Regenerar el ID de sesión evita ataques de fijación de sesión
        session_regenerate_id(true);

        $_SESSION['loggedin']      = true;
        $_SESSION['user_id']       = (int) $cuenta['id'];
        $_SESSION['rol']           = $cuenta['rol'];
        $_SESSION['user_role']     = etiqueta_rol($cuenta['rol']);
        $_SESSION['user_name']     = $cuenta['nombre_visible'];
        $_SESSION['estudiante_id'] = $cuenta['estudiante_id'] !== null ? (int) $cuenta['estudiante_id'] : null;

        header('Location: ' . pagina_inicio_por_rol($cuenta['rol']));
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['login_error'] = 'Error de base de datos al iniciar sesión. Detalles: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}

$_SESSION['login_error'] = 'Usuario o contraseña incorrectos. Inténtalo de nuevo.';
header('Location: login.php');
exit;
