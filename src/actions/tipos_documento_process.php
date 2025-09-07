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
            $stmt = $pdo->prepare("CALL sp_create_tipo_documento(?, ?, ?)");
            $stmt->execute([
                $_POST['codigo'],
                $_POST['nombre'],
                $_POST['descripcion']
            ]);
            break;

        case 'update':
            $stmt = $pdo->prepare("CALL sp_update_tipo_documento(?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['estado']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_tipo_documento(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=tipos_documento&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=tipos_documento&success=Operación realizada con éxito');

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=tipos_documento&error=Error: El código ya existe.');
    } else {
        header('Location: ../../public/index.php?page=tipos_documento&error=Error en la base de datos: ' . $e->getMessage());
    }
}
?>
