<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$id_centro_costo = isset($_GET['id_centro_costo']) ? (int)$_GET['id_centro_costo'] : null;

if ($id_centro_costo === null) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_reporte_total_soles_por_tipo_documento_y_centro_costo(?, ?, ?)");
    $stmt->execute([$anio, $mes, $id_centro_costo]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([]);
}
