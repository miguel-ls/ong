<?php
header('Content-Type: application/json');

if (!isset($_GET['fecha'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El parámetro fecha es requerido.']);
    exit;
}

$fecha = $_GET['fecha'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato de la fecha debe ser YYYY-MM-DD.']);
    exit;
}

$apiUrl = 'https://api.apis.net.pe/v1/tipo-cambio-sunat?fecha=' . urlencode($fecha);

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo conectar con la API de tipo de cambio.']);
    exit;
}

$data = json_decode($response);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'La respuesta de la API externa no es un JSON válido.']);
    exit;
}

if (strpos($http_response_header[0], '200 OK') === false) {
    sscanf($http_response_header[0], 'HTTP/%*d.%*d %d', $http_status_code);
    http_response_code($http_status_code ?? 500);
}

echo $response;
?>
