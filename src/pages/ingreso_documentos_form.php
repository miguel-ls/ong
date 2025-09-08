<?php
require_once __DIR__ . '/../database.php';

$is_edit_mode = isset($_GET['id']);
$documento = null;
$tipos_documento = [];
$auxiliares = [];
$conceptos = [];
$proyectos = [];
$centros_costos = [];

try {
    $pdo = getDbConnection();
    $stmt_tipos_doc = $pdo->prepare("CALL sp_read_tipos_documento_for_dropdown()");
    $stmt_tipos_doc->execute();
    $tipos_documento = $stmt_tipos_doc->fetchAll();
    $stmt_tipos_doc->closeCursor();

    $pdo = null; $pdo = getDbConnection();
    $stmt_auxiliares = $pdo->prepare("CALL sp_read_auxiliares_for_dropdown()");
    $stmt_auxiliares->execute();
    $auxiliares = $stmt_auxiliares->fetchAll();
    $stmt_auxiliares->closeCursor();

    $pdo = null; $pdo = getDbConnection();
    $stmt_conceptos = $pdo->prepare("CALL sp_read_conceptos_for_dropdown()");
    $stmt_conceptos->execute();
    $conceptos = $stmt_conceptos->fetchAll();
    $stmt_conceptos->closeCursor();

    $pdo = null; $pdo = getDbConnection();
    $stmt_proyectos = $pdo->prepare("CALL sp_read_proyectos_for_dropdown()");
    $stmt_proyectos->execute();
    $proyectos = $stmt_proyectos->fetchAll();
    $stmt_proyectos->closeCursor();

    $pdo = null; $pdo = getDbConnection();
    $stmt_centros_costos = $pdo->prepare("CALL sp_read_all_centros_costos(null, null)");
    $stmt_centros_costos->execute();
    $centros_costos = $stmt_centros_costos->fetchAll();
    $stmt_centros_costos->closeCursor();

    if ($is_edit_mode) {
        $pdo = null; $pdo = getDbConnection();
        $id_documento = $_GET['id'];
        $stmt_header = $pdo->prepare("CALL sp_read_documento_header_by_id(?)");
        $stmt_header->execute([$id_documento]);
        $documento = $stmt_header->fetch(PDO::FETCH_ASSOC);
        $stmt_header->closeCursor();
        $pdo = null; $pdo = getDbConnection();
        $stmt_detalle = $pdo->prepare("CALL sp_read_documento_detalle_by_id(?)");
        $stmt_detalle->execute([$id_documento]);
        $documento_detalle = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
        $stmt_detalle->closeCursor();
        $documento_data_json = json_encode(['header' => $documento, 'detail' => $documento_detalle]);
    }
} catch (PDOException $e) {
    die("Error al preparar el formulario: " . $e->getMessage());
}
?>

