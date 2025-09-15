<?php
require_once __DIR__ . '/../database.php';


$item = null;
$is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $item_id = $_GET['id'];

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("CALL sp_read_centro_costo_by_id(?)");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
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
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Centro de Costo</h1>
</header>
<section class="form-container">
    <form action="../src/actions/centros_costos_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="anio">Año</label>
            <select id="anio" name="anio" required>
                <?php
                $current_year = date('Y');
                for ($y = $current_year; $y >= 2020; $y--):
                    $selected = (isset($item['anio']) && $item['anio'] == $y) ? 'selected' : '';
                ?>
                    <option value="<?= $y ?>" <?= $selected ?>><?= $y ?></option>
                <?php endfor; ?>
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
        <?php if ($is_edit): ?>
        <div class="form-group">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" required>
                <option value="1" <?= (isset($item['estado']) && $item['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= (isset($item['estado']) && $item['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-buttons" style="display: flex; gap: 10px;">
            <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Centro de Costo</button>
            <a href="index.php?page=centros_costos" class="btn btn-secondary" style="background-color: #6c757d; text-decoration: none; padding: 10px 15px; border-radius: 4px; color: white;">Cancelar</a>
        </div>
    </form>
</section>
