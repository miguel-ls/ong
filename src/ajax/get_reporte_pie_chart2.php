<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$id_centro_costo = isset($_GET['id_centro_costo']) ? (int)$_GET['id_centro_costo'] : 0;

try {
    $pdo = getDbConnection();

    if ($id_centro_costo) {
        // Si se especifica un centro de costo, usa el SP existente
        $stmt = $pdo->prepare("CALL sp_reporte_total_soles_por_tipo_documento_y_centro_costo(?, ?, ?)");
        $stmt->execute([$anio, $mes, $id_centro_costo]);
    } else {
        // Si no se especifica (o es 'Todos'), agrega por tipo de documento en todos los centros de costo
        $query = "
            SELECT
                td.nombre as nombre_tipo_documento,
                SUM(d.total_soles) as total_soles
            FROM documentos d
            JOIN tipos_documento td ON d.id_tipo_documento = td.id
            WHERE YEAR(d.fecha_emision) = ? AND MONTH(d.fecha_emision) = ?
            GROUP BY td.nombre
            ORDER BY total_soles DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$anio, $mes]);
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);

} catch (Exception $e) {
    // En caso de error, devuelve un array vacío para no romper el frontend
    echo json_encode([]);
}
