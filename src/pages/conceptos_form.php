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
    const displayInput = document.getElementById('cuenta_contable_display');
    const hiddenInput = document.getElementById('cuenta_contable');
    const dataList = document.getElementById('cuentas_contables_list');
    let accountsData = [];

    function setInitialValue() {
        const initialCode = hiddenInput.value;
        if (initialCode && accountsData.length > 0) {
            const account = accountsData.find(acc => acc.value === initialCode);
            if (account) {
                displayInput.value = account.text;
            }
        }
    }

    fetch('../src/ajax/get_cuentas_contables.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error del servidor:', data.error);
                return;
            }
            accountsData = data;
            data.forEach(cuenta => {
                const option = document.createElement('option');
                option.value = cuenta.text;
                option.dataset.value = cuenta.value;
                dataList.appendChild(option);
            });

            // Si estamos editando, intentamos poner el valor descriptivo
            setInitialValue();
        })
        .catch(error => {
            console.error('Error al cargar las cuentas contables:', error);
        });

    displayInput.addEventListener('input', function(e) {
        const inputText = e.target.value;
        const selectedOption = Array.from(dataList.options).find(opt => opt.value === inputText);

        if (selectedOption) {
            hiddenInput.value = selectedOption.dataset.value;
        } else {
            // Si el usuario borra el input o escribe algo que no está en la lista,
            // borramos el valor del campo oculto para evitar enviar un dato inválido.
            hiddenInput.value = '';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const anioSelect = document.getElementById('año');
    const currentAnio = "<?= htmlspecialchars($item['año'] ?? '') ?>";

    // Si no hay años, podemos agregar el actual o dejarlo vacío
    // Para este caso, vamos a buscar los años disponibles
    fetch('../src/ajax/get_conceptos_years.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error del servidor:', data.error);
                return;
            }

            // Si no hay años en la BD, se puede agregar el año actual como opción
            let years = data.map(item => item.año);
            if (years.length === 0) {
                const currentServerYear = new Date().getFullYear();
                years.push(currentServerYear);
            }

            // Si estamos editando y el año del item no está en la lista, lo agregamos
            if (currentAnio && !years.includes(parseInt(currentAnio))) {
                years.push(parseInt(currentAnio));
                years.sort((a, b) => b - a); // Re-ordenar descendente
            }

            anioSelect.innerHTML = ''; // Limpiar opciones existentes
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year == currentAnio) {
                    option.selected = true;
                }
                anioSelect.appendChild(option);
            });

            // Si no hay año seleccionado (ej. en 'crear'), y hay años, seleccionar el más reciente
            if (!currentAnio && years.length > 0) {
                 anioSelect.value = years[0];
            }
        })
        .catch(error => {
            console.error('Error al cargar los años:', error);
            // Fallback: agregar solo el año actual si la carga falla
            const option = document.createElement('option');
            const year = new Date().getFullYear();
            option.value = year;
            option.textContent = year;
            anioSelect.appendChild(option);
            anioSelect.value = year;
        });
});
</script>
