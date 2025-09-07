<?php
session_start();

require_once __DIR__ . '/../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/login.php');
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    header('Location: ../../public/login.php?error=Usuario y contraseña son requeridos');
    exit();
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("CALL sp_read_usuario_by_username(?)");
    $stmt->execute([$username]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['contrasena'])) {
        // Contraseña correcta

        // Verificar si el usuario está activo
        if (!$user['estado']) {
            header('Location: ../../public/login.php?error=El usuario está inactivo');
            exit();
        }

        // --- Lógica de 2FA (se implementará después) ---
        // Por ahora, iniciamos sesión directamente.

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['nombre_usuario'];
        $_SESSION['user_role'] = $user['rol'];

        header('Location: ../../public/index.php?page=inicio');
        exit();

    } else {
        // Usuario no encontrado o contraseña incorrecta
        header('Location: ../../public/login.php?error=Credenciales incorrectas');
        exit();
    }

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    header('Location: ../../public/login.php?error=Error del sistema. Intente más tarde.');
    exit();
}
?>
