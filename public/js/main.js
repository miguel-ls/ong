document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reusableModal');
    const modalMessage = document.getElementById('modalMessage');
    const modalOkButton = document.getElementById('modalOkButton');

    // Función global para mostrar el modal
    window.showAlertModal = function(message) {
        if (modal && modalMessage) {
            modalMessage.textContent = message;
            modal.style.display = 'flex';
        }
    }

    // Función para adjuntar el evento de cierre al botón OK
    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        }
        // Limpiar el evento onclick para que no se acumulen si se reutiliza el modal para diferentes acciones
        if (modalOkButton) {
            modalOkButton.onclick = closeModal;
        }
    }

    // Evento para cerrar el modal con el botón OK
    if (modalOkButton) {
        modalOkButton.addEventListener('click', closeModal);
    }

    // Opcional: cerrar el modal si se hace clic fuera de él
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }
});
