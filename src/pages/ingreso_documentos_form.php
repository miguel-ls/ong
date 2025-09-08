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

    // Fetch data for dropdowns
    $stmt_tipos_doc = $pdo->prepare("CALL sp_read_tipos_documento_for_dropdown()");
    $stmt_tipos_doc->execute();
    $tipos_documento = $stmt_tipos_doc->fetchAll();
    $stmt_tipos_doc->closeCursor();

    $stmt_auxiliares = $pdo->prepare("CALL sp_read_auxiliares_for_dropdown()");
    $stmt_auxiliares->execute();
    $auxiliares = $stmt_auxiliares->fetchAll();
    $stmt_auxiliares->closeCursor();

    $stmt_conceptos = $pdo->prepare("CALL sp_read_conceptos_for_dropdown()");
    $stmt_conceptos->execute();
    $conceptos = $stmt_conceptos->fetchAll();
    $stmt_conceptos->closeCursor();

    // NOTE: Need SPs for proyectos and centros_costos as well.
    // For now, using the existing ones for simplicity in this phase.
    $stmt_proyectos = $pdo->prepare("CALL sp_read_proyectos_for_dropdown()");
    $stmt_proyectos->execute();
    $proyectos = $stmt_proyectos->fetchAll();
    $stmt_proyectos->closeCursor();

    $stmt_centros_costos = $pdo->prepare("CALL sp_read_all_centros_costos(null, null)");
    $stmt_centros_costos->execute();
    $centros_costos = $stmt_centros_costos->fetchAll();
    $stmt_centros_costos->closeCursor();


    if ($is_edit_mode) {
        // Fetch existing document data
        // NOTE: Logic for fetching and populating will be fully implemented in a later phase.
        $id_documento = $_GET['id'];
        // $stmt = $pdo->prepare("CALL sp_read_documento_by_id(?)");
        // $stmt->execute([$id_documento]);
        // $documento = $stmt->fetch();
    }

} catch (PDOException $e) {
    die("Error al preparar el formulario: " . $e->getMessage());
}
?>

<style>
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
    <form action="../src/actions/documentos_process.php" method="POST">
        <input type="hidden" name="id_documento" value="<?= htmlspecialchars($documento['id'] ?? '') ?>">

        <div class="form-section">
            <h2>Cabecera del Documento</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_tipo_documento">Tipo de Documento</label>
                    <select id="id_tipo_documento" name="id_tipo_documento" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($tipos_documento as $tipo): ?>
                            <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="fecha_emision">Fecha Emisión</label>
                    <input type="date" id="fecha_emision" name="fecha_emision" value="<?= htmlspecialchars($documento['fecha_emision'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="serie_documento">Serie</label>
                    <input type="text" id="serie_documento" name="serie_documento" value="<?= htmlspecialchars($documento['serie_documento'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="numero_documento">Número</label>
                    <input type="text" id="numero_documento" name="numero_documento" value="<?= htmlspecialchars($documento['numero_documento'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="id_auxiliar">Auxiliar (Proveedor/Cliente)</label>
                    <select id="id_auxiliar" name="id_auxiliar" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($auxiliares as $aux): ?>
                            <option value="<?= $aux['id'] ?>"><?= htmlspecialchars($aux['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="moneda">Moneda</label>
                    <select id="moneda" name="moneda" required>
                        <option value="SOLES">Soles (PEN)</option>
                        <option value="DOLARES">Dólares (USD)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_cambio">Tipo de Cambio (Venta)</label>
                    <input type="number" id="tipo_cambio" name="tipo_cambio" step="0.0001" value="<?= htmlspecialchars($documento['tipo_cambio'] ?? '1.0000') ?>" required>
                </div>
            </div>
             <div class="form-grid" style="margin-top: 20px;">
                 <div class="form-group">
                    <label for="id_proyecto">Proyecto</label>
                    <select id="id_proyecto" name="id_proyecto" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($proyectos as $proy): ?>
                            <option value="<?= $proy['id'] ?>"><?= htmlspecialchars($proy['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_centro_costo">Centro de Costo</label>
                    <select id="id_centro_costo" name="id_centro_costo" required>
                        <option value="">Seleccione...</option>
                         <?php foreach($centros_costos as $cc): ?>
                            <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="details-tbody">
                    <!-- Filas de detalle se añadirán aquí con JS en la Fase 4 -->
                    <tr>
                        <td>1</td>
                        <td><input type="number" name="detalle[0][cantidad]" value="1" step="0.01"></td>
                        <td><input type="text" name="detalle[0][descripcion]" value=""></td>
                        <td>
                            <select name="detalle[0][id_concepto]">
                                <option value="">Seleccione...</option>
                                <?php foreach($conceptos as $con): ?>
                                    <option value="<?= $con['id'] ?>"><?= htmlspecialchars($con['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="detalle[0][precio_unitario]" value="0.00" step="0.01"></td>
                        <td><input type="text" name="detalle[0][precio_total]" value="0.00" readonly></td>
                        <td><button type="button" class="btn btn-delete-row">X</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5">SUBTOTAL</td>
                        <td id="subtotal">0.00</td>
                        <td></td>
                    </tr>
                     <tr class="total-row">
                        <td colspan="5">IGV (18%)</td>
                        <td id="igv">0.00</td>
                        <td></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="5">TOTAL</td>
                        <td id="total">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <button type="button" id="btn-add-row" class="btn btn-add-row" style="margin-top: 10px;">Añadir Fila</button>
        </div>

        <div style="text-align: right;">
            <button type="submit" class="btn btn-save">Guardar Documento</button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailsTbody = document.getElementById('details-tbody');
    const btnAddRow = document.getElementById('btn-add-row');

    function calculateRowTotal(row) {
        const cantidad = parseFloat(row.querySelector('input[name*="[cantidad]"]').value) || 0;
        const precioUnitario = parseFloat(row.querySelector('input[name*="[precio_unitario]"]').value) || 0;
        const precioTotalInput = row.querySelector('input[name*="[precio_total]"]');

        const precioTotal = cantidad * precioUnitario;
        precioTotalInput.value = precioTotal.toFixed(2);

        calculateGrandTotals();
    }

    function calculateGrandTotals() {
        let subtotal = 0;
        detailsTbody.querySelectorAll('tr').forEach(row => {
            const precioTotal = parseFloat(row.querySelector('input[name*="[precio_total]"]').value) || 0;
            subtotal += precioTotal;
        });

        const igv = subtotal * 0.18;
        const total = subtotal + igv;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('igv').textContent = igv.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
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

    btnAddRow.addEventListener('click', function() {
        const newRow = detailsTbody.rows[0].cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => {
            if (input.type !== 'text') {
                input.value = input.name.includes('cantidad') ? '1' : '0.00';
            } else {
                input.value = '';
            }
        });
        newRow.querySelector('select').selectedIndex = 0;
        detailsTbody.appendChild(newRow);
        updateRowIndices();
    });

    detailsTbody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-delete-row')) {
            if (detailsTbody.rows.length > 1) {
                e.target.closest('tr').remove();
                updateRowIndices();
                calculateGrandTotals();
            } else {
                alert('No se puede eliminar la última fila.');
            }
        }
    });

    detailsTbody.addEventListener('input', function(e) {
        if (e.target && (e.target.name.includes('[cantidad]') || e.target.name.includes('[precio_unitario]'))) {
            const row = e.target.closest('tr');
            calculateRowTotal(row);
        }
    });

    // Initial calculation for pre-populated rows (in edit mode)
    detailsTbody.querySelectorAll('tr').forEach(calculateRowTotal);
});
</script>
