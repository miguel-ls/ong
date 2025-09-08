<?php
header('Content-Type: application/json');

// 1. Validar el parámetro de número de RUC
if (!isset($_GET['numero'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'El parámetro numero es requerido.']);
    exit;
}

$numero_ruc = $_GET['numero'];

// Validar que el RUC tenga 11 dígitos y sea numérico
if (!preg_match('/^\d{11}$/', $numero_ruc)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'El número de RUC debe tener 11 dígitos.']);
    exit;
}

// 2. Construir la URL de la API externa
$apiUrl = 'https://api.apis.net.pe/v1/ruc?numero=' . urlencode($numero_ruc);

// 3. Realizar la solicitud a la API externa
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

// 4. Manejar la respuesta
if ($response === false) {
    http_response_code(502); // Bad Gateway
    echo json_encode(['error' => 'No se pudo conectar con la API de consulta RUC.']);
    exit;
}

// Intentar decodificar para verificar si es un JSON válido
$data = json_decode($response);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502); // Bad Gateway
    echo json_encode(['error' => 'La respuesta de la API externa no es un JSON válido.']);
    exit;
}

// Si la API externa devolvió un error (por ejemplo, 404, 500), lo reflejamos.
if (strpos($http_response_header[0], '200 OK') === false) {
    sscanf($http_response_header[0], 'HTTP/%*d.%*d %d', $http_status_code);
    http_response_code($http_status_code ?? 500);
}

// 5. Devolver la respuesta de la API al cliente
echo $response;
