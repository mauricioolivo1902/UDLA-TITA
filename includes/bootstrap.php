<?php
// includes/bootstrap.php
// Punto de arranque único de la aplicación. Toda página lo incluye primero:
//
//   require_once __DIR__ . '/includes/bootstrap.php';
//   exigir_rol('admisiones');
//
// Centralizar el arranque evita repetir sesión/carga en cada página y
// garantiza que las clases y helpers estén siempre disponibles.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autocarga: cada clase vive en includes/clases/<NombreClase>.php
spl_autoload_register(function ($clase) {
    $ruta = __DIR__ . '/clases/' . $clase . '.php';
    if (file_exists($ruta)) {
        require_once $ruta;
    }
});

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/requisitos.php';
