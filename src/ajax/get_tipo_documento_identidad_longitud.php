<?php
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No se proporcionó el ID del tipo de documento de identidad.']);
    http_response_code(400);
    exit;
}

$id_tipo_documento = $_GET['id'];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_tipo_documento_identidad_longitud_by_id(?)");
    $stmt->execute([$id_tipo_documento]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Tipo de documento de identidad no encontrado.']);
        http_response_code(404);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    http_response_code(500);
}
?>
