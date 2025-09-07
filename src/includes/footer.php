</main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('click', function(event) {
                event.preventDefault();
                var submenu = this.nextElementSibling;
                if (submenu.style.display === 'block') {
                    submenu.style.display = 'none';
                } else {
                    // Opcional: cerrar otros submenús abiertos
                    document.querySelectorAll('.sidebar .collapse').forEach(function(otherSubmenu) {
                        if (otherSubmenu !== submenu) {
                            otherSubmenu.style.display = 'none';
                        }
                    });
                    submenu.style.display = 'block';
                }
            });
        });
    });
    </script>
</body>
</html>
