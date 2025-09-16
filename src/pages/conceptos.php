<?php
require_once __DIR__ . '/../database.php';


// Obtener los valores del filtro
$filter_año = $_GET['año'] ?? null;
$filter_codigo = $_GET['codigo'] ?? null;
$filter_nombre = $_GET['nombre'] ?? null;
$filter_tipo = $_GET['tipo'] ?? null;

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_all_conceptos(?, ?, ?, ?)");
    $stmt->execute([$filter_año, $filter_codigo, $filter_nombre, $filter_tipo]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener los conceptos: " . $e->getMessage());
}
?>

<style>
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; }
    .table th { background-color: #004a99; color: white; }
    .table tr:nth-child(even) { background-color: #f2f2f2; }
    .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; }
    .btn-edit { background-color: #ffc107; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; display: inline-block; margin-bottom: 20px; }
    .btn-migrate { background-color: #17a2b8; display: inline-block; margin-bottom: 20px; margin-left: 10px; }
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group input, .filter-form .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1>Mantenimiento de Conceptos</h1>
</header>
<section>
    <a href="index.php?page=conceptos_form" class="btn btn-add">Añadir Nuevo Concepto</a>
    <button id="migrar-cuentas-btn" class="btn btn-migrate">Migrar Cuentas</button>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="conceptos">
        <div class="form-group">
            <label for="año">Año</label>
            <select id="año" name="año">
                <option value="">Todos</option>
                <!-- Opciones de año se cargarán aquí -->
            </select>
        </div>
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($filter_codigo ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <option value="INGRESO" <?= ($filter_tipo == 'INGRESO') ? 'selected' : '' ?>>Ingreso</option>
                <option value="GASTO" <?= ($filter_tipo == 'GASTO') ? 'selected' : '' ?>>Gasto</option>
            </select>
        </div>
        <button type="submit" class="btn-filter">Filtrar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Año</th>
                <th>Cta. Contable</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars($item['codigo']) ?></td>
                <td><?= htmlspecialchars($item['nombre']) ?></td>
                <td><?= htmlspecialchars($item['tipo']) ?></td>
                <td><?= htmlspecialchars($item['descripcion']) ?></td>
                <td><?= htmlspecialchars($item['año']) ?></td>
                <td><?= htmlspecialchars($item['cuenta_contable']) ?></td>
                <td><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <a href="index.php?page=conceptos_form&id=<?= $item['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/conceptos_process.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const anioSelect = document.getElementById('año');
    const selectedAnio = "<?= htmlspecialchars($filter_año ?? '') ?>";

    // Cargar años en el dropdown
    fetch('../src/ajax/get_conceptos_years.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error del servidor:', data.error);
                return;
            }
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.año;
                option.textContent = item.año;
                if (item.año == selectedAnio) {
                    option.selected = true;
                }
                anioSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error al cargar los años:', error);
        });

    // Lógica para el botón de migración
    const migrarBtn = document.getElementById('migrar-cuentas-btn');
    migrarBtn.addEventListener('click', function() {
        const anio = anioSelect.value;
        if (!anio || anio === "") {
            showAlertModal('Por favor, seleccione un año para la migración.');
            return;
        }

        const url = '<?php echo NODE_RED; ?>/maestros/migrarcuentas';
        const data = {
            Emp_cCodigo: '<?php echo Emp_cCodigo; ?>',
            Pan_cAnio: anio
        };

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('La respuesta de la red no fue exitosa: ' + response.statusText);
            }
            return response.json();
        })
        .then(result => {
            if (result.status === 'success') {
                if (result.data && result.data.inserted > 0) {
                    showAlertModal(`Se insertaron ${result.data.inserted} registros.`);
                } else {
                    showAlertModal('No se encontraron registros nuevos para migrar.');
                }
            } else {
                showAlertModal(result.message || 'Ocurrió un error durante la migración.');
            }
        })
        .catch(error => {
            console.error('Error en la migración:', error);
            showAlertModal('No se pudo conectar con el servicio de migración. Verifique la consola para más detalles.');
        });
    });
});
</script>
