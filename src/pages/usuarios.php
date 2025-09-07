<?php
require_once __DIR__ . '/../database.php';

// Solo los administradores pueden ver esta página
if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("CALL sp_read_all_usuarios()");
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
</style>

<header>
    <h1>Mantenimiento de Usuarios</h1>
</header>
<section>
    <a href="index.php?page=usuarios_form" class="btn btn-add">Añadir Nuevo Usuario</a>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
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
                <td>
                    <a href="index.php?page=usuarios_form&id=<?= $user['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="../src/actions/usuario_process.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro de que desea eliminar a este usuario?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
