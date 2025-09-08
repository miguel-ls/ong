<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$pdo = getDbConnection();

try {
    switch ($action) {
        case 'create':
            $stmt = $pdo->prepare("CALL sp_create_auxiliar(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_tipo_auxiliar'],
                $_POST['tipo_doc_identidad'],
                $_POST['num_doc_identidad'],
                $_POST['razon_social_nombres'],
                $_POST['direccion'],
                $_POST['telefono'],
                $_POST['email'],
                $_POST['ubigeo']
            ]);
            break;

        case 'update':
            $stmt = $pdo->prepare("CALL sp_update_auxiliar(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['id_tipo_auxiliar'],
                $_POST['tipo_doc_identidad'],
                $_POST['num_doc_identidad'],
                $_POST['razon_social_nombres'],
                $_POST['direccion'],
                $_POST['telefono'],
                $_POST['email'],
                $_POST['ubigeo'],
                $_POST['estado']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_auxiliar(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=auxiliares&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=auxiliares&success=Operación realizada con éxito');

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=auxiliares&error=Error: El número de documento ya existe.');
    } else {
        header('Location: ../../public/index.php?page=auxiliares&error=Error en la base de datos: ' . $e->getMessage());
    }
}
?>
