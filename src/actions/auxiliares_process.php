<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, returning JSON might be better, but for now, redirect.
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

// The action can come from POST (forms) or GET (delete links)
$action = $_REQUEST['action'] ?? null;

if (!$action) {
    header('Location: ../../public/index.php?page=auxiliares&error=Acción no especificada');
    exit();
}

$pdo = getDbConnection();
$redirect_page = '../../public/index.php?page=auxiliares';

try {
    switch ($action) {
        case 'create':
            // Check for duplicates
            $stmt_check = $pdo->prepare("CALL sp_check_auxiliar_duplicado(?, ?, NULL)");
            $stmt_check->execute([$_POST['id_tipo_auxiliar'], $_POST['num_doc_identidad']]);
            if ($stmt_check->fetch()) {
                $stmt_check->closeCursor();
                $_SESSION['form_data'] = $_POST;
                $error_message = "Ya existe un auxiliar con el mismo tipo y número de documento.";
                header('Location: ../../public/index.php?page=auxiliares_form&error_modal=' . urlencode($error_message));
                exit();
            }
            $stmt_check->closeCursor();

            $stmt = $pdo->prepare("CALL sp_create_auxiliar(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_tipo_auxiliar'],
                $_POST['id_tipo_documento_identidad'],
                $_POST['num_doc_identidad'],
                $_POST['razon_social_nombres'],
                $_POST['direccion'],
                $_POST['telefono'],
                $_POST['email'],
                $_POST['ubigeo'],
                $_POST['TipoERP'],
                $_POST['CodigoERP']
            ]);
            $redirect_page .= '&success=' . urlencode("Auxiliar creado con éxito.");
            break;

        case 'update':
            if (!isset($_POST['id'])) throw new Exception("ID de auxiliar no proporcionado para actualizar.");

            // Check for duplicates
            $stmt_check = $pdo->prepare("CALL sp_check_auxiliar_duplicado(?, ?, ?)");
            $stmt_check->execute([$_POST['id_tipo_auxiliar'], $_POST['num_doc_identidad'], $_POST['id']]);
            if ($stmt_check->fetch()) {
                $stmt_check->closeCursor();
                $_SESSION['form_data'] = $_POST;
                $error_message = "Ya existe otro auxiliar con el mismo tipo y número de documento.";
                header('Location: ../../public/index.php?page=auxiliares_form&id=' . $_POST['id'] . '&error_modal=' . urlencode($error_message));
                exit();
            }
            $stmt_check->closeCursor();

            $stmt = $pdo->prepare("CALL sp_update_auxiliar(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                $_POST['estado'],
                $_POST['TipoERP'],
                $_POST['CodigoERP']
            ]);
            $redirect_page .= '&success=' . urlencode("Auxiliar actualizado con éxito.");
            break;

        case 'delete':
            if (!isset($_GET['id'])) throw new Exception("ID de auxiliar no proporcionado para eliminar.");
            $stmt = $pdo->prepare("CALL sp_delete_auxiliar(?)");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($result['status'] === 'HAS_DOCS') {
                $redirect_page .= '&error=' . urlencode("No se puede eliminar el auxiliar porque tiene documentos asociados.");
            } else {
                $redirect_page .= '&success=' . urlencode("Auxiliar eliminado con éxito.");
            }
            break;

        default:
            throw new Exception("Acción no válida.");
    }

} catch (PDOException $e) {
    // For form submissions, redirect back to the form with an error
    if ($action === 'create' || $action === 'update') {
        $error_url = '../../public/index.php?page=auxiliares_form';
        if (isset($_POST['id'])) {
            $error_url .= '&id=' . $_POST['id'];
        }
        $error_url .= '&error=' . urlencode('Error de base de datos: ' . $e->getMessage());
        header('Location: ' . $error_url);
        exit();
    } else {
        // For other errors (like delete), redirect to the list
        $redirect_page .= '&error=' . urlencode('Error de base de datos: ' . $e->getMessage());
    }
} catch (Exception $e) {
    $redirect_page .= '&error=' . urlencode($e->getMessage());
}

header('Location: ' . $redirect_page);
exit();
?>
