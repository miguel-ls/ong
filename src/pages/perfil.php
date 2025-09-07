<?php
require_once __DIR__ . '/../database.php';

// Obtener los datos del usuario actual desde la base de datos
$user_id = $_SESSION['user_id'];
$user = null;

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_usuario_by_id(?)");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Error al obtener la información del usuario: " . $e->getMessage());
}

if (!$user) {
    die("No se pudo encontrar al usuario.");
}
?>

<style>
    .profile-container { max-width: 700px; }
    .profile-info { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .profile-info h2 { color: #004a99; border-bottom: 2px solid #f4f7fa; padding-bottom: 10px; margin-top: 0; }
    .profile-info p { line-height: 1.6; }
    .btn-2fa { display: inline-block; padding: 10px 15px; border-radius: 5px; text-decoration: none; color: white; margin-top: 20px; }
    .btn-enable-2fa { background-color: #28a745; }
    .btn-disable-2fa { background-color: #dc3545; }
</style>

<header>
    <h1>Mi Perfil</h1>
</header>

<section class="profile-container">
    <div class="profile-info">
        <h2>Información del Usuario</h2>
        <p><strong>Nombre de Usuario:</strong> <?= htmlspecialchars($user['nombre_usuario']) ?></p>
        <p><strong>Nombres:</strong> <?= htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    </div>

    <div class="profile-info" style="margin-top: 20px;">
        <h2>Seguridad - Autenticación de Dos Factores (2FA)</h2>
        <?php if ($user['is_2fa_enabled']): ?>
            <p>El estado de 2FA es: <strong>Activado</strong>.</p>
            <p>Tu cuenta está protegida con un nivel adicional de seguridad.</p>
            <a href="../src/actions/perfil_process.php?action=disable_2fa" class="btn btn-disable-2fa" onclick="return confirm('¿Estás seguro de que quieres desactivar la autenticación de dos factores?');">Desactivar 2FA</a>
        <?php else: ?>
            <p>El estado de 2FA es: <strong>Desactivado</strong>.</p>
            <p>Añade una capa adicional de seguridad a tu cuenta. Se te pedirá un código de una aplicación de autenticación cada vez que inicies sesión.</p>
            <a href="index.php?page=2fa_setup" class="btn btn-enable-2fa">Activar 2FA</a>
        <?php endif; ?>
    </div>
</section>
