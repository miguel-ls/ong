<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$pdo = getDbConnection();

try {
    switch ($action) {
        case 'create':
            $stmt = $pdo->prepare("CALL sp_create_tipo_cambio(?, ?, ?)");
            $stmt->execute([
                $_POST['fecha'],
                $_POST['compra'],
                $_POST['venta']
            ]);
            break;

        case 'update':
            $stmt = $pdo->prepare("CALL sp_update_tipo_cambio(?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['fecha'],
                $_POST['compra'],
                $_POST['venta']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_tipo_cambio(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=tipos_cambio&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=tipos_cambio&success=Operación realizada con éxito');

} catch (PDOException $e) {
    // El código de error 23000 es para violación de unicidad (en este caso, la fecha)
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=tipos_cambio_form&error=duplicate_date');
    } else {
        header('Location: ../../public/index.php?page=tipos_cambio&error=db_error');
    }
}
?>
