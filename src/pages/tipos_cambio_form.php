<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

$item = null;
$is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $item_id = $_GET['id'];

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("CALL sp_read_tipo_cambio_by_id(?)");
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
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Tipo de Cambio</h1>
</header>
<section class="form-container">
    <form action="../src/actions/tipos_cambio_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($item['fecha'] ?? date('Y-m-d')) ?>" required>
        </div>
        <div class="form-group">
            <label for="compra">Tipo de Cambio (Compra)</label>
            <input type="number" step="0.0001" id="compra" name="compra" value="<?= htmlspecialchars($item['compra'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="venta">Tipo de Cambio (Venta)</label>
            <input type="number" step="0.0001" id="venta" name="venta" value="<?= htmlspecialchars($item['venta'] ?? '') ?>" required>
        </div>

        <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Registro</button>
    </form>
</section>
