<?php
require_once __DIR__ . '/../database.php';


$item = null;
$is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $item_id = $_GET['id'];

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("CALL sp_read_concepto_by_id(?)");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
}
?>

<style>
    .form-container { max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-cancel { display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-align: center; text-decoration: none; border-radius: 4px; }
    .form-buttons { display: flex; gap: 10px; align-items: center; }
</style>

<header>
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Concepto</h1>
</header>
<section class="form-container">
    <form action="../src/actions/conceptos_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" required <?= $is_edit ? 'readonly' : '' ?>>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required>
                <option value="INGRESO" <?= (isset($item['tipo']) && $item['tipo'] == 'INGRESO') ? 'selected' : '' ?>>Ingreso</option>
                <option value="GASTO" <?= (isset($item['tipo']) && $item['tipo'] == 'GASTO') ? 'selected' : '' ?>>Gasto</option>
            </select>
        </div>
        <div class="form-group">
            <label for="año">Año</label>
            <select id="año" name="año" required>
                <!-- Opciones de año se cargarán aquí -->
            </select>
        </div>
        <div class="form-group">
            <label for="cuenta_contable_display">Cuenta Contable</label>
            <input type="text" id="cuenta_contable_display" name="cuenta_contable_display" list="cuentas_contables_list" value="" maxlength="150" autocomplete="off" placeholder="Escriba para buscar...">
            <input type="hidden" id="cuenta_contable" name="cuenta_contable" value="<?= htmlspecialchars($item['cuenta_contable'] ?? '') ?>">
            <datalist id="cuentas_contables_list"></datalist>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
        </div>
        <?php if ($is_edit): ?>
        <div class="form-group">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" required>
                <option value="1" <?= (isset($item['estado']) && $item['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= (isset($item['estado']) && $item['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-buttons">
            <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Concepto</button>
            <a href="index.php?page=conceptos" class="btn-cancel">Cancelar</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const anioSelect = document.getElementById('año');
    const cuentaDisplayInput = document.getElementById('cuenta_contable_display');
    const cuentaHiddenInput = document.getElementById('cuenta_contable');
    const cuentaDataList = document.getElementById('cuentas_contables_list');

    // Estado
    let accountsData = [];
    const initialCuentaCode = cuentaHiddenInput.value;

    /**
     * Carga las cuentas contables para un año específico.
     * @param {string} year - El año para el cual cargar las cuentas.
     */
    function loadCuentasContables(year) {
        if (!year) {
            cuentaDataList.innerHTML = '';
            return;
        }

        fetch(`../src/ajax/get_cuentas_contables.php?año=${year}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error del servidor al cargar cuentas:', data.error);
                    return;
                }

                cuentaDataList.innerHTML = ''; // Limpiar opciones anteriores
                accountsData = data;
                data.forEach(cuenta => {
                    const option = document.createElement('option');
                    option.value = cuenta.text;
                    option.dataset.value = cuenta.value;
                    cuentaDataList.appendChild(option);
                });

                // Si el código de cuenta inicial existe, intenta establecer el valor de visualización.
                // Esto es importante para el modo de edición.
                if (initialCuentaCode) {
                    const account = accountsData.find(acc => acc.value === initialCuentaCode);
                    if (account) {
                        cuentaDisplayInput.value = account.text;
                    } else {
                        // La cuenta guardada no es válida para este año, así que la limpiamos.
                        cuentaDisplayInput.value = '';
                        cuentaHiddenInput.value = '';
                    }
                }
            })
            .catch(error => console.error('Error al cargar las cuentas contables:', error));
    }

    /**
     * Carga los años disponibles en el desplegable de años.
     */
    function loadAnios() {
        const currentAnio = "<?= htmlspecialchars($item['año'] ?? '') ?>";

        fetch('../src/ajax/get_conceptos_years.php')
            .then(response => response.json())
            .then(data => {
                let years = data.map(item => item.año);
                if (years.length === 0) years.push(new Date().getFullYear());
                if (currentAnio && !years.includes(parseInt(currentAnio))) {
                    years.push(parseInt(currentAnio));
                    years.sort((a, b) => b - a);
                }

                anioSelect.innerHTML = '';
                years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    if (year == currentAnio) option.selected = true;
                    anioSelect.appendChild(option);
                });

                if (!currentAnio && years.length > 0) {
                    anioSelect.value = years[0];
                }

                // Una vez que los años están cargados y seleccionados, carga las cuentas contables.
                loadCuentasContables(anioSelect.value);
            })
            .catch(error => {
                console.error('Error al cargar los años:', error);
                // Fallback
                const option = document.createElement('option');
                const year = new Date().getFullYear();
                option.value = year;
                option.textContent = year;
                anioSelect.appendChild(option);
                loadCuentasContables(year);
            });
    }

    // --- Event Listeners ---

    // Cuando el año cambia, limpia el campo de cuenta y recarga las cuentas.
    anioSelect.addEventListener('change', function() {
        cuentaDisplayInput.value = '';
        cuentaHiddenInput.value = '';
        loadCuentasContables(this.value);
    });

    // Sincroniza el input de texto con el valor oculto.
    cuentaDisplayInput.addEventListener('input', function(e) {
        const selectedOption = Array.from(cuentaDataList.options).find(opt => opt.value === e.target.value);
        cuentaHiddenInput.value = selectedOption ? selectedOption.dataset.value : '';
    });

    // --- Inicialización ---
    loadAnios();
});
</script>
