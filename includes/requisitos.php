<?php
// includes/requisitos.php
// Lista maestra de requisitos documentales según el tipo de programa.
// Es la ÚNICA fuente de verdad: la usan el módulo del estudiante (subida),
// Admisiones (revisión) y el Delegado (cálculo de documentación completa).

function obtenerRequisitosPorPrograma($tipo_programa) {
    if ($tipo_programa === 'Validación') {
        return [
            'Solicitud_Rector' => 'Solicitud al Rector',
            'Titulo_Grado' => 'Título de Grado',
            'Copia_Cedula' => 'Copia de Cédula',
            'Papeleta_Votacion' => 'Papeleta de Votación',
            'Fotos_Carnet' => 'Fotos Carnet',
            'Planilla_Servicio' => 'Planilla Servicio Básico',
            'Declaracion_Simple' => 'Declaración Juramentada Simple',
            'Declaracion_Notariada' => 'Declaración Juramentada Notariada',
            'Hoja_Vida' => 'Hoja de Vida',
            'Certificado_Ingles' => 'Certificado de Inglés',
            'Servicio_Comunitario' => 'Servicio Comunitario',
            'Practicas_Pre_profesionales' => 'Prácticas Pre-profesionales'
        ];
    } else { // Ordinario
        return [
            'Titulo_Grado' => 'Título de Grado',
            'Copia_Cedula' => 'Copia de Cédula',
            'Papeleta_Votacion' => 'Papeleta de Votación',
            'Fotos_Carnet' => 'Fotos Carnet',
            'Planilla_Servicio' => 'Planilla Servicio Básico',
            'Hoja_Vida' => 'Hoja de Vida',
            'Certificado_Ingles' => 'Certificado de Inglés',
            'Servicio_Comunitario' => 'Servicio Comunitario',
            'Practicas_Pre_profesionales' => 'Prácticas Pre-profesionales'
        ];
    }
}

// Cantidad de documentos obligatorios para un tipo de programa
function totalRequisitosPorPrograma($tipo_programa) {
    return count(obtenerRequisitosPorPrograma($tipo_programa));
}
?>
