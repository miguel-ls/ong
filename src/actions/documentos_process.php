<?php
session_start();
require_once __DIR__ . '/../database.php';

function send_json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    send_json_response(['status' => 'error', 'message' => 'Acceso no autorizado. Sesión no iniciada.']);
}

$action = $_REQUEST['action'] ?? null;
$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_documento = $_POST['id_documento'] ?? null;
    $is_update = !empty($id_documento);

    // En modo de actualización, los campos clave no se envían porque están deshabilitados.
    // Necesitamos obtenerlos de la base de datos para la validación y el nuevo cálculo.
    if ($is_update) {
        $stmt = $pdo->prepare("CALL sp_read_documento_header_by_id(?)");
        $stmt->execute([$id_documento]);
        $existing_doc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $id_tipo_documento = $existing_doc['id_tipo_documento'];
        $serie_documento = $existing_doc['serie_documento'];
        $numero_documento = $existing_doc['numero_documento'];
        $id_auxiliar = $existing_doc['id_auxiliar'];
    } else {
        $id_tipo_documento = $_POST['id_tipo_documento'];
        $serie_documento = $_POST['serie_documento'];
        $numero_documento = $_POST['numero_documento'];
        $id_auxiliar = $_POST['id_auxiliar'];
    }

    $fecha_emision = $_POST['fecha_emision'];
    $moneda_principal = $_POST['moneda'];
    $tipo_cambio = (float)($_POST['tipo_cambio'] ?? 1.0);
    $id_proyecto = $_POST['id_proyecto'];
    $id_centro_costo = $_POST['id_centro_costo'];
    $id_sub_proyecto = $_POST['id_sub_proyecto'] ?? null;
    $glosa = $_POST['glosa'];
    $id_usuario_registro = $_SESSION['user_id'];
    $detalle_items = $_POST['detalle'] ?? [];

    if ($tipo_cambio <= 0) {
        send_json_response(['status' => 'error', 'message' => 'El tipo de cambio debe ser mayor a cero para poder guardar el documento.']);
    }
    if (empty($detalle_items)) {
        send_json_response(['status' => 'error', 'message' => 'El documento debe tener al menos un item en el detalle.']);
    }

    try {
        if (!$is_update) {
            $stmt_check = $pdo->prepare("CALL sp_check_documento_duplicado(?, ?, ?, ?)");
            $stmt_check->execute([$id_tipo_documento, $serie_documento, $numero_documento, $id_auxiliar]);
            $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $stmt_check->closeCursor();
            if ($result && $result['duplicate_count'] > 0) {
                send_json_response(['status' => 'error', 'message' => 'El documento con el mismo tipo, serie, número y auxiliar ya ha sido registrado.']);
            }
        }

        $subtotal_principal = 0;
        foreach ($detalle_items as &$item) {
            $cantidad = (float)($item['cantidad'] ?? 0);
            $precio_unitario = (float)($item['precio_unitario'] ?? 0);
            $item['precio_total'] = $cantidad * $precio_unitario;
            if ($moneda_principal === 'SOLES') {
                $item['total_soles'] = $item['precio_total'];
                $item['total_dolares'] = $tipo_cambio > 0 ? $item['precio_total'] / $tipo_cambio : 0;
            } else {
                $item['total_dolares'] = $item['precio_total'];
                $item['total_soles'] = $item['precio_total'] * $tipo_cambio;
            }
            $subtotal_principal += $item['precio_total'];
        }
        unset($item);
        $igv_principal = $subtotal_principal * 0.18;
        $total_principal = $subtotal_principal + $igv_principal;
        $total_soles_acumulado = array_sum(array_column($detalle_items, 'total_soles'));
        $total_dolares_acumulado = array_sum(array_column($detalle_items, 'total_dolares'));

        $pdo->beginTransaction();
        if ($is_update) {
            $stmt_header = $pdo->prepare("CALL sp_update_documento_header(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_header->execute([
                $id_documento, $id_tipo_documento, $id_proyecto, $id_sub_proyecto, $id_centro_costo, $id_auxiliar,
                $serie_documento, $numero_documento, $fecha_emision, $moneda_principal, $tipo_cambio,
                $subtotal_principal, $igv_principal, $total_principal,
                $total_soles_acumulado + ($total_soles_acumulado * 0.18),
                $total_dolares_acumulado + ($total_dolares_acumulado * 0.18),
                $glosa
            ]);
            $stmt_delete_detalle = $pdo->prepare("CALL sp_delete_documento_detalle_by_id_documento(?)");
            $stmt_delete_detalle->execute([$id_documento]);
        } else {
            $stmt_header = $pdo->prepare("CALL sp_create_documento_header(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @new_id)");
            $stmt_header->execute([
                $id_tipo_documento, $id_proyecto, $id_sub_proyecto, $id_centro_costo, $id_auxiliar, $id_usuario_registro,
                $serie_documento, $numero_documento, $fecha_emision, $moneda_principal, $tipo_cambio,
                $subtotal_principal, $igv_principal, $total_principal,
                $total_soles_acumulado + ($total_soles_acumulado * 0.18),
                $total_dolares_acumulado + ($total_dolares_acumulado * 0.18),
                $glosa
            ]);
            $stmt_header->closeCursor();
            $result = $pdo->query("SELECT @new_id AS new_id")->fetch(PDO::FETCH_ASSOC);
            $id_documento = $result['new_id'];
            if (!$id_documento) throw new Exception("No se pudo obtener el ID del nuevo documento.");
        }

        $stmt_detalle = $pdo->prepare("CALL sp_create_documento_detalle(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($detalle_items as $index => $item) {
            $stmt_detalle->execute([
                $id_documento, $index + 1, $item['cantidad'], $item['descripcion'], $item['id_concepto'],
                $item['precio_unitario'], $item['precio_total'], $item['total_soles'], $item['total_dolares']
            ]);
        }
        $pdo->commit();
        send_json_response(['status' => 'success', 'message' => 'Documento guardado con éxito.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        send_json_response(['status' => 'error', 'message' => "Error al guardar: " . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id_documento = $_GET['id'] ?? null;
    if ($id_documento) {
        try {
            $stmt = $pdo->prepare("CALL sp_delete_documento(?)");
            $stmt->execute([$id_documento]);
            header('Location: ../../public/index.php?page=ingreso_documentos&success=3');
        } catch (Exception $e) {
            header('Location: ../../public/index.php?page=ingreso_documentos&error=' . urlencode($e->getMessage()));
        }
    } else {
        header('Location: ../../public/index.php?page=ingreso_documentos&error=ID no proporcionado');
    }
    exit();
}
?>
