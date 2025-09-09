<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['error' => 'ID de tipo de documento inválido.']);
    http_response_code(400);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_tipo_documento_identidad_longitud_by_id(?)");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($result) {
        echo json_encode(['longitud' => $result['longitud']]);
    } else {
        echo json_encode(['longitud' => null]);
    }
} catch (PDOException $e) {
    // It's better not to expose detailed DB errors in a public-facing AJAX endpoint.
    // Log the error instead for debugging.
    error_log("AJAX Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar la base de datos.']);
}
?>
