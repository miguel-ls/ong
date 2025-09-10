<?php
session_start();
require_once __DIR__ . '/../database.php';

$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado. Por favor, inicie sesión.';
    echo json_encode($response);
    exit();
}

$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $is_post ? (json_decode(file_get_contents('php://input'), true)['header']['action'] ?? null) : ($_GET['action'] ?? null);
if($is_post) {
    // For create/update, the action is inside the JSON payload
    $data = json_decode(file_get_contents('php://input'), true);
    $header = $data['header'];
    $details = $data['details'];
    $action = !empty($header['id_documento']) ? 'update' : 'create';
}


$pdo = getDbConnection();

try {
    // Begin Transaction for CUD operations
    if ($action !== 'read') { // Assuming read operations don't need transactions
        $pdo->beginTransaction();
    }

    switch ($action) {
        case 'create':
        case 'update':
            if (!$data || !isset($data['header']) || !isset($data['details'])) {
                throw new Exception('Datos del formulario inválidos o incompletos.');
            }

            // Server-side Calculation and Validation
            $subtotal = 0;
            foreach ($details as $item) {
                $subtotal += (float)$item['cantidad'] * (float)$item['precio_unitario'];
            }

            $igv = $subtotal * 0.18;
            $total = $subtotal + $igv;
            $tc = (float)$header['tipo_cambio'];
            $is_soles = $header['moneda'] === 'SOLES';

            $total_soles = $is_soles ? $total : $total * $tc;
            $total_dolares = !$is_soles ? $total : $total / $tc;

            if ($action === 'update') {
                $doc_id = $header['id_documento'];
                $stmt_update = $pdo->prepare("CALL sp_update_documento_header(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_update->execute([$doc_id, $header['id_tipo_documento'], $header['id_proyecto'], $header['id_sub_proyecto'], $header['id_centro_costo'], $header['id_auxiliar'], $header['serie_documento'], $header['numero_documento'], $header['fecha_emision'], $header['moneda'], $tc, $subtotal, $igv, $total, $total_soles, $total_dolares, $header['glosa']]);
                $stmt_delete_details = $pdo->prepare("CALL sp_delete_documento_detalle_by_id_documento(?)");
                $stmt_delete_details->execute([$doc_id]);
                $response['message'] = 'Documento actualizado con éxito.';
            } else { // create
                $stmt_create = $pdo->prepare("CALL sp_create_documento_header(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @new_id)");
                $stmt_create->execute([$header['id_tipo_documento'], $header['id_proyecto'], $header['id_sub_proyecto'], $header['id_centro_costo'], $header['id_auxiliar'], $_SESSION['user_id'], $header['serie_documento'], $header['numero_documento'], $header['fecha_emision'], $header['moneda'], $tc, $subtotal, $igv, $total, $total_soles, $total_dolares, $header['glosa']]);
                $stmt_create->closeCursor();
                $doc_id = $pdo->query("SELECT @new_id as new_id")->fetch(PDO::FETCH_ASSOC)['new_id'];
                if (!$doc_id) throw new Exception("No se pudo crear el encabezado del documento.");
                $response['message'] = 'Documento creado con éxito.';
            }

            // Insert new details for both create and update
            $stmt_insert_detail = $pdo->prepare("CALL sp_create_documento_detalle(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($details as $index => $item) {
                $item_total = (float)$item['cantidad'] * (float)$item['precio_unitario'];
                $item_total_soles = $is_soles ? $item_total : $item_total * $tc;
                $item_total_dolares = !$is_soles ? $item_total : $item_total / $tc;
                $descripcion = $item['descripcion'] ?? '';
                $id_centro_costo = $item['id_centro_costo'] ?? null; // Get CC from item
                $stmt_insert_detail->execute([$doc_id, $index + 1, $item['cantidad'], $descripcion, $item['id_concepto'], $id_centro_costo, $item['precio_unitario'], $item_total, $item_total_soles, $item_total_dolares]);
            }
            break;

        case 'delete':
            if (!isset($_GET['id'])) throw new Exception("ID de documento no proporcionado para eliminar.");
            $doc_id = $_GET['id'];
            $stmt_delete = $pdo->prepare("CALL sp_delete_documento(?)");
            $stmt_delete->execute([$doc_id]);
            $pdo->commit(); // Commit the transaction before redirecting
            // Since this is called via a link, we redirect instead of returning JSON
            header('Location: ../../public/index.php?page=ingreso_documentos&success=' . urlencode('Documento eliminado con éxito.'));
            exit();

        default:
            throw new Exception("Acción no válida.");
    }

    $pdo->commit();
    $response['success'] = true;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

header('Content-Type: application/json');
echo json_encode($response);
?>
