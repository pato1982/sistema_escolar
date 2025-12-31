/* ==================== MODALES DEL FOOTER ==================== */

// Abrir modal de privacidad
function abrirModalPrivacidad() {
    const modal = document.getElementById('modalPrivacidad');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Cerrar modal de privacidad
function cerrarModalPrivacidad(event) {
    if (event && event.target !== event.currentTarget) {
        return;
    }
    const modal = document.getElementById('modalPrivacidad');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Abrir modal de términos y condiciones
function abrirModalTerminos() {
    const modal = document.getElementById('modalTerminos');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Cerrar modal de términos y condiciones
function cerrarModalTerminos(event) {
    if (event && event.target !== event.currentTarget) {
        return;
    }
    const modal = document.getElementById('modalTerminos');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Cerrar modales con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalPrivacidad();
        cerrarModalTerminos();
    }
});
