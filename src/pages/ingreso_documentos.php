<?php
require_once __DIR__ . '/../database.php';

// Obtener valores de filtro de la URL
$f_fecha_desde = $_GET['fecha_desde'] ?? null;
$f_fecha_hasta = $_GET['fecha_hasta'] ?? null;
$f_id_tipo_documento = $_GET['id_tipo_documento'] ?? null;
$f_serie_numero = $_GET['serie_numero'] ?? null;
$f_auxiliar = $_GET['auxiliar'] ?? null;
$f_id_centro_costo = $_GET['id_centro_costo'] ?? null;
$f_moneda = $_GET['moneda'] ?? null;

$documentos = [];
try {
    $pdo = getDbConnection();

    // Obtener datos para los dropdowns de los filtros
    $tipos_documento_list = $pdo->query("CALL sp_read_tipos_documento_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);
    $centros_costo_list = $pdo->query("CALL sp_read_centros_costos_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);

    // Llamar al SP con los filtros
    $stmt = $pdo->prepare("CALL sp_read_all_documentos(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        empty($f_fecha_desde) ? null : $f_fecha_desde,
        empty($f_fecha_hasta) ? null : $f_fecha_hasta,
        empty($f_id_tipo_documento) ? null : $f_id_tipo_documento,
        empty($f_serie_numero) ? null : $f_serie_numero,
        empty($f_auxiliar) ? null : $f_auxiliar,
        empty($f_id_centro_costo) ? null : $f_id_centro_costo,
        empty($f_moneda) ? null : $f_moneda
    ]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si el SP no existe, no es un error fatal, la tabla simplemente estará vacía.
    // En un futuro, se podría manejar este error de forma más explícita.
    if ($e->getCode() !== '42000') { // 42000 es el código para 'Syntax error or access violation'
      die("Error al obtener los documentos: " . $e->getMessage());
    } else {
      // Potentially handle the case where the old SP without filters is still in use
      $documentos = [];
    }
}
?>

<style>
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; }
    .table th { background-color: #004a99; color: white; }
    .table tr:nth-child(even) { background-color: #f2f2f2; }
    .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; }
    .btn-edit { background-color: #ffc107; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; display: inline-block; margin-bottom: 20px; }
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group input, .filter-form .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-clear { background-color: #6c757d; }
</style>

<header>
    <h1>Ingreso de Documentos</h1>
</header>
<section>
    <a href="index.php?page=ingreso_documentos_form" class="btn btn-add">Añadir Nuevo Documento</a>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="ingreso_documentos">

        <div class="form-group">
            <label for="fecha_desde">Fecha Desde</label>
            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($f_fecha_desde ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="fecha_hasta">Fecha Hasta</label>
            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($f_fecha_hasta ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="tipo_documento">Tipo Documento</label>
            <select id="tipo_documento" name="id_tipo_documento">
                <option value="">Todos</option>
                <?php foreach($tipos_documento_list as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= (($f_id_tipo_documento ?? null) == $tipo['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="serie_numero">Serie-Número</label>
            <input type="text" id="serie_numero" name="serie_numero" value="<?= htmlspecialchars($f_serie_numero ?? '') ?>" placeholder="Ej: F001-123">
        </div>
        <div class="form-group">
            <label for="auxiliar">Auxiliar</label>
            <input type="text" id="auxiliar" name="auxiliar" value="<?= htmlspecialchars($f_auxiliar ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="centro_costo">Centro de Costo</label>
            <select id="centro_costo" name="id_centro_costo">
                <option value="">Todos</option>
                 <?php foreach($centros_costo_list as $cc): ?>
                    <option value="<?= $cc['id'] ?>" <?= (($f_id_centro_costo ?? null) == $cc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cc['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="moneda">Moneda</label>
            <select id="moneda" name="moneda">
                <option value="">Todas</option>
                <option value="SOLES" <?= (($f_moneda ?? null) == 'SOLES') ? 'selected' : '' ?>>Soles</option>
                <option value="DOLARES" <?= (($f_moneda ?? null) == 'DOLARES') ? 'selected' : '' ?>>Dólares</option>
            </select>
        </div>

        <button type="submit" class="btn btn-filter">Filtrar</button>
        <a href="index.php?page=ingreso_documentos" class="btn btn-clear">Limpiar</a>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha Emisión</th>
                <th>Tipo Documento</th>
                <th>Serie y Número</th>
                <th>Auxiliar</th>
                <th>Centro de Costo</th>
                <th>Moneda</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documentos)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No hay documentos registrados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($documentos as $doc): ?>
                <tr>
                    <td><?= htmlspecialchars($doc['id']) ?></td>
                    <td><?= htmlspecialchars($doc['fecha_emision']) ?></td>
                    <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                    <td><?= htmlspecialchars($doc['serie_documento'] . '-' . $doc['numero_documento']) ?></td>
                    <td><?= htmlspecialchars($doc['auxiliar']) ?></td>
                    <td><?= htmlspecialchars($doc['centro_costo']) ?></td>
                    <td><?= htmlspecialchars($doc['moneda']) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format($doc['total'], 2)) ?></td>
                    <td>
                        <a href="index.php?page=ingreso_documentos_form&id=<?= $doc['id'] ?>" class="btn btn-edit">Editar</a>
                        <a href="../src/actions/documentos_process.php?action=delete&id=<?= $doc['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro de que quiere eliminar este documento?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
