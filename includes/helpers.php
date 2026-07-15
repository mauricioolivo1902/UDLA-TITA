<?php
// includes/helpers.php
// Funciones auxiliares de presentación compartidas por todas las vistas.

// Escapa texto para imprimirlo en HTML. Toda salida de datos debe pasar por aquí.
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Iniciales de apellido y nombre para los avatares de las listas.
function obtenerIniciales($apellidos, $nombres): string
{
    $iniciales = mb_substr(trim((string) $apellidos), 0, 1, 'UTF-8')
               . mb_substr(trim((string) $nombres), 0, 1, 'UTF-8');
    return mb_strtoupper($iniciales, 'UTF-8');
}

// Valor monetario con formato uniforme en todo el sistema.
function dinero($valor): string
{
    return '$' . number_format((float) $valor, 2);
}

// ==========================================================
// Avisos "flash": mensajes que sobreviven a una redirección.
// flash('exito'|'error', $texto) los guarda; el partial
// mensajes_flash.php los muestra y los consume.
// ==========================================================

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][$tipo] = $mensaje;
}

function obtener_flash(string $tipo): ?string
{
    if (!isset($_SESSION['flash'][$tipo])) {
        return null;
    }
    $mensaje = $_SESSION['flash'][$tipo];
    unset($_SESSION['flash'][$tipo]);
    return $mensaje;
}

// Imprime un aviso con el estilo correspondiente a su tipo.
// La usan tanto los mensajes flash como los avisos calculados en la página.
function mostrar_aviso(string $tipo, string $mensaje): void
{
    $clases = [
        'exito' => 'aviso aviso-exito',
        'error' => 'aviso aviso-error',
    ];
    $clase = $clases[$tipo] ?? 'aviso';
    echo '<div class="' . $clase . '">' . e($mensaje) . '</div>';
}
