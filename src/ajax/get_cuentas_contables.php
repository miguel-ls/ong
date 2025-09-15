<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

try {
    $pdo = getDbConnection();

    // El SP espera los parámetros de la empresa y el año
    $empresaCodigo = defined('Emp_cCodigo') ? Emp_cCodigo : '';
    $anio = $_GET['año'] ?? null;

    // Si no se proporciona un año, no se pueden obtener las cuentas.
    if (!$anio) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit();
    }

    $stmt = $pdo->prepare("CALL sp_get_cuentas_contables(?, ?)");
    $stmt->execute([$empresaCodigo, $anio]);

    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($cuentas);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las cuentas contables: ' . $e->getMessage()]);
}
?>
