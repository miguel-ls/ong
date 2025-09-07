<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Necesario para futuras acciones

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php');
    exit();
}

$action = $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'];
$pdo = getDbConnection();

try {
    switch ($action) {
        case 'disable_2fa':
            // Llamar al SP para desactivar 2FA (borrando el secreto y poniendo el flag a false)
            $stmt = $pdo->prepare("CALL sp_update_usuario_2fa(?, NULL, FALSE)");
            $stmt->execute([$user_id]);

            header('Location: ../../public/index.php?page=perfil&success=2FA_disabled');
            break;

        // El caso 'enable_2fa' se manejará en un script de proceso de configuración separado

        default:
            header('Location: ../../public/index.php?page=perfil&error=invalid_action');
            exit();
    }

} catch (PDOException $e) {
    header('Location: ../../public/index.php?page=perfil&error=db_error');
}
?>
