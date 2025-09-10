<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_reporte_total_soles_por_mes_vs_centro_costo(?, ?)");
    $stmt->execute([$anio, $mes]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([]);
}
