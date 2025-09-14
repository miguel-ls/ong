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
            $stmt = $pdo->prepare("CALL sp_create_tipo_documento_identidad(?, ?, ?, ?)");
            $stmt->execute([
                $_POST['codigo'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['longitud'] ?: null
            ]);
            break;

        case 'update':
            $stmt = $pdo->prepare("CALL sp_update_tipo_documento_identidad(?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id'],
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['longitud'] ?: null,
                $_POST['estado']
            ]);
            break;

        case 'delete':
            $stmt = $pdo->prepare("CALL sp_delete_tipo_documento_identidad(?)");
            $stmt->execute([$_GET['id']]);
            break;

        default:
            header('Location: ../../public/index.php?page=tipos_documento_identidad&error=Acción no válida');
            exit();
    }

    header('Location: ../../public/index.php?page=tipos_documento_identidad&success=Operación realizada con éxito');

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: ../../public/index.php?page=tipos_documento_identidad_form&error=' . urlencode('Error: El código ya existe.'));
    } else {
        header('Location: ../../public/index.php?page=tipos_documento_identidad&error=' . urlencode('Error en la base de datos: ' . $e->getMessage()));
    }
}
?>
