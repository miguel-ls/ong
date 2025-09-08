<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/login.php');
    exit();
}

$pdo = getDbConnection();

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
try {
    $pdo->beginTransaction();

    // 1. Guardar la cabecera
    $stmt_header = $pdo->prepare("
        CALL sp_create_documento_header(
            :id_tipo_documento, :id_proyecto, :id_sub_proyecto, :id_centro_costo,
            :id_auxiliar, :id_usuario_registro, :serie_documento, :numero_documento,
            :fecha_emision, :moneda, :tipo_cambio, :subtotal, :igv, :total,
            :total_soles, :total_dolares, :glosa, @new_id
        )
    ");
    $stmt_header->execute([
        ':id_tipo_documento' => $id_tipo_documento,
        ':id_proyecto' => $id_proyecto,
        ':id_sub_proyecto' => $id_sub_proyecto,
        ':id_centro_costo' => $id_centro_costo,
        ':id_auxiliar' => $id_auxiliar,
        ':id_usuario_registro' => $id_usuario_registro,
        ':serie_documento' => $serie_documento,
        ':numero_documento' => $numero_documento,
        ':fecha_emision' => $fecha_emision,
        ':moneda' => $moneda_principal,
        ':tipo_cambio' => $tipo_cambio,
        ':subtotal' => $subtotal_principal,
        ':igv' => $igv_principal,
        ':total' => $total_principal,
        ':total_soles' => $total_soles_acumulado + ($total_soles_acumulado * 0.18),
        ':total_dolares' => $total_dolares_acumulado + ($total_dolares_acumulado * 0.18),
        ':glosa' => $glosa
    ]);
    $stmt_header->closeCursor();

    $result = $pdo->query("SELECT @new_id AS new_id")->fetch(PDO::FETCH_ASSOC);
    $id_documento = $result['new_id'];

    if (!$id_documento) {
        throw new Exception("No se pudo obtener el ID del nuevo documento.");
    }

    // 2. Guardar las líneas de detalle
    $stmt_detalle = $pdo->prepare("CALL sp_create_documento_detalle(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($detalle_items as $index => $item) {
        $stmt_detalle->execute([
            $id_documento,
            $index + 1,
            $item['cantidad'],
            $item['descripcion'],
            $item['id_concepto'],
            $item['precio_unitario'],
            $item['precio_total'],
            $item['total_soles'],
            $item['total_dolares']
        ]);
    }

    $pdo->commit();
    header('Location: ../../public/index.php?page=ingreso_documentos&success=1');
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = urlencode("Error al guardar: " . $e->getMessage());
    header('Location: ../../public/index.php?page=ingreso_documentos_form&error=' . $error_message);
    exit();
}
?>
