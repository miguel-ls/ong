<?php
require_once __DIR__ . '/../database.php';


// Obtener los valores del filtro
$filter_inicio = $_GET['fecha_inicio'] ?? null;
$filter_fin = $_GET['fecha_fin'] ?? null;
$filter_year = $_GET['year'] ?? date('Y');
$filter_month = $_GET['month'] ?? date('m');
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_all_tipos_cambio(?, ?)");
    $stmt->execute([$filter_inicio, $filter_fin]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener los tipos de cambio: " . $e->getMessage());
}
?>

<style>
    .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    .table th { background-color: #004a99; color: white; }
    .table tr:nth-child(even) { background-color: #f2f2f2; }
    .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; }
    .btn-edit { background-color: #ffc107; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; display: inline-block; margin-bottom: 20px; }
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1>Mantenimiento de Tipos de Cambio</h1>
</header>
<section>
    <a href="index.php?page=tipos_cambio_form" class="btn btn-add">Añadir Nuevo Tipo de Cambio</a>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="tipos_cambio">

        <div class="form-group">
            <label for="year">Año</label>
            <select id="year" name="year" class="form-control">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="month">Mes</label>
            <select id="month" name="month" class="form-control">
                <?php foreach ($meses as $num => $nombre): ?>
                    <option value="<?= str_pad($num, 2, '0', STR_PAD_LEFT) ?>" <?= ($filter_month == $num) ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="fecha_inicio">Fecha Desde</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($filter_inicio ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="fecha_fin">Fecha Hasta</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($filter_fin ?? '') ?>">
        </div>
        <button type="submit" class="btn-filter">Filtrar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Compra</th>
                <th>Venta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars(date("d/m/Y", strtotime($item['fecha']))) ?></td>
                <td><?= htmlspecialchars($item['compra']) ?></td>
                <td><?= htmlspecialchars($item['venta']) ?></td>
                <td>
                    <a href="index.php?page=tipos_cambio_form&id=<?= $item['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/tipos_cambio_process.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro de que desea eliminar este registro? La acción no se puede deshacer.');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearSelect = document.getElementById('year');
    const monthSelect = document.getElementById('month');
    const startDateInput = document.getElementById('fecha_inicio');
    const endDateInput = document.getElementById('fecha_fin');

    function updateDateFields() {
        const year = parseInt(yearSelect.value, 10);
        const month = parseInt(monthSelect.value, 10);

        if (!year || !month) {
            return;
        }

        // Calculate the first day of the month
        const startDate = new Date(year, month - 1, 1);

        // Calculate the last day of the month
        const endDate = new Date(year, month, 0);

        // Format dates as YYYY-MM-DD
        const startDateStr = startDate.toISOString().split('T')[0];
        const endDateStr = endDate.toISOString().split('T')[0];

        // Set the values of the date inputs
        startDateInput.value = startDateStr;
        endDateInput.value = endDateStr;
    }

    // Add event listeners
    yearSelect.addEventListener('change', updateDateFields);
    monthSelect.addEventListener('change', updateDateFields);

    // Initial call on page load to set dates if year/month are pre-selected,
    // but only if the date fields are not already filled from a specific GET request.
    if (!startDateInput.value && !endDateInput.value) {
        updateDateFields();
    }
});
</script>
