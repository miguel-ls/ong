<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("CALL sp_read_all_tipos_cambio()");
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
</style>

<header>
    <h1>Mantenimiento de Tipos de Cambio</h1>
</header>
<section>
    <a href="index.php?page=tipos_cambio_form" class="btn btn-add">Añadir Nuevo Tipo de Cambio</a>
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
