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
    // Validación del Tipo de Documento de Identidad para crear y actualizar
    if ($action === 'create' || $action === 'update') {
        if (empty($_POST['id_tipo_documento_identidad']) || !is_numeric($_POST['id_tipo_documento_identidad'])) {
            // Construir la URL de redirección, manteniendo el ID si es una actualización
            $redirect_url = '../../public/index.php?page=auxiliares_form&error=invalid_doc_type';
            if ($action === 'update' && isset($_POST['id'])) {
                $redirect_url .= '&id=' . $_POST['id'];
            }
            header('Location: ' . $redirect_url);
            exit();
        }
    }

    switch ($action) {
        case 'create':
            $stmt = $pdo->prepare("CALL sp_create_auxiliar(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_tipo_auxiliar'],
                $_POST['id_tipo_documento_identidad'],
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
                $_POST['id_tipo_documento_identidad'],
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['status'] === 'HAS_DOCS') {
                header('Location: ../../public/index.php?page=auxiliares&error=delete_failed_has_docs');
                exit();
            }
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
        // Redirigir con el mensaje de error real para otros problemas
        $error_message = urlencode("Error en la base de datos: " . $e->getMessage());
        header('Location: ../../public/index.php?page=auxiliares&error=' . $error_message);
    }
}
?>
