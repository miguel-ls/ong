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

    // Evento para cerrar el modal
    if (modalOkButton && modal) {
        modalOkButton.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Opcional: cerrar el modal si se hace clic fuera de él
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});
