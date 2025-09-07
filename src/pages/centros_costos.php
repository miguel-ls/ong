<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

// Obtener los valores del filtro
$filter_codigo = $_GET['codigo'] ?? null;
$filter_nombre = $_GET['nombre'] ?? null;

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_all_centros_costos(?, ?)");
    $stmt->execute([$filter_codigo, $filter_nombre]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener los centros de costos: " . $e->getMessage());
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
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1>Mantenimiento de Centros de Costos</h1>
</header>
<section>
    <a href="index.php?page=centros_costos_form" class="btn btn-add">Añadir Nuevo Centro de Costo</a>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="centros_costos">
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($filter_codigo ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
        </div>
        <button type="submit" class="btn-filter">Filtrar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Descripción</th>
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
                <td><?= htmlspecialchars($item['descripcion']) ?></td>
                <td><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <a href="index.php?page=centros_costos_form&id=<?= $item['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/centros_costos_process.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
