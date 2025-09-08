<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

$item = null;
$is_edit = false;
$tipos_auxiliar = [];

try {
    $pdo = getDbConnection();
    // Obtener todos los tipos para el dropdown
    $stmt_tipos = $pdo->prepare("CALL sp_read_all_tipos_auxiliar(?, ?)");
    $stmt_tipos->execute([null, null]);
    $tipos_auxiliar = $stmt_tipos->fetchAll();
    $stmt_tipos->closeCursor();

    if (isset($_GET['id'])) {
        $is_edit = true;
        $item_id = $_GET['id'];

        // Re-establish connection to prevent "MySQL server has gone away" errors on long-running pages
        $pdo = getDbConnection();

        $stmt_item = $pdo->prepare("CALL sp_read_auxiliar_by_id(?)");
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch();
        $stmt_item->closeCursor();
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
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Auxiliar</h1>
</header>
<section class="form-container">
    <form action="../src/actions/auxiliares_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="id_tipo_auxiliar">Tipo de Auxiliar</label>
            <select id="id_tipo_auxiliar" name="id_tipo_auxiliar" required>
                <option value="">Seleccione un tipo</option>
                <?php foreach ($tipos_auxiliar as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= (isset($item['id_tipo_auxiliar']) && $item['id_tipo_auxiliar'] == $tipo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="tipo_doc_identidad">Tipo Documento Identidad</label>
            <select id="tipo_doc_identidad" name="tipo_doc_identidad" required>
                <option value="RUC" <?= (isset($item['tipo_doc_identidad']) && $item['tipo_doc_identidad'] == 'RUC') ? 'selected' : '' ?>>RUC</option>
                <option value="DNI" <?= (isset($item['tipo_doc_identidad']) && $item['tipo_doc_identidad'] == 'DNI') ? 'selected' : '' ?>>DNI</option>
                <option value="CE" <?= (isset($item['tipo_doc_identidad']) && $item['tipo_doc_identidad'] == 'CE') ? 'selected' : '' ?>>Carnet Extranjería</option>
                <option value="PASAPORTE" <?= (isset($item['tipo_doc_identidad']) && $item['tipo_doc_identidad'] == 'PASAPORTE') ? 'selected' : '' ?>>Pasaporte</option>
                <option value="OTRO" <?= (isset($item['tipo_doc_identidad']) && $item['tipo_doc_identidad'] == 'OTRO') ? 'selected' : '' ?>>Otro</option>
            </select>
        </div>
        <div class="form-group">
            <label for="num_doc_identidad">Número Documento</label>
            <input type="text" id="num_doc_identidad" name="num_doc_identidad" value="<?= htmlspecialchars($item['num_doc_identidad'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="razon_social_nombres">Razón Social / Nombres</label>
            <input type="text" id="razon_social_nombres" name="razon_social_nombres" value="<?= htmlspecialchars($item['razon_social_nombres'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($item['direccion'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($item['telefono'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
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

        <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Auxiliar</button>
    </form>
</section>
