<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../database.php';

use PragmaRX\Google2FAQRCode\Google2FA;

if (!isset($_SESSION['2fa_pending_user_id'])) {
    header('Location: ../../public/login.php');
    exit();
}

$user_id = $_SESSION['2fa_pending_user_id'];
$one_time_password = $_POST['one_time_password'];

try {
    $pdo = getDbConnection();
    // Necesitamos leer el usuario para obtener su secreto 2FA
    $stmt = $pdo->prepare("CALL sp_read_usuario_by_id(?)");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // Si no se encuentra el usuario, algo anda muy mal.
        throw new Exception("Usuario pendiente de 2FA no encontrado en la base de datos.");
    }

    $google2fa = new Google2FA();
    $isValid = $google2fa->verifyKey($user['secret_2fa'], $one_time_password);

    if ($isValid) {
        // El código es correcto. Limpiar la sesión temporal y crear la sesión final.
        unset($_SESSION['2fa_pending_user_id']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['nombre_usuario'];
        $_SESSION['user_role'] = $user['rol'];

        header('Location: ../../public/index.php?page=inicio');
        exit();

    } else {
        // Código incorrecto
        header('Location: ../../public/2fa_verify.php?error=invalid_code');
        exit();
    }

} catch (Exception $e) {
    // Manejo de errores (DB o de otro tipo)
    // En una app real, aquí se registraría el error.
    session_destroy(); // Destruir la sesión por seguridad
    header('Location: ../../public/login.php?error=system_error');
    exit();
}
?>
