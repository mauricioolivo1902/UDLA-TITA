<?php
// includes/partials/mensajes_flash.php
// Muestra (y consume) los avisos flash dejados por la página anterior
// mediante flash('exito'|'error', $texto). Se incluye al inicio del
// contenido principal de cada página.

foreach (['exito', 'error'] as $tipo_flash) {
    $mensaje_flash = obtener_flash($tipo_flash);
    if ($mensaje_flash !== null) {
        mostrar_aviso($tipo_flash, $mensaje_flash);
    }
}
