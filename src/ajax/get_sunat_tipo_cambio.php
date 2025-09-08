<?php
header('Content-Type: application/json');

// 1. Validar el parámetro de fecha
if (!isset($_GET['fecha'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'El parámetro fecha es requerido.']);
    exit;
}

$fecha = $_GET['fecha'];

// Expresión regular para validar el formato YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'El formato de la fecha debe ser YYYY-MM-DD.']);
    exit;
}

// 2. Construir la URL de la API externa
$apiUrl = 'https://api.apis.net.pe/v1/tipo-cambio-sunat?fecha=' . urlencode($fecha);

// 3. Realizar la solicitud a la API externa
// Usar un contexto de stream para manejar errores y timeouts
$context = stream_context_create([
    'http' => [
        'timeout' => 10, // 10 segundos de timeout
        'ignore_errors' => true // Permite leer el cuerpo de la respuesta incluso si hay un error HTTP
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

// 4. Manejar la respuesta
if ($response === false) {
    http_response_code(502); // Bad Gateway
    echo json_encode(['error' => 'No se pudo conectar con la API de tipo de cambio.']);
    exit;
}

// Intentar decodificar para verificar si es un JSON válido, aunque lo pasaremos tal cual
$data = json_decode($response);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502); // Bad Gateway
    echo json_encode(['error' => 'La respuesta de la API externa no es un JSON válido.']);
    exit;
}

// Si la API externa devolvió un error (por ejemplo, 404, 500), lo reflejamos.
// $http_response_header es una variable mágica que se llena con los encabezados de la última respuesta HTTP.
if (strpos($http_response_header[0], '200 OK') === false) {
    // Intentamos obtener el código de estado real
    sscanf($http_response_header[0], 'HTTP/%*d.%*d %d', $http_status_code);
    http_response_code($http_status_code ?? 500);
}

// 5. Devolver la respuesta de la API al cliente
echo $response;
?>
