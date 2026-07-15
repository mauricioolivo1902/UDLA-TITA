// js/rechazo.js
// Despliega/oculta el formulario de rechazo con motivo de una fila.
// Lo comparten la bandeja de documentos (Admisiones) y la de pagos
// (Contabilidad); solo puede haber un formulario abierto a la vez.
function toggleRechazo(id) {
    const formulario = document.getElementById('rechazo-' + id);
    const estabaActivo = formulario.classList.contains('active');

    document.querySelectorAll('.rechazo-form-container').forEach(el => el.classList.remove('active'));

    if (!estabaActivo) {
        formulario.classList.add('active');
    }
}
