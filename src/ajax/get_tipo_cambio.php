<?php
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_GET['fecha'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó la fecha.']);
    exit;
}

$fecha = $_GET['fecha'];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_tipo_cambio_by_fecha(?)");
    $stmt->execute([$fecha]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Encontrado en la base de datos local
        echo json_encode([
            'compra' => (float)$result['compra'],
            'venta' => (float)$result['venta'],
            'origen' => 'LOCAL'
        ]);
    } else {
        // No encontrado, devolver 0.00
        echo json_encode([
            'compra' => 0.00,
            'venta' => 0.00,
            'origen' => 'NO_ENCONTRADO'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
