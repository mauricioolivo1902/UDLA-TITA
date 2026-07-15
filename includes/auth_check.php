<?php
// includes/auth_check.php
// Guard de sesión y utilidades de roles, compartido por todas las páginas.
//
// Uso al inicio de cada página protegida:
//   require_once 'includes/auth_check.php';
//   exigir_rol('admisiones');            // un solo rol permitido
//   exigir_rol(['delegado', 'admin']);   // varios roles permitidos

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Página de inicio que corresponde a cada rol
function pagina_inicio_por_rol($rol) {
    switch ($rol) {
        case 'contabilidad': return 'dashboard_contabilidad.php';
        case 'delegado':     return 'dashboard_delegado.php';
        case 'admin':        return 'inicio_admin.php';
        case 'estudiante':   return 'dashboard_estudiante.php';
        case 'admisiones':   return 'dashboard.php';
        default:             return 'login.php';
    }
}

// Etiqueta visible de cada rol (se guarda en $_SESSION['user_role'])
function etiqueta_rol($rol) {
    switch ($rol) {
        case 'contabilidad': return 'Contabilidad';
        case 'delegado':     return 'Delegado Académico';
        case 'admin':        return 'Dirección General';
        case 'estudiante':   return 'Estudiante';
        case 'admisiones':   return 'Admisiones';
        default:             return 'Usuario';
    }
}

// Exige sesión iniciada; si no la hay, redirige al login
function exigir_login() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// Exige sesión iniciada Y uno de los roles indicados.
// Si el usuario tiene sesión pero otro rol, se lo envía a su propio inicio.
function exigir_rol($roles) {
    exigir_login();
    $roles = (array) $roles;
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        header("Location: " . pagina_inicio_por_rol($_SESSION['rol'] ?? ''));
        exit;
    }
}
?>
