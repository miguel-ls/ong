<?php
require_once __DIR__ . '/../database.php';

// Solo los administradores pueden ver esta página
if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

// Obtener los valores del filtro (si existen)
$filter_nombre = $_GET['nombre'] ?? null;
$filter_email = $_GET['email'] ?? null;
$filter_rol = $_GET['rol'] ?? null;

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_all_usuarios(?, ?, ?)");
    $stmt->execute([$filter_nombre, $filter_email, $filter_rol]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener usuarios: " . $e->getMessage());
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
    <h1>Mantenimiento de Usuarios</h1>
</header>
<section>
    <a href="index.php?page=usuarios_form" class="btn btn-add">Añadir Nuevo Usuario</a>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="usuarios">
        <div class="form-group">
            <label for="nombre">Nombre o Apellido</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" value="<?= htmlspecialchars($filter_email ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="">Todos</option>
                <option value="administrador" <?= ($filter_rol == 'administrador') ? 'selected' : '' ?>>Administrador</option>
                <option value="usuario" <?= ($filter_rol == 'usuario') ? 'selected' : '' ?>>Usuario</option>
            </select>
        </div>
        <button type="submit" class="btn-filter">Filtrar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>2FA</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['nombre_usuario']) ?></td>
                <td><?= htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['rol']) ?></td>
                <td><?= $user['estado'] ? 'Activo' : 'Inactivo' ?></td>
                <td><?= $user['is_2fa_enabled'] ? 'Sí' : 'No' ?></td>
                <td>
                    <a href="index.php?page=usuarios_form&id=<?= $user['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/usuario_process.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro de que desea eliminar a este usuario?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
