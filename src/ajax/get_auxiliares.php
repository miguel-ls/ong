<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_auxiliares_for_dropdown()");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([]);
}
