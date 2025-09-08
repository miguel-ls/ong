<?php
session_start();
require_once __DIR__ . '/../database.php';

// Verificar que el usuario esté logueado y que la solicitud sea POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/login.php');
    exit();
}

$pdo = getDbConnection();

// --- Obtener datos del formulario ---
// Cabecera
$id_tipo_documento = $_POST['id_tipo_documento'];
$fecha_emision = $_POST['fecha_emision'];
$serie_documento = $_POST['serie_documento'];
$numero_documento = $_POST['numero_documento'];
$id_auxiliar = $_POST['id_auxiliar'];
$moneda = $_POST['moneda'];
$tipo_cambio = $_POST['tipo_cambio'];
$id_proyecto = $_POST['id_proyecto'];
$id_centro_costo = $_POST['id_centro_costo'];
$glosa = $_POST['glosa'];
$id_usuario_registro = $_SESSION['user_id'];
// TODO: El subproyecto necesita ser añadido al formulario
$id_sub_proyecto = $_POST['id_sub_proyecto'] ?? null;

// Detalle (es un array)
$detalle = $_POST['detalle'] ?? [];

// --- Calcular totales desde el detalle ---
$subtotal = 0;
foreach ($detalle as $item) {
    $cantidad = (float)($item['cantidad'] ?? 0);
    $precio_unitario = (float)($item['precio_unitario'] ?? 0);
    $subtotal += $cantidad * $precio_unitario;
}
$igv = $subtotal * 0.18;
$total = $subtotal + $igv;


// --- Lógica de guardado con Transacción ---
try {
    $pdo->beginTransaction();

    // 1. Guardar la cabecera y obtener el nuevo ID
    $stmt_header = $pdo->prepare("
        CALL sp_create_documento_header(
            :id_tipo_documento, :id_proyecto, :id_sub_proyecto, :id_centro_costo,
            :id_auxiliar, :id_usuario_registro, :serie_documento, :numero_documento,
            :fecha_emision, :moneda, :tipo_cambio, :subtotal, :igv, :total, :glosa, @new_id
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
        ':moneda' => $moneda,
        ':tipo_cambio' => $tipo_cambio,
        ':subtotal' => $subtotal,
        ':igv' => $igv,
        ':total' => $total,
        ':glosa' => $glosa
    ]);
    $stmt_header->closeCursor();

    // Obtener el ID del documento recién creado
    $result = $pdo->query("SELECT @new_id AS new_id")->fetch(PDO::FETCH_ASSOC);
    $id_documento = $result['new_id'];

    if (!$id_documento) {
        throw new Exception("No se pudo obtener el ID del nuevo documento.");
    }

    // 2. Guardar las líneas de detalle
    $stmt_detalle = $pdo->prepare("
        CALL sp_create_documento_detalle(
            :id_documento, :item, :cantidad, :descripcion,
            :id_concepto, :precio_unitario, :precio_total
        )
    ");

    foreach ($detalle as $index => $item) {
        $cantidad = (float)($item['cantidad'] ?? 0);
        $precio_unitario = (float)($item['precio_unitario'] ?? 0);

        $stmt_detalle->execute([
            ':id_documento' => $id_documento,
            ':item' => $index + 1,
            ':cantidad' => $cantidad,
            ':descripcion' => $item['descripcion'],
            ':id_concepto' => $item['id_concepto'],
            ':precio_unitario' => $precio_unitario,
            ':precio_total' => $cantidad * $precio_unitario
        ]);
    }

    // 3. Si todo fue bien, confirmar la transacción
    $pdo->commit();

    // Redirigir a la lista con mensaje de éxito
    header('Location: ../../public/index.php?page=ingreso_documentos&success=1');
    exit();

} catch (Exception $e) {
    // Si algo falló, revertir la transacción
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Redirigir con mensaje de error
    // En una app real, sería bueno registrar el error $e->getMessage()
    header('Location: ../../public/index.php?page=ingreso_documentos_form&error=1');
    exit();
}
?>
