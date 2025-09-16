<?php
require_once __DIR__ . '/../database.php';


// Obtener los valores del filtro
$filter_inicio = $_GET['fecha_inicio'] ?? null;
$filter_fin = $_GET['fecha_fin'] ?? null;

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
    <button id="migrarTcBtn" class="btn" style="background-color: #17a2b8; color: white; margin-left: 10px;">Migrar TC SUNAT</button>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="tipos_cambio">
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
    const migrarBtn = document.getElementById('migrarTcBtn');
    if (migrarBtn) {
        migrarBtn.addEventListener('click', function() {
            if (!confirm('¿Está seguro de que desea iniciar la migración de tipos de cambio para el período 09/2025? Esta acción puede tardar unos minutos.')) {
                return;
            }

            const url = 'http://localhost:1880/maestros/migraratc';
            const data = {
                Emp_cCodigo: "088",
                Pan_cAnio: "2025",
                Per_cPeriodo: "09"
            };

            this.textContent = 'Migrando...';
            this.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
                mode: 'no-cors'
            })
            .then(() => {
                alert('Solicitud de migración enviada. El proceso se ejecutará en segundo plano y puede tardar unos minutos. La página se recargará al finalizar.');
                // We can't know for sure when it finishes with no-cors, so maybe just reload after a delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000); // Reload after 2 seconds
            })
            .catch(error => {
                console.error('Error en la migración:', error);
                alert('Error: No se pudo enviar la solicitud de migración. Verifique la consola para más detalles y asegúrese de que el servicio de migración esté activo.');
                this.textContent = 'Migrar TC SUNAT';
                this.disabled = false;
            });
        });
    }
});
</script>
