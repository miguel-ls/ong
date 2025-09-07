<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../database.php';

use PragmaRX\Google2FAQRCode\Google2FA;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_temp_secret'])) {
    header('Location: ../../public/index.php?page=perfil&error=invalid_setup_session');
    exit();
}

$user_id = $_SESSION['user_id'];
$secret = $_SESSION['2fa_temp_secret'];
$one_time_password = $_POST['one_time_password'];

$google2fa = new Google2FA();
$isValid = $google2fa->verifyKey($secret, $one_time_password);

if ($isValid) {
    try {
        // El código es correcto, guardar el secreto en la BD y activar 2FA
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("CALL sp_update_usuario_2fa(?, ?, TRUE)");
        $stmt->execute([$user_id, $secret]);

        // Limpiar el secreto temporal de la sesión
        unset($_SESSION['2fa_temp_secret']);

        header('Location: ../../public/index.php?page=perfil&success=2fa_enabled');
        exit();

    } catch (PDOException $e) {
        header('Location: ../../public/index.php?page=2fa_setup&error=db_error');
        exit();
    }
} else {
    // El código es incorrecto, redirigir de vuelta a la configuración
    header('Location: ../../public/index.php?page=2fa_setup&error=invalid_code');
    exit();
}
?>
