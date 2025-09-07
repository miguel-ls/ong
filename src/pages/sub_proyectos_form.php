<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

$item = null;
$is_edit = false;
$proyectos = [];

try {
    $pdo = getDbConnection();
    // Obtener todos los proyectos para el dropdown
    $stmt_proyectos = $pdo->prepare("CALL sp_read_all_proyectos(?, ?)");
    $stmt_proyectos->execute([null, null]);
    $proyectos = $stmt_proyectos->fetchAll();
    $stmt_proyectos->closeCursor();

    if (isset($_GET['id'])) {
        $is_edit = true;
        $item_id = $_GET['id'];

        $stmt_item = $pdo->prepare("CALL sp_read_sub_proyecto_by_id(?)");
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch();
    }
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<style>
    .form-container { max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Sub Proyecto</h1>
</header>
<section class="form-container">
    <form action="../src/actions/sub_proyectos_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="id_proyecto">Proyecto Padre</label>
            <select id="id_proyecto" name="id_proyecto" required>
                <option value="">Seleccione un proyecto</option>
                <?php foreach ($proyectos as $proyecto): ?>
                    <option value="<?= $proyecto['id'] ?>" <?= (isset($item['id_proyecto']) && $item['id_proyecto'] == $proyecto['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proyecto['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" required <?= $is_edit ? 'readonly' : '' ?>>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="presupuesto">Presupuesto</label>
            <input type="number" step="0.01" id="presupuesto" name="presupuesto" value="<?= htmlspecialchars($item['presupuesto'] ?? '') ?>">
        </div>
        <?php if ($is_edit): ?>
        <div class="form-group">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" required>
                <option value="1" <?= (isset($item['estado']) && $item['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= (isset($item['estado']) && $item['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Sub Proyecto</button>
    </form>
</section>
