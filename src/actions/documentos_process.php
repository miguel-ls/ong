<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../database.php';

$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado. Por favor, inicie sesión.';
    echo json_encode($response);
    exit();
}

$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $_GET['action'] ?? null;

if ($is_post) {
    // Data now comes from FormData, not a single JSON payload
    if (!isset($_POST['header']) || !isset($_POST['details'])) {
        $response['message'] = 'Datos del formulario no recibidos correctamente.';
        echo json_encode($response);
        exit();
    }
    $header = json_decode($_POST['header'], true);
    $details = json_decode($_POST['details'], true);
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
            if (!$header || !$details) {
                throw new Exception('Datos del formulario inválidos o incompletos.');
            }

            // -- Padding de Número de Documento --
            $numero_documento = $header['numero_documento'];
            if (is_numeric($numero_documento) && strlen($numero_documento) < 8) {
                $header['numero_documento'] = str_pad($numero_documento, 8, '0', STR_PAD_LEFT);
            }

            // -- Validación de Duplicados --
            $doc_id_to_check = !empty($header['id_documento']) ? $header['id_documento'] : null;
            $stmt_check = $pdo->prepare("CALL sp_check_documento_duplicado(?, ?, ?, ?, ?)");
            $stmt_check->execute([
                $header['id_tipo_documento'],
                $header['serie_documento'],
                $header['numero_documento'],
                $header['id_auxiliar'],
                $doc_id_to_check
            ]);
            $duplicate = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $stmt_check->closeCursor();

            if ($duplicate) {
                $message = "El documento " . htmlspecialchars($duplicate['serie_documento']) . "-" . htmlspecialchars($duplicate['numero_documento']) . " ya existe para este auxiliar.";
                throw new Exception($message);
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
                $stmt_update->execute([$doc_id, $header['id_tipo_documento'], $header['id_proyecto'], $header['id_sub_proyecto'], $header['id_auxiliar'], $header['serie_documento'], $header['numero_documento'], $header['fecha_emision'], $header['moneda'], $tc, $subtotal, $igv, $total, $total_soles, $total_dolares, $header['glosa'], $header['observaciones'] ?? null]);
                $stmt_delete_details = $pdo->prepare("CALL sp_delete_documento_detalle_by_id_documento(?)");
                $stmt_delete_details->execute([$doc_id]);
                $response['message'] = 'Documento actualizado con éxito.';
            } else { // create
                $stmt_create = $pdo->prepare("CALL sp_create_documento_header(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @new_id)");
                $stmt_create->execute([$header['id_tipo_documento'], $header['id_proyecto'], $header['id_sub_proyecto'], $header['id_auxiliar'], $_SESSION['user_id'], $header['serie_documento'], $header['numero_documento'], $header['fecha_emision'], $header['moneda'], $tc, $subtotal, $igv, $total, $total_soles, $total_dolares, $header['glosa'], $header['observaciones'] ?? null]);
                $stmt_create->closeCursor();
                $doc_id = $pdo->query("SELECT @new_id as new_id")->fetch(PDO::FETCH_ASSOC)['new_id'];
                if (!$doc_id) throw new Exception("No se pudo crear el encabezado del documento.");
                $response['message'] = 'Documento creado con éxito.';
            }

            // Insert new details for both create and update
            $stmt_insert_detail = $pdo->prepare("CALL sp_create_documento_detalle(?, ?, ?, ?, ?, ?, ?, ?, ?, @new_detalle_id)");
            $stmt_insert_distribucion = $pdo->prepare("CALL sp_create_documento_detalle_distribucion(?, ?, ?)");

            foreach ($details as $index => $item) {
                $item_total = (float)$item['cantidad'] * (float)$item['precio_unitario'];
                $item_total_soles = $is_soles ? $item_total : $item_total * $tc;
                $item_total_dolares = !$is_soles ? $item_total : $item_total / $tc;
                $descripcion = $item['descripcion'] ?? '';

                // 1. Insertar el detalle y obtener el nuevo ID
                $stmt_insert_detail->execute([
                    $doc_id,
                    $index + 1,
                    $item['cantidad'],
                    $descripcion,
                    $item['id_concepto'],
                    $item['precio_unitario'],
                    $item_total,
                    $item_total_soles,
                    $item_total_dolares
                ]);
                $stmt_insert_detail->closeCursor(); // Muy importante al usar OUT parameters
                $new_detalle_id = $pdo->query("SELECT @new_detalle_id as new_id")->fetch(PDO::FETCH_ASSOC)['new_id'];

                if (!$new_detalle_id) {
                    throw new Exception("No se pudo crear el item del detalle: " . ($descripcion ?: ('Item ' . $index + 1)));
                }

                // 2. Insertar la distribución para este detalle
                if (isset($item['distribucion']) && is_array($item['distribucion'])) {
                    $total_porcentaje = 0;
                    foreach($item['distribucion'] as $dist) {
                        $total_porcentaje += (float)$dist['porcentaje'];
                    }

                    // Validar que la suma de porcentajes sea (aproximadamente) 100
                    if (abs($total_porcentaje - 100) > 0.01 && count($item['distribucion']) > 0) {
                        throw new Exception("La suma de porcentajes para el item '".($descripcion ?: ($index + 1))."' no es 100%. Suma actual: $total_porcentaje%");
                    }

                    foreach ($item['distribucion'] as $distribucion_item) {
                        if (!empty($distribucion_item['id_centro_costo']) && !empty($distribucion_item['porcentaje'])) {
                            $stmt_insert_distribucion->execute([
                                $new_detalle_id,
                                $distribucion_item['id_centro_costo'],
                                $distribucion_item['porcentaje']
                            ]);
                        }
                    }
                } else {
                     // Opcional: Lanzar un error si no se proporciona distribución para un item
                     throw new Exception("No se proporcionó distribución de centro de costo para el item: " . ($descripcion ?: ('Item ' . $index + 1)));
                }
            }

            // -- Procesamiento de Archivos Adjuntos --
            if (isset($_FILES['adjuntos']) && is_array($_FILES['adjuntos']['name'])) {
                // 1. Crear directorio específico para el documento si no existe
                $doc_upload_dir_relative = 'uploads/documentos/' . $doc_id;
                $doc_upload_dir_absolute = realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR . $doc_upload_dir_relative;

                if (!is_dir($doc_upload_dir_absolute)) {
                    if (!mkdir($doc_upload_dir_absolute, 0775, true)) {
                        throw new Exception("No se pudo crear el directorio para los adjuntos del documento.");
                    }
                }

                foreach ($_FILES['adjuntos']['name'] as $key => $name) {
                    if ($_FILES['adjuntos']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['adjuntos']['tmp_name'][$key];
                        $original_file_name = basename($name);
                        $file_type = $_FILES['adjuntos']['type'][$key];
                        $file_size = $_FILES['adjuntos']['size'][$key];

                        // 2. Sanitizar nombre de archivo y manejar colisiones
                        $sanitized_file_name = preg_replace("/[^a-zA-Z0-9.\-_]/", "", pathinfo($original_file_name, PATHINFO_FILENAME));
                        $extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
                        $final_file_name = $sanitized_file_name . '.' . $extension;

                        $counter = 1;
                        while (file_exists($doc_upload_dir_absolute . DIRECTORY_SEPARATOR . $final_file_name)) {
                            $final_file_name = $sanitized_file_name . '-' . $counter . '.' . $extension;
                            $counter++;
                        }

                        $destination = $doc_upload_dir_absolute . DIRECTORY_SEPARATOR . $final_file_name;

                        // 3. Mover archivo y guardar en DB
                        if (move_uploaded_file($tmp_name, $destination)) {
                            // Establecer permisos para que el archivo sea legible por el servidor web
                            chmod($destination, 0644);

                            $stmt_adjunto = $pdo->prepare("CALL sp_create_documento_adjunto(?, ?, ?, ?, ?, ?)");
                            $storage_path_for_db = $doc_upload_dir_relative . '/';
                            $stmt_adjunto->execute([$doc_id, $original_file_name, $final_file_name, $storage_path_for_db, $file_type, $file_size]);
                        } else {
                            throw new Exception("No se pudo mover el archivo subido '" . htmlspecialchars($original_file_name) . "'. Verifique los permisos del servidor.");
                        }
                    } elseif ($_FILES['adjuntos']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception("Error al subir el archivo '" . htmlspecialchars($name) . "'. Código de error: " . $_FILES['adjuntos']['error'][$key]);
                    }
                }
            }
            break;

        case 'delete':
            if (!isset($_GET['id'])) throw new Exception("ID de documento no proporcionado para eliminar.");
            $doc_id = $_GET['id'];
            $stmt_delete = $pdo->prepare("CALL sp_delete_documento(?)");
            $stmt_delete->execute([$doc_id]);
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

echo json_encode($response);
?>
