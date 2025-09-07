<?php
session_start();
require_once __DIR__ . '/../database.php';

// Solo los administradores pueden realizar estas acciones
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$pdo = getDbConnection();

try {
    switch ($action) {
        case 'create':
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("CALL sp_create_usuario(?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['username'],
                $hashed_password,
                $_POST['rol'],
                $_POST['nombres'],
                $_POST['apellidos'],
                $_POST['email'],
                $_POST['telefono']
            ]);
            break;

        case 'update':
            // La actualización de contraseña no se maneja aquí para simplificar.
            // Se podría hacer en un formulario separado.
            $stmt = $pdo->prepare("CALL sp_update_usuario(?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['rol'],
                $_POST['nombres'],
                $_POST['apellidos'],
                $_POST['email'],
                $_POST['telefono'],
                $_POST['estado']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_usuario(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=usuarios&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=usuarios&success=Operación realizada con éxito');

} catch (PDOException $e) {
    // Si es un error de duplicado (ej. usuario o email ya existe)
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=usuarios&error=Error: El nombre de usuario o email ya existe.');
    } else {
        header('Location: ../../public/index.php?page=usuarios&error=Error en la base de datos.');
    }
}
?>
