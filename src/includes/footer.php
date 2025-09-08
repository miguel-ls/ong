</main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('click', function(event) {
                event.preventDefault();
                var submenu = this.nextElementSibling;
                var isExpanded = this.getAttribute('aria-expanded') === 'true';

                // Cerrar todos los menús
                document.querySelectorAll('.sidebar .collapse').forEach(function(otherSubmenu) {
                    otherSubmenu.style.display = 'none';
                });
                document.querySelectorAll('.sidebar .dropdown-toggle').forEach(function(otherDropdown) {
                    otherDropdown.setAttribute('aria-expanded', 'false');
                });

                // Abrir o cerrar el menú actual
                if (isExpanded) {
                    submenu.style.display = 'none';
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    submenu.style.display = 'block';
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });
    });
    </script>

    <!-- Reusable Modal -->
    <div id="reusableModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <p id="modalMessage"></p>
            <button id="modalOkButton" class="btn">Aceptar</button>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
