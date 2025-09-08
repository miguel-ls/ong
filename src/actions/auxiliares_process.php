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
            $form_page = $action === 'create' ? 'auxiliares_form' : 'auxiliares_form&id=' . $_POST['id'];
            header('Location: ../../public/index.php?page=' . $form_page . '&error=invalid_doc_type');
            exit();
        }
    }

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
    // --- DEBUGGING ---
    // Modificado para mostrar siempre el error real de la base de datos.
    $error_message = "Error Detallado de la Base de Datos: " . $e->getMessage() .
                     " (Código: " . $e->getCode() . ")" .
                     " | En Archivo: " . $e->getFile() . " Línea: " . $e->getLine();

    // Para asegurar que el mensaje se vea, lo mostramos directamente.
    die('<pre>' . htmlspecialchars($error_message) . '</pre>');
}
?>
