<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../database.php';

$response = ['success' => false, 'message' => 'Solicitud no válida.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado. Por favor, inicie sesión.';
    echo json_encode($response);
    exit();
}

$action = $_GET['action'] ?? null;
$id_adjunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'delete' && $id_adjunto > 0) {
    $pdo = getDbConnection();
    try {
        $pdo->beginTransaction();

        // 1. Obtener información del archivo antes de eliminar el registro
        $stmt_get = $pdo->prepare("SELECT ruta_almacenamiento, nombre_almacenado FROM documento_adjuntos WHERE id = ?");
        $stmt_get->execute([$id_adjunto]);
        $file_info = $stmt_get->fetch(PDO::FETCH_ASSOC);
        $stmt_get->closeCursor();

        if (!$file_info) {
            throw new Exception("No se encontró el adjunto con ID " . $id_adjunto);
        }

        // 2. Eliminar el registro de la base de datos
        $stmt_delete = $pdo->prepare("CALL sp_delete_adjunto_by_id(?)");
        $stmt_delete->execute([$id_adjunto]);
        $stmt_delete->closeCursor();

        // 3. Eliminar el archivo físico del servidor
        $base_path = realpath(__DIR__ . '/../../public/');
        if ($base_path === false) {
             throw new Exception("Error de configuración: El directorio público no se encuentra.");
        }

        $file_path = $base_path . DIRECTORY_SEPARATOR . $file_info['ruta_almacenamiento'] . $file_info['nombre_almacenado'];

        // Normalizar la ruta para asegurar compatibilidad
        $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);

        if (file_exists($normalized_path)) {
            if (!unlink($normalized_path)) {
                // Si unlink falla, revertimos la transacción para no tener un registro huérfano
                throw new Exception("No se pudo eliminar el archivo físico. Verifique los permisos.");
            }
        }

        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Archivo adjunto eliminado con éxito.';

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = 'Error: ' . $e->getMessage();
        http_response_code(500);
    }
} else {
    $response['message'] = 'Acción no válida o ID de adjunto no proporcionado.';
}

echo json_encode($response);
?>
