<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

// Obtener filtros
$filter_nombre = $_GET['nombre'] ?? null;
$filter_num_doc = $_GET['num_doc'] ?? null;
$filter_tipo_aux = $_GET['tipo_aux'] ?? null;

try {
    $pdo = getDbConnection();

    // Obtener tipos de auxiliar para el dropdown
    $stmt_tipos = $pdo->prepare("CALL sp_read_tipos_auxiliar_for_dropdown()");
    $stmt_tipos->execute();
    $tipos_auxiliar = $stmt_tipos->fetchAll();
    $stmt_tipos->closeCursor();

    // Forzar la reconexión. A pesar de las optimizaciones, la conexión sigue fallando
    // en este punto específico. Esta es la solución más directa para asegurar una conexión viva.
    $pdo = null;
    $pdo = getDbConnection();

    // Obtener auxiliares filtrados
    $stmt_items = $pdo->prepare("CALL sp_read_all_auxiliares(?, ?, ?)");
    $stmt_items->execute([$filter_nombre, $filter_num_doc, $filter_tipo_aux]);
    $items = $stmt_items->fetchAll();

} catch (PDOException $e) {
    die("Error al obtener los auxiliares: " . $e->getMessage());
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
    <h1>Mantenimiento de Auxiliares</h1>
</header>
<section>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed_has_docs'): ?>
        <script>
            alert('No se puede eliminar el auxiliar porque tiene documentos asociados.');
        </script>
    <?php endif; ?>
    <a href="index.php?page=auxiliares_form" class="btn btn-add">Añadir Nuevo Auxiliar</a>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="auxiliares">
        <div class="form-group">
            <label for="nombre">Razón Social / Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="num_doc">Nro. Documento</label>
            <input type="text" id="num_doc" name="num_doc" value="<?= htmlspecialchars($filter_num_doc ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="tipo_aux">Tipo de Auxiliar</label>
            <select id="tipo_aux" name="tipo_aux">
                <option value="">Todos</option>
                <?php foreach($tipos_auxiliar as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= ($filter_tipo_aux == $tipo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['nombre']) ?>
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
                <th>Tipo</th>
                <th>Documento</th>
                <th>Razón Social / Nombres</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars($item['nombre_tipo_auxiliar']) ?></td>
                <td>
                    <?php
                        $doc_type = $item['tipo_doc_identidad'] ?? '';
                        $doc_num = $item['num_doc_identidad'] ?? '';
                        echo htmlspecialchars(trim($doc_type . ' ' . $doc_num));
                    ?>
                </td>
                <td><?= htmlspecialchars($item['razon_social_nombres']) ?></td>
                <td><?= htmlspecialchars($item['email']) ?></td>
                <td><?= htmlspecialchars($item['telefono']) ?></td>
                <td><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <a href="index.php?page=auxiliares_form&id=<?= $item['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/auxiliares_process.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
