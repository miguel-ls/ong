document.addEventListener('DOMContentLoaded', function() {
    // --- Reusable Bootstrap Modal Logic ---
    const modalElement = document.getElementById('reusableModal');
    const modalMessage = document.getElementById('modalMessage');
    let reusableModal; // Variable to hold the Bootstrap Modal instance

    if (modalElement) {
        // Initialize the Bootstrap Modal instance
        reusableModal = new bootstrap.Modal(modalElement);
    }

    // Make showAlertModal globally accessible
    window.showAlertModal = function(message) {
        if (reusableModal && modalMessage) {
            // Set the message in the modal's body
            modalMessage.textContent = message;
            // Show the modal using Bootstrap's API
            reusableModal.show();
        } else {
            // Fallback for browsers or situations where Bootstrap isn't loaded
            alert(message);
        }
    }

    // --- Sidebar Toggle Logic ---
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const body = document.body;

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
            const icon = sidebarToggleBtn.querySelector('i');

            // Check the state *after* toggling
            if (body.classList.contains('sidebar-collapsed')) {
                // Now it's collapsed, so show the 'bars' icon
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            } else {
                // Now it's expanded, so show the 'times' icon
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        });
    }

    // --- Sidebar Active State and Submenu Logic ---
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page');

    if (currentPage) {
        // Find the link that contains the current page in its href and make it active
        const activeLink = document.querySelector(`.sidebar nav a[href*="page=${currentPage}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
});
