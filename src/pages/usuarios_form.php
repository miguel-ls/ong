<?php
require_once __DIR__ . '/../database.php';

// Solo los administradores pueden acceder
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
    // Redirigir a la página de inicio con un mensaje de error
    header('Location: index.php?page=inicio&error=acceso_denegado');
    exit();
}

$user = null;
$is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $user_id = $_GET['id'];

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("CALL sp_read_usuario_by_id(?)");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        die("Error al obtener datos del usuario: " . $e->getMessage());
    }
}
?>

<style>
    .form-container { max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Usuario</h1>
</header>
<section class="form-container">
    <form action="../src/actions/usuario_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="nombres">Nombres</label>
            <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($user['nombres'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="apellidos">Apellidos</label>
            <input type="text" id="apellidos" name="apellidos" value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="username">Nombre de Usuario</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['nombre_usuario'] ?? '') ?>" required <?= $is_edit ? 'readonly' : '' ?>>
        </div>
        <div class="form-group">
            <label for="password">Contraseña <?= !$is_edit ? '(requerida)' : '(dejar en blanco para no cambiar)' ?></label>
            <input type="password" id="password" name="password" <?= !$is_edit ? 'required' : '' ?>>
        </div>
        <div class="form-group">
            <label for="rol">Rol</label>
            <select id="rol" name="rol" required>
                <option value="usuario" <?= (isset($user['rol']) && $user['rol'] == 'usuario') ? 'selected' : '' ?>>Usuario</option>
                <option value="administrador" <?= (isset($user['rol']) && $user['rol'] == 'administrador') ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>
        <div class="form-group">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" required>
                <option value="1" <?= (isset($user['estado']) && $user['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= (isset($user['estado']) && $user['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Usuario</button>
    </form>
</section>
