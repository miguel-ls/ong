<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación de Tipo de Cambio
    $tipo_cambio_val = isset($_POST['tipo_cambio']) ? (float)$_POST['tipo_cambio'] : 0;
    if ($tipo_cambio_val <= 0) {
        $id_documento = $_POST['id_documento'] ?? null;
        $redirect_url = '../../public/index.php?page=ingreso_documentos_form&error=tipo_cambio_zero';
        if (!empty($id_documento)) {
            $redirect_url .= '&id=' . $id_documento;
        }
        header('Location: ' . $redirect_url);
        exit();
    }

    // Lógica para CREATE y UPDATE
    // --- Obtener datos del formulario ---
    $id_tipo_documento = $_POST['id_tipo_documento'];
$fecha_emision = $_POST['fecha_emision'];
$serie_documento = $_POST['serie_documento'];
$numero_documento = $_POST['numero_documento'];
$id_auxiliar = $_POST['id_auxiliar'];
$moneda_principal = $_POST['moneda'];
$tipo_cambio = (float)($_POST['tipo_cambio'] ?? 1.0);
$id_proyecto = $_POST['id_proyecto'];
$id_centro_costo = $_POST['id_centro_costo'];
$id_sub_proyecto = $_POST['id_sub_proyecto'] ?? null;
$glosa = $_POST['glosa'];
$id_usuario_registro = $_SESSION['user_id'];
$detalle_items = $_POST['detalle'] ?? [];

// --- Recalcular todo en el backend para seguridad ---
$subtotal_principal = 0;
$total_soles_acumulado = 0;
$total_dolares_acumulado = 0;

foreach ($detalle_items as &$item) {
    $cantidad = (float)($item['cantidad'] ?? 0);
    $precio_unitario = (float)($item['precio_unitario'] ?? 0);
    $precio_total_item = $cantidad * $precio_unitario;

    $item['precio_total'] = $precio_total_item;

    if ($moneda_principal === 'SOLES') {
        $item['total_soles'] = $precio_total_item;
        $item['total_dolares'] = $tipo_cambio > 0 ? $precio_total_item / $tipo_cambio : 0;
    } else { // DOLARES
        $item['total_dolares'] = $precio_total_item;
        $item['total_soles'] = $precio_total_item * $tipo_cambio;
    }

    $subtotal_principal += $item['precio_total'];
    $total_soles_acumulado += $item['total_soles'];
    $total_dolares_acumulado += $item['total_dolares'];
}
unset($item); // Romper la referencia del bucle

$igv_principal = $subtotal_principal * 0.18;
$total_principal = $subtotal_principal + $igv_principal;

// --- Lógica de guardado con Transacción ---
$is_update = isset($_POST['id_documento']) && !empty($_POST['id_documento']);

try {
    $pdo->beginTransaction();

    if ($is_update) {
        // --- LÓGICA DE ACTUALIZACIÓN ---
        $id_documento = $_POST['id_documento'];

        // 1. Actualizar la cabecera
        $stmt_header = $pdo->prepare("CALL sp_update_documento_header(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_header->execute([
            $id_documento, $id_tipo_documento, $id_proyecto, $id_sub_proyecto, $id_centro_costo, $id_auxiliar,
            $serie_documento, $numero_documento, $fecha_emision, $moneda_principal, $tipo_cambio,
            $subtotal_principal, $igv_principal, $total_principal,
            $total_soles_acumulado + ($total_soles_acumulado * 0.18),
            $total_dolares_acumulado + ($total_dolares_acumulado * 0.18),
            $glosa
        ]);

        // 2. Borrar detalle antiguo
        $stmt_delete_detalle = $pdo->prepare("CALL sp_delete_documento_detalle_by_id_documento(?)");
        $stmt_delete_detalle->execute([$id_documento]);

        // 3. Insertar nuevo detalle
        $stmt_detalle = $pdo->prepare("CALL sp_create_documento_detalle(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($detalle_items as $index => $item) {
            $stmt_detalle->execute([
                $id_documento, $index + 1, $item['cantidad'], $item['descripcion'], $item['id_concepto'],
                $item['precio_unitario'], $item['precio_total'], $item['total_soles'], $item['total_dolares']
            ]);
        }

    } else {
        // --- LÓGICA DE CREACIÓN (EXISTENTE) ---
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

        if (!$id_documento) {
            throw new Exception("No se pudo obtener el ID del nuevo documento.");
        }

        $stmt_detalle = $pdo->prepare("CALL sp_create_documento_detalle(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($detalle_items as $index => $item) {
            $stmt_detalle->execute([
                $id_documento, $index + 1, $item['cantidad'], $item['descripcion'], $item['id_concepto'],
                $item['precio_unitario'], $item['precio_total'], $item['total_soles'], $item['total_dolares']
            ]);
        }
    }

    $pdo->commit();
    $success_code = $is_update ? 2 : 1;
    header('Location: ../../public/index.php?page=ingreso_documentos&success=' . $success_code);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = urlencode("Error al guardar: " . $e->getMessage());
    header('Location: ../../public/index.php?page=ingreso_documentos_form&error=' . $error_message);
    exit();
}
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    // Lógica para DELETE
    $id_documento = $_GET['id'] ?? null;
    if ($id_documento) {
        try {
            $stmt = $pdo->prepare("CALL sp_delete_documento(?)");
            $stmt->execute([$id_documento]);
            header('Location: ../../public/index.php?page=ingreso_documentos&success=3'); // 3 = deleted
        } catch (Exception $e) {
            header('Location: ../../public/index.php?page=ingreso_documentos&error=' . urlencode($e->getMessage()));
        }
    } else {
        header('Location: ../../public/index.php?page=ingreso_documentos&error=ID no proporcionado');
    }
    exit();
}
?>
