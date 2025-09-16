<?php
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'Spanish');
require_once __DIR__ . '/../database.php';


// Obtener los valores del filtro
$filter_anio = $_GET['filter_anio'] ?? null;
$filter_mes = $_GET['filter_mes'] ?? null;
$filter_inicio = $_GET['fecha_inicio'] ?? null;
$filter_fin = $_GET['fecha_fin'] ?? null;

// Si se selecciona año y/o mes, estos tienen precedencia y calculan el rango de fechas.
if (!empty($filter_anio) && $filter_anio !== "") {
    if (!empty($filter_mes) && $filter_mes !== "") {
        // Año y mes seleccionados: calcula el primer y último día del mes.
        $filter_inicio = "{$filter_anio}-{$filter_mes}-01";
        $ultimo_dia = date('t', strtotime($filter_inicio));
        $filter_fin = "{$filter_anio}-{$filter_mes}-{$ultimo_dia}";
    } else {
        // Solo año seleccionado: calcula el primer y último día del año.
        $filter_inicio = "{$filter_anio}-01-01";
        $filter_fin = "{$filter_anio}-12-31";
    }
}

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
    .btn-migrate { background-color: #17a2b8; display: inline-block; margin-bottom: 20px; margin-left: 10px; }
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group input, .filter-form .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1>Mantenimiento de Tipos de Cambio</h1>
</header>
<section>
    <a href="index.php?page=tipos_cambio_form" class="btn btn-add">Añadir Nuevo Tipo de Cambio</a>
    <button id="migrar-tc-btn" class="btn btn-migrate">Migrar TC</button>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="tipos_cambio">
        <div class="form-group">
            <label for="filter_anio">Año</label>
            <select id="filter_anio" name="filter_anio">
                <option value="">Todos</option>
                <?php
                $currentYear = date('Y');
                // The user wants the year 2025 to be available in the dropdown
                for ($year = $currentYear + 1; $year >= 2020; $year--) {
                    $selected = ($_GET['filter_anio'] ?? '') == $year ? 'selected' : '';
                    echo "<option value=\"$year\" $selected>$year</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_mes">Mes</label>
            <select id="filter_mes" name="filter_mes">
                <option value="">Todos</option>
                <?php
                for ($month = 1; $month <= 12; $month++) {
                    $monthName = strftime('%B', mktime(0, 0, 0, $month, 10));
                    $monthValue = str_pad($month, 2, '0', STR_PAD_LEFT);
                    $selected = ($_GET['filter_mes'] ?? '') == $monthValue ? 'selected' : '';
                    echo "<option value=\"$monthValue\" $selected>" . ucfirst($monthName) . "</option>";
                }
                ?>
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
document.addEventListener('DOMContentLoaded', function () {
    const migrarTcBtn = document.getElementById('migrar-tc-btn');
    const anioFilter = document.getElementById('filter_anio');
    const mesFilter = document.getElementById('filter_mes');
    const fechaInicioFilter = document.getElementById('fecha_inicio');
    const fechaFinFilter = document.getElementById('fecha_fin');

    const yearMonthGroup = [anioFilter, mesFilter];
    const dateRangeGroup = [fechaInicioFilter, fechaFinFilter];

    function handleYearMonthChange() {
        const disableDates = anioFilter.value !== '' || mesFilter.value !== '';
        dateRangeGroup.forEach(el => {
            el.disabled = disableDates;
            if (disableDates) el.value = '';
        });
    }

    function handleDateChange() {
        const disableYearMonth = fechaInicioFilter.value !== '' || fechaFinFilter.value !== '';
        yearMonthGroup.forEach(el => {
            el.disabled = disableYearMonth;
            if (disableYearMonth) el.value = '';
        });
    }

    yearMonthGroup.forEach(el => el.addEventListener('change', handleYearMonthChange));
    dateRangeGroup.forEach(el => el.addEventListener('input', handleDateChange));

    // Set initial state on page load based on URL parameters
    handleYearMonthChange();
    handleDateChange();

    migrarTcBtn.addEventListener('click', function () {
        const anio = anioFilter.value;
        const mes = mesFilter.value;

        if (!anio || anio === "" || !mes || mes === "") {
            showAlertModal('Por favor, seleccione un año y un mes para la migración.');
            return;
        }

        const url = '<?php echo NODE_RED; ?>/maestros/migraratc';
        const data = {
            Emp_cCodigo: '<?php echo Emp_cCodigo; ?>',
            Pan_cAnio: anio,
            Per_cPeriodo: mes
        };

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
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
