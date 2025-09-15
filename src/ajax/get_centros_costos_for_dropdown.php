<?php
session_start();
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

$año = $_GET['año'] ?? null;

if (!$año) {
    http_response_code(400);
    echo json_encode(['error' => 'El parámetro año es requerido']);
    exit();
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_centros_costos_for_dropdown(?)");
    $stmt->execute([$año]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los centros de costo: ' . $e->getMessage()]);
}
?>
