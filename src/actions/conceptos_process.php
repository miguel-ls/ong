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
            $stmt = $pdo->prepare("CALL sp_create_concepto(?, ?, ?, ?)");
            $stmt->execute([
                $_POST['codigo'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['tipo']
            ]);
            break;

        case 'update':
            $stmt = $pdo->prepare("CALL sp_update_concepto(?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['tipo'],
                $_POST['estado']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_concepto(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=conceptos&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=conceptos&success=Operación realizada con éxito');

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=conceptos&error=Error: El código ya existe.');
    } else {
        header('Location: ../../public/index.php?page=conceptos&error=Error en la base de datos: ' . $e->getMessage());
    }
}
?>
