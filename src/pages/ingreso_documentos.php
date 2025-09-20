<?php
require_once __DIR__ . '/../database.php';

// --- Lógica de Paginación y Filtros ---
$page_size = 10;
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Obtener valores de filtro de la URL
$f_anio = $_GET['anio'] ?? null;
$f_fecha_desde = $_GET['fecha_desde'] ?? null;
$f_fecha_hasta = $_GET['fecha_hasta'] ?? null;
$f_id_tipo_documento = $_GET['id_tipo_documento'] ?? null;
$f_serie_numero = $_GET['serie_numero'] ?? null;
$f_auxiliar = $_GET['auxiliar'] ?? null;
$f_moneda = $_GET['moneda'] ?? null;

// Construir la cadena de consulta para la paginación
$query_params = $_GET;
unset($query_params['p']);
$filter_query_string = http_build_query($query_params);


$documentos = [];
$total_records = 0;
$total_pages = 0;

try {
    $pdo = getDbConnection();

    // Obtener datos para los dropdowns de los filtros
    $tipos_documento_list = $pdo->query("CALL sp_read_tipos_documento_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);
    $centros_costo_list = $pdo->query("CALL sp_read_centros_costos_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);
    $years_list = $pdo->query("CALL sp_get_years_with_documents()")->fetchAll(PDO::FETCH_ASSOC);

    // Llamar al SP con los filtros y la paginación
    $stmt = $pdo->prepare("CALL sp_read_all_documentos(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $params = [
        empty($f_anio) ? null : $f_anio,
        empty($f_fecha_desde) ? null : $f_fecha_desde,
        empty($f_fecha_hasta) ? null : $f_fecha_hasta,
        empty($f_id_tipo_documento) ? null : $f_id_tipo_documento,
        empty($f_serie_numero) ? null : $f_serie_numero,
        empty($f_auxiliar) ? null : $f_auxiliar,
        empty($f_moneda) ? null : $f_moneda,
        $page_size,
        $current_page
    ];
    $stmt->execute($params);

    $total_records = (int) $stmt->fetchColumn();
    $stmt->nextRowset();
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($total_records > 0) {
        $total_pages = ceil($total_records / $page_size);
    }

} catch (PDOException $e) {
    if ($e->getCode() !== '42000') {
      die("Error al obtener los documentos: " . $e->getMessage());
    } else {
      $documentos = [];
    }
}
?>

<style>
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; vertical-align: middle; }
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
    .col-acciones { white-space: nowrap; }
</style>

<header>
    <h1>Ingreso de Documentos</h1>
</header>
<section>
    <a href="index.php?page=ingreso_documentos_form" class="btn btn-add">Añadir Nuevo Documento</a>

    <form action="index.php" method="GET" class="filter-form" id="filter-form">
        <input type="hidden" name="page" value="ingreso_documentos">
        <div class="form-group">
            <label for="anio">Año</label>
            <select id="anio" name="anio">
                <option value="">Todos</option>
                <?php foreach($years_list as $year): ?>
                    <option value="<?= $year['anio'] ?>" <?= (($f_anio ?? null) == $year['anio']) ? 'selected' : '' ?>><?= htmlspecialchars($year['anio']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
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
                <th><i class="fas fa-paperclip"></i></th>
                <th>Fecha Emisión</th>
                <th>Tipo Documento</th>
                <th>Serie y Número</th>
                <th>Auxiliar</th>
                <th>Centro de Costo</th>
                <th>Moneda</th>
                <th>Total</th>
                <th style="width: 90px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documentos)): ?>
                <tr>
                    <td colspan="10" style="text-align: center;">No hay documentos que coincidan con los filtros.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($documentos as $doc): ?>
                <tr>
                    <td><?= htmlspecialchars($doc['id']) ?></td>
                    <td class="text-center">
                        <?php if ($doc['tiene_adjuntos']): ?>
                            <i class="fas fa-paperclip text-secondary"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($doc['fecha_emision']) ?></td>
                    <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                    <td><?= htmlspecialchars($doc['serie_documento'] . '-' . $doc['numero_documento']) ?></td>
                    <td><?= htmlspecialchars($doc['auxiliar']) ?></td>
                    <td><?= htmlspecialchars($doc['centro_costo']) ?></td>
                    <td><?= htmlspecialchars($doc['moneda']) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format($doc['total'], 2)) ?></td>
                    <td class="col-acciones">
                        <a href="index.php?page=ingreso_documentos_form&id=<?= $doc['id'] ?>" class="btn btn-sm btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="../src/actions/documentos_process.php?action=delete&id=<?= $doc['id'] ?>" class="btn btn-sm btn-delete" title="Eliminar" onclick="return confirm('¿Está seguro de que quiere eliminar este documento?');"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Controles de Paginación -->
    <nav aria-label="Paginación de documentos">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=ingreso_documentos&p=<?= $current_page - 1 ?>&<?= $filter_query_string ?>">Anterior</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>" aria-current="page">
                    <a class="page-link" href="index.php?page=ingreso_documentos&p=<?= $i ?>&<?= $filter_query_string ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=ingreso_documentos&p=<?= $current_page + 1 ?>&<?= $filter_query_string ?>">Siguiente</a>
                </li>
            <?php else: ?>
                 <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Siguiente</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const anioSelect = document.getElementById('anio');
    const fechaDesdeInput = document.getElementById('fecha_desde');
    const fechaHastaInput = document.getElementById('fecha_hasta');

    anioSelect.addEventListener('change', function() {
        const selectedYear = this.value;
        if (selectedYear) {
            fechaDesdeInput.value = `${selectedYear}-01-01`;
            fechaHastaInput.value = `${selectedYear}-12-31`;
        } else {
            fechaDesdeInput.value = '';
            fechaHastaInput.value = '';
        }
    });
});
</script>
