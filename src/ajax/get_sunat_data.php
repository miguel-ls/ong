<?php
header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? '';
$numero = $_GET['numero'] ?? '';

if (empty($tipo) || empty($numero)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo y número de documento son requeridos.']);
    exit;
}

if (!in_array($tipo, ['dni', 'ruc'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de documento no válido. Use "dni" o "ruc".']);
    exit;
}

$api_url = "https://api.apis.net.pe/v1/{$tipo}?numero=" . urlencode($numero);

// The user's curl examples don't show an Authorization token.
// If this API required one, it would be added to the headers like this:
// "Authorization: Bearer YOUR_API_TOKEN"

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
// Optional: set a timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Optional: for HTTPS, you might need these in some environments
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la conexión con la API externa: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    http_response_code($http_code);
    // Try to decode the response to see if the API gave a specific error message
    $error_details = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($error_details['message'])) {
         echo json_encode(['error' => 'Error de la API externa: ' . $error_details['message']]);
    } else {
         echo json_encode(['error' => 'La API externa devolvió un estado HTTP ' . $http_code]);
    }
    exit;
}

// Forward the successful response to the client
echo $response;
?>
