<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("CALL sp_read_all_proyectos()");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener los proyectos: " . $e->getMessage());
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
</style>

<header>
    <h1>Mantenimiento de Proyectos</h1>
</header>
<section>
    <a href="index.php?page=proyectos_form" class="btn btn-add">Añadir Nuevo Proyecto</a>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Presupuesto</th>
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
                <td><?= htmlspecialchars($item['fecha_inicio']) ?></td>
                <td><?= htmlspecialchars($item['fecha_fin']) ?></td>
                <td><?= htmlspecialchars($item['presupuesto']) ?></td>
                <td><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <a href="index.php?page=proyectos_form&id=<?= $item['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/proyectos_process.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