<style>
    .hidden-col { display: none !important; }
    .form-container { max-width: 1000px; margin: auto; }
    .form-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .form-section h2 { margin-top: 0; color: #004a99; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { margin-bottom: 5px; font-weight: bold; }
    .form-group input, .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .details-table th { background-color: #eef; }
    .details-table input, .details-table select { width: 100%; padding: 6px; box-sizing: border-box; }
    .total-row td { font-weight: bold; text-align: right; }
    .btn { padding: 10px 15px; border-radius: 4px; text-decoration: none; color: white; border: none; cursor: pointer; }
    .btn-save { background-color: #28a745; }
    .btn-add-row { background-color: #007bff; }
    .btn-delete-row { background-color: #dc3545; }
</style>

<header>
    <h1><?= $is_edit_mode ? 'Editar' : 'Nuevo' ?> Documento</h1>
</header>

<section class="form-container">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'tipo_cambio_zero'): ?>
        <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">
            <strong>Error:</strong> El tipo de cambio debe ser mayor a cero para poder guardar el documento.
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">
            <strong>Error al guardar el documento:</strong><br>
            <?= htmlspecialchars(urldecode($_GET['error'])) ?>
        </div>
    <?php endif; ?>
    <form action="../src/actions/documentos_process.php" method="POST">
        <input type="hidden" name="id_documento" value="<?= htmlspecialchars($documento['id'] ?? '') ?>">

        <div class="form-section">
            <h2>Cabecera del Documento</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_tipo_documento">Tipo de Documento</label>
                    <select id="id_tipo_documento" name="id_tipo_documento" required <?= $is_edit_mode ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach($tipos_documento as $tipo): ?>
                            <option value="<?= $tipo['id'] ?>" <?= (isset($documento['id_tipo_documento']) && $documento['id_tipo_documento'] == $tipo['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="fecha_emision">Fecha Emisión</label>
                    <input type="date" id="fecha_emision" name="fecha_emision" value="<?= htmlspecialchars($documento['fecha_emision'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="serie_documento">Serie</label>
                    <input type="text" id="serie_documento" name="serie_documento" value="<?= htmlspecialchars($documento['serie_documento'] ?? '') ?>" maxlength="4" <?= $is_edit_mode ? 'disabled' : '' ?>>
                </div>
                <div class="form-group">
                    <label for="numero_documento">Número</label>
                    <input type="text" id="numero_documento" name="numero_documento" value="<?= htmlspecialchars($documento['numero_documento'] ?? '') ?>" required <?= $is_edit_mode ? 'disabled' : '' ?>>
                </div>
                 <div class="form-group">
                    <label for="moneda">Moneda</label>
                    <select id="moneda" name="moneda" required>
                        <option value="SOLES" <?= (isset($documento['moneda']) && $documento['moneda'] == 'SOLES') ? 'selected' : '' ?>>Soles (PEN)</option>
                        <option value="DOLARES" <?= (isset($documento['moneda']) && $documento['moneda'] == 'DOLARES') ? 'selected' : '' ?>>Dólares (USD)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_cambio">Tipo de Cambio (Venta)</label>
                    <div style="display: flex; align-items: center;">
                        <input type="number" id="tipo_cambio" name="tipo_cambio" step="0.0001" value="<?= htmlspecialchars($documento['tipo_cambio'] ?? '1.0000') ?>" required style="flex-grow: 1;">
                        <button type="button" id="btn-refresh-tc" class="btn-refresh" style="margin-left: 5px; height: 38px;">&#x21bb;</button>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label for="id_auxiliar">Auxiliar (Proveedor/Cliente)</label>
                <div style="display: flex; align-items: center;">
                    <select id="id_auxiliar" name="id_auxiliar" required style="flex-grow: 1;" <?= $is_edit_mode ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach($auxiliares as $aux): ?>
                            <option value="<?= $aux['id'] ?>" <?= (isset($documento['id_auxiliar']) && $documento['id_auxiliar'] == $aux['id']) ? 'selected' : '' ?>><?= htmlspecialchars($aux['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-refresh" data-target="id_auxiliar" data-source="../src/ajax/get_auxiliares.php" style="margin-left: 5px; height: 38px;">&#x21bb;</button>
                </div>
            </div>

             <div class="form-grid" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="id_proyecto">Proyecto</label>
                    <div style="display: flex; align-items: center;">
                        <select id="id_proyecto" name="id_proyecto" required style="flex-grow: 1;">
                            <option value="">Seleccione...</option>
                            <?php foreach($proyectos as $proy): ?>
                                <option value="<?= $proy['id'] ?>" <?= (isset($documento['id_proyecto']) && $documento['id_proyecto'] == $proy['id']) ? 'selected' : '' ?>><?= htmlspecialchars($proy['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-refresh" data-target="id_proyecto" data-source="../src/ajax/get_proyectos.php" style="margin-left: 5px; height: 38px;">&#x21bb;</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="id_sub_proyecto">Sub Proyecto</label>
                    <div style="display: flex; align-items: center;">
                        <select id="id_sub_proyecto" name="id_sub_proyecto" style="flex-grow: 1;">
                            <option value="">Seleccione un proyecto primero</option>
                        </select>
                         <button type="button" class="btn-refresh" data-target="id_sub_proyecto" data-source="../src/ajax/get_subproyectos.php" style="margin-left: 5px; height: 38px;">&#x21bb;</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="id_centro_costo">Centro de Costo</label>
                    <div style="display: flex; align-items: center;">
                        <select id="id_centro_costo" name="id_centro_costo" required style="flex-grow: 1;">
                            <option value="">Seleccione...</option>
                             <?php foreach($centros_costos as $cc): ?>
                                <option value="<?= $cc['id'] ?>" <?= (isset($documento['id_centro_costo']) && $documento['id_centro_costo'] == $cc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cc['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-refresh" data-target="id_centro_costo" data-source="../src/ajax/get_centros_costos.php" style="margin-left: 5px; height: 38px;">&#x21bb;</button>
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <label for="glosa">Glosa / Descripción General</label>
                <input type="text" id="glosa" name="glosa" value="<?= htmlspecialchars($documento['glosa'] ?? '') ?>">
            </div>
        </div>

        <div class="form-section">
            <h2>Detalle del Documento</h2>
            <table class="details-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">Item</th>
                        <th style="width: 10%;">Cantidad</th>
                        <th style="width: 35%;">Descripción</th>
                        <th style="width: 25%;">Concepto</th>
                        <th style="width: 10%;">P. Unitario</th>
                        <th style="width: 10%;">Total</th>
                        <th style="width: 10%;" class="col-soles">Total Soles</th>
                        <th style="width: 10%;" class="col-dolares">Total Dolares</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="details-tbody">
                    <!-- Filas de detalle se añadirán aquí con JS -->
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5">SUBTOTAL</td>
                        <td id="subtotal">0.00</td>
                        <td id="subtotal_soles" class="col-soles">0.00</td>
                        <td id="subtotal_dolares" class="col-dolares">0.00</td>
                        <td></td>
                    </tr>
                     <tr class="total-row">
                        <td colspan="5">IGV (18%)</td>
                        <td id="igv">0.00</td>
                        <td id="igv_soles" class="col-soles">0.00</td>
                        <td id="igv_dolares" class="col-dolares">0.00</td>
                        <td></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="5">TOTAL</td>
                        <td id="total">0.00</td>
                        <td id="total_soles" class="col-soles">0.00</td>
                        <td id="total_dolares" class="col-dolares">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <button type="button" id="btn-add-row" class="btn btn-add-row" style="margin-top: 10px;">Añadir Fila</button>
        </div>

        <div style="text-align: right;">
            <a href="index.php?page=ingreso_documentos" class="btn" style="background-color: #6c757d; margin-right: 10px;">Cancelar</a>
            <button type="submit" class="btn btn-save">Guardar Documento</button>
        </div>
    </form>
</section>

<script>
// Poner los datos del documento (en modo edición) y de los combos en variables globales de JS
const isEditMode = <?= json_encode($is_edit_mode) ?>;
const documentoData = <?= $documento_data_json ?? 'null' ?>;
const conceptosData = <?= json_encode($conceptos) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const numeroDocumentoInput = document.getElementById('numero_documento');
    const detailsTbody = document.getElementById('details-tbody');
    const btnAddRow = document.getElementById('btn-add-row');

    numeroDocumentoInput.addEventListener('blur', function() {
        let value = this.value.trim();
        if (value.length > 0 && value.length < 8) {
            this.value = value.padStart(8, '0');
        }
    });

    function calculateAll() {
        const moneda = document.getElementById('moneda').value;
        const tipoCambio = parseFloat(document.getElementById('tipo_cambio').value) || 1.0;
        let grandSubtotal = 0;
        detailsTbody.querySelectorAll('tr').forEach(row => {
            const cantidad = parseFloat(row.querySelector('input[name*="[cantidad]"]').value) || 0;
            const precioUnitario = parseFloat(row.querySelector('input[name*="[precio_unitario]"]').value) || 0;
            const precioTotal = cantidad * precioUnitario;
            let totalSoles, totalDolares;
            if (moneda === 'SOLES') {
                totalSoles = precioTotal;
                totalDolares = tipoCambio > 0 ? precioTotal / tipoCambio : 0;
            } else {
                totalDolares = precioTotal;
                totalSoles = precioTotal * tipoCambio;
            }
            row.querySelector('input[name*="[precio_total]"]').value = precioTotal.toFixed(2);
            row.querySelector('input[name*="[total_soles]"]').value = totalSoles.toFixed(2);
            row.querySelector('input[name*="[total_dolares]"]').value = totalDolares.toFixed(2);
            grandSubtotal += precioTotal;
        });
        const grandIgv = grandSubtotal * 0.18;
        const grandTotal = grandSubtotal + grandIgv;
        const grandTotalSoles = (moneda === 'SOLES') ? grandTotal : grandTotal * tipoCambio;
        const grandTotalDolares = (moneda === 'DOLARES') ? grandTotal : grandTotal / tipoCambio;
        document.getElementById('subtotal').textContent = grandSubtotal.toFixed(2);
        document.getElementById('igv').textContent = grandIgv.toFixed(2);
        document.getElementById('total').textContent = grandTotal.toFixed(2);
        document.getElementById('total_soles').textContent = grandTotalSoles.toFixed(2);
        document.getElementById('total_dolares').textContent = grandTotalDolares.toFixed(2);
    }

    function updateRowIndices() {
        detailsTbody.querySelectorAll('tr').forEach((row, index) => {
            row.querySelector('td:first-child').textContent = index + 1;
            row.querySelectorAll('input, select').forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                }
            });
        });
    }

    function createNewDetailRow(item = null) {
        const index = detailsTbody.rows.length;
        const newRow = detailsTbody.insertRow();
        const conceptoSelect = document.createElement('select');
        conceptoSelect.name = `detalle[${index}][id_concepto]`;
        let optionsHtml = '<option value="">Seleccione...</option>';
        conceptosData.forEach(con => {
            const isSelected = item && con.id == item.id_concepto ? 'selected' : '';
            optionsHtml += `<option value="${con.id}" ${isSelected}>${escapeHTML(con.nombre)}</option>`;
        });
        conceptoSelect.innerHTML = optionsHtml;

        newRow.innerHTML = `
            <td>${index + 1}</td>
            <td><input type="number" name="detalle[${index}][cantidad]" value="${item ? item.cantidad : '1.00'}" step="0.01" class="row-input"></td>
            <td><input type="text" name="detalle[${index}][descripcion]" value="${item ? escapeHTML(item.descripcion) : ''}"></td>
            <td class="concepto-cell"></td>
            <td><input type="number" name="detalle[${index}][precio_unitario]" value="${item ? item.precio_unitario : '0.00'}" step="0.01" class="row-input"></td>
            <td><input type="text" name="detalle[${index}][precio_total]" value="${item ? item.precio_total : '0.00'}" readonly></td>
            <td class="col-soles"><input type="text" name="detalle[${index}][total_soles]" value="${item ? item.total_soles : '0.00'}" readonly></td>
            <td class="col-dolares"><input type="text" name="detalle[${index}][total_dolares]" value="${item ? item.total_dolares : '0.00'}" readonly></td>
            <td><button type="button" class="btn btn-delete-row">X</button></td>
        `;
        newRow.querySelector('.concepto-cell').appendChild(conceptoSelect);
    }

    btnAddRow.addEventListener('click', () => createNewDetailRow());

    detailsTbody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-delete-row')) {
            if (detailsTbody.rows.length > 1) {
                e.target.closest('tr').remove();
                updateRowIndices();
                calculateAll();
            } else {
                alert('No se puede eliminar la última fila.');
            }
        }
    });

    document.querySelector('.form-container').addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('row-input') || e.target.id === 'tipo_cambio' || e.target.id === 'moneda')) {
            calculateAll();
        }
    });

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.textContent = str;
        return p.innerHTML;
    }

    const monedaSelect = document.getElementById('moneda');
    const proyectoSelect = document.getElementById('id_proyecto');
    const subproyectoSelect = document.getElementById('id_sub_proyecto');

    function toggleCurrencyColumns() {
        // ... (lógica existente)
    }

    async function loadAndSelectSubProyecto() {
        // ... (lógica existente)
    }

    const fechaEmisionInput = document.getElementById('fecha_emision');
    const tipoCambioInput = document.getElementById('tipo_cambio');
    const btnRefreshTC = document.getElementById('btn-refresh-tc');

    async function fetchAndSetTipoCambio() {
        const fecha = fechaEmisionInput.value;
        if (!fecha) return;
        try {
            const response = await fetch(`../src/ajax/get_tipo_cambio.php?fecha=${fecha}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            const data = await response.json();
            if (data && data.venta !== undefined) {
                tipoCambioInput.value = parseFloat(data.venta).toFixed(4);
                tipoCambioInput.dispatchEvent(new Event('input', { bubbles: true }));
            } else {
                tipoCambioInput.value = (0.0).toFixed(4);
                tipoCambioInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } catch (error) {
            console.error('Error al obtener el tipo de cambio:', error);
        }
    }

    monedaSelect.addEventListener('change', toggleCurrencyColumns);
    proyectoSelect.addEventListener('change', loadAndSelectSubProyecto);
    btnRefreshTC.addEventListener('click', fetchAndSetTipoCambio);
    fechaEmisionInput.addEventListener('change', fetchAndSetTipoCambio);

    if (isEditMode && documentoData) {
        documentoData.detail.forEach(item => createNewDetailRow(item));
        loadAndSelectSubProyecto();
        calculateAll();
        toggleCurrencyColumns();
    } else {
        createNewDetailRow();
        toggleCurrencyColumns();
        fetchAndSetTipoCambio();
    }

    // ... (lógica de envío AJAX existente) ...
});
</script>
