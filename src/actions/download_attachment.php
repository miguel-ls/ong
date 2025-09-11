<?php
session_start();
require_once __DIR__ . '/../database.php';

// 1. Verificación de seguridad: usuario autenticado y ID de adjunto
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso prohibido. Debe iniciar sesión.');
}

$id_adjunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_adjunto <= 0) {
    http_response_code(400);
    die('Solicitud incorrecta. No se proporcionó un ID de adjunto válido.');
}

try {
    $pdo = getDbConnection();

    // 2. Obtener metadatos del archivo desde la BD
    $stmt = $pdo->prepare("CALL sp_read_adjunto_by_id(?)");
    $stmt->execute([$id_adjunto]);
    $file_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$file_info) {
        http_response_code(404);
        die('Archivo no encontrado en la base de datos.');
    }

    // (Opcional) Verificación de permisos avanzada:
    // Aquí se podría añadir una lógica para verificar si el $_SESSION['user_id']
    // tiene permiso para ver el documento con id $file_info['id_documento'].

    // 3. Construir la ruta absoluta y segura al archivo
    $file_path = realpath(__DIR__ . '/../../public/' . $file_info['ruta_almacenamiento'] . $file_info['nombre_almacenado']);

    if ($file_path === false || !file_exists($file_path)) {
        http_response_code(404);
        die('El archivo no existe en el servidor.');
    }

    // 4. Enviar cabeceras HTTP para la descarga/visualización
    header('Content-Type: ' . $file_info['tipo_mime']);
    header('Content-Disposition: inline; filename="' . basename($file_info['nombre_original']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Limpiar el buffer de salida antes de enviar el archivo
    ob_clean();
    flush();

    // 5. Enviar el contenido del archivo
    readfile($file_path);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    // No mostrar el mensaje de error de la excepción directamente por seguridad
    die('Error interno del servidor al procesar la solicitud.');
}
?>
