<?php
session_start();
require_once __DIR__ . '/../database.php';

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
            $stmt = $pdo->prepare("CALL sp_update_usuario_2fa(?, NULL, FALSE)");
            $stmt->execute([$user_id]);

            header('Location: ../../public/index.php?page=perfil&success=2FA_disabled');
            break;

        default:
            header('Location: ../../public/index.php?page=perfil&error=invalid_action');
            exit();
    }

} catch (PDOException $e) {
    header('Location: ../../public/index.php?page=perfil&error=db_error');
}
?>
