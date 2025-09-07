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
            $stmt = $pdo->prepare("CALL sp_create_sub_proyecto(?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_proyecto'],
                $_POST['codigo'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['presupuesto']
            ]);
            break;

        case 'update':
            $stmt = $pdo->prepare("CALL sp_update_sub_proyecto(?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['id_proyecto'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['presupuesto'],
                $_POST['estado']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_sub_proyecto(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=sub_proyectos&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=sub_proyectos&success=Operación realizada con éxito');

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=sub_proyectos&error=Error: El código ya existe.');
    } else {
        header('Location: ../../public/index.php?page=sub_proyectos&error=Error en la base de datos: ' . $e->getMessage());
    }
}
?>
