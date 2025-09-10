</main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

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
                    if (otherSubmenu !== submenu) {
                       // Opcional: si quieres que solo uno esté abierto a la vez
                       // otherSubmenu.style.display = 'none';
                       // otherSubmenu.previousElementSibling.setAttribute('aria-expanded', 'false');
                    }
                });

                // Abrir o cerrar el menú actual
                if (submenu.style.display === 'block') {
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
