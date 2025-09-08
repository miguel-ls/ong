<?php
// Silencio, es un endpoint de API
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

$id_proyecto = $_GET['id_proyecto'] ?? null;

if (!$id_proyecto) {
    echo json_encode([]);
    exit();
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_sub_proyectos_by_proyecto_id(?)");
    $stmt->execute([$id_proyecto]);
    $subproyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($subproyectos);

} catch (Exception $e) {
    // En caso de error, devolver un array vacío.
    // Se podría añadir un log de errores aquí.
    echo json_encode([]);
}
