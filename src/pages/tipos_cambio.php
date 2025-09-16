<?php
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../../config/config.php';

// Obtener los valores del filtro
$filter_anio = $_GET['anio'] ?? 'todos';
$filter_mes = $_GET['mes'] ?? 'todos';

$filter_inicio = null;
$filter_fin = null;

if ($filter_anio !== 'todos') {
    $year = intval($filter_anio);
    if ($filter_mes !== 'todos') {
        // Mes específico seleccionado
        $month = str_pad(intval($filter_mes), 2, '0', STR_PAD_LEFT);
        $filter_inicio = "{$year}-{$month}-01";
        $filter_fin = date('Y-m-t', strtotime($filter_inicio));
    } else {
        // Solo año seleccionado
        $filter_inicio = "{$year}-01-01";
        $filter_fin = "{$year}-12-31";
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
    .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; cursor: pointer; }
    .btn-edit { background-color: #ffc107; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; }
    .btn-migrate { background-color: #17a2b8; } /* Color cian para el nuevo botón */
    .header-actions { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1>Mantenimiento de Tipos de Cambio</h1>
</header>
<section>
    <div class="header-actions">
        <a href="index.php?page=tipos_cambio_form" class="btn btn-add">Añadir Nuevo Tipo de Cambio</a>
        <button id="migrateTC" class="btn btn-migrate">Migrar TC</button>
    </div>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="tipos_cambio">
        <div class="form-group">
            <label for="anio">Año</label>
            <select id="anio" name="anio" onchange="this.form.submit()">
                <option value="todos">Todos</option>
                <?php
                $currentYear = date('Y');
                for ($i = $currentYear; $i >= 2020; $i--) {
                    $selected = ($filter_anio == $i) ? 'selected' : '';
                    echo "<option value='{$i}' {$selected}>{$i}</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="mes">Mes</label>
            <select id="mes" name="mes" onchange="this.form.submit()">
                <option value="todos">Todos</option>
                <?php
                $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                foreach ($meses as $index => $nombre) {
                    $num_mes = $index + 1;
                    $selected = ($filter_mes == $num_mes) ? 'selected' : '';
                    echo "<option value='{$num_mes}' {$selected}>{$nombre}</option>";
                }
                ?>
            </select>
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
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5">No se encontraron registros para el período seleccionado.</td>
                </tr>
            <?php else: ?>
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
            <?php endif; ?>
        </tbody>
    </table>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const migrateButton = document.getElementById('migrateTC');
    if (migrateButton) {
        migrateButton.addEventListener('click', function() {
            const anio = document.getElementById('anio').value;
            const mes = document.getElementById('mes').value;

            if (anio === 'todos' || mes === 'todos') {
                showAlertModal('Por favor, seleccione un año y un mes específicos para poder migrar los datos.');
                return;
            }

            const nodeRedUrl = '<?php echo NODE_RED; ?>';
            const empCodigo = '088'; // Asignado directamente como en el ejemplo de cURL
            // Asegurarse de que el mes tenga dos dígitos (ej. '09')
            const paddedMes = mes.toString().padStart(2, '0');

            const apiData = {
                Emp_cCodigo: empCodigo,
                Pan_cAnio: anio,
                Per_cPeriodo: paddedMes
            };

            // Mostrar un modal de carga/espera si existe
            // showAlertModal('Migrando datos, por favor espere...');

            fetch(`${nodeRedUrl}/maestros/migraratc`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(apiData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error en la red: ${response.statusText}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.status === 'success') {
                    if (result.data && typeof result.data.inserted !== 'undefined') {
                        if (result.data.inserted > 0) {
                            showAlertModal(`Se insertaron ${result.data.inserted} registros.`);
                            // Opcional: Recargar la página para ver los nuevos datos
                             setTimeout(() => window.location.reload(), 2000);
                        } else {
                            showAlertModal('No se encontraron nuevos registros para migrar.');
                        }
                    } else {
                         showAlertModal('Respuesta inesperada del servidor.');
                    }
                } else {
                    showAlertModal(`Error en la migración: ${result.message || 'Respuesta no exitosa.'}`);
                }
            })
            .catch(error => {
                console.error('Error en la llamada de migración:', error);
                showAlertModal(`No se pudo conectar con el servicio de migración. ${error.message}`);
            });
        });
    }
});
</script>
