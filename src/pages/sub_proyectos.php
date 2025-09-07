<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

// Obtener filtros
$filter_codigo = $_GET['codigo'] ?? null;
$filter_nombre = $_GET['nombre'] ?? null;
$filter_proyecto = $_GET['proyecto'] ?? null;

try {
    $pdo = getDbConnection();

    // Obtener lista de proyectos para el dropdown del filtro
    $stmt_proyectos = $pdo->prepare("CALL sp_read_proyectos_for_dropdown()");
    $stmt_proyectos->execute();
    $proyectos = $stmt_proyectos->fetchAll();
    $stmt_proyectos->closeCursor();

    // Obtener sub proyectos filtrados
    $stmt_items = $pdo->prepare("CALL sp_read_all_sub_proyectos(?, ?, ?)");
    $stmt_items->execute([$filter_codigo, $filter_nombre, $filter_proyecto]);
    $items = $stmt_items->fetchAll();

} catch (PDOException $e) {
    die("Error al obtener los sub proyectos: " . $e->getMessage());
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
    .filter-form .form-group input, .filter-form .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1>Mantenimiento de Sub Proyectos</h1>
</header>
<section>
    <a href="index.php?page=sub_proyectos_form" class="btn btn-add">Añadir Nuevo Sub Proyecto</a>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="sub_proyectos">
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($filter_codigo ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="nombre">Nombre Sub Proyecto</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="proyecto">Proyecto Padre</label>
            <select id="proyecto" name="proyecto">
                <option value="">Todos</option>
                <?php foreach($proyectos as $proyecto): ?>
                    <option value="<?= $proyecto['id'] ?>" <?= ($filter_proyecto == $proyecto['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proyecto['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-filter">Filtrar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Proyecto Padre</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Presupuesto</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars($item['nombre_proyecto']) ?></td>
                <td><?= htmlspecialchars($item['codigo']) ?></td>
                <td><?= htmlspecialchars($item['nombre']) ?></td>
                <td><?= htmlspecialchars($item['presupuesto']) ?></td>
                <td><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <a href="index.php?page=sub_proyectos_form&id=<?= $item['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/sub_proyectos_process.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
