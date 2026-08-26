</main>
        </div>
        <footer class="footer">
            <p>Sistema de Gestión Documentaria GestDoc &copy; <?= date('Y') ?> CODESICORP SAC. Todos los derechos reservados.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.sidebar .dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
        var submenu = dropdown.nextElementSibling;
        var storageKey = 'sidebar-submenu-' + submenu.id;
        var savedState = localStorage.getItem(storageKey);

        if (savedState !== null) {
          var isOpen = savedState === 'open';
          submenu.classList.toggle('show', isOpen);
          dropdown.setAttribute('aria-expanded', String(isOpen));
        }

            dropdown.addEventListener('click', function(event) {
                event.preventDefault();

                // If sidebar is collapsed, expand it first
                if (document.body.classList.contains('sidebar-collapsed')) {
                    document.body.classList.remove('sidebar-collapsed');
                    // Update the main toggle button icon
                    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
                    if (sidebarToggleBtn) {
                        const icon = sidebarToggleBtn.querySelector('i');
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    }
                }

                submenu = this.nextElementSibling;
                var isExpanded = submenu.classList.contains('show');

                submenu.classList.toggle('show', !isExpanded);
                this.setAttribute('aria-expanded', String(!isExpanded));
                localStorage.setItem(storageKey, isExpanded ? 'closed' : 'open');
            });
        });
    });
    </script>

    <!-- Reusable Alert Modal -->
    <div class="modal fade" id="reusableModal" tabindex="-1" aria-labelledby="reusableModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="reusableModalLabel">Alerta del Sistema</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p id="modalMessage"></p>
          </div>
          <div class="modal-footer">
            <button type="button" id="modalOkButton" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
