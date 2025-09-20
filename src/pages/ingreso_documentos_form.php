<?php
// Este archivo es cargado por public/index.php, que ya incluye el header.php.
// No incluir header.php aquí para evitar duplicar el HTML.
require_once __DIR__ . '/../database.php';

// Aunque el router principal (index.php) ya se encarga de la seguridad,
// añadimos una capa extra de protección para evitar el acceso directo.
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login si no hay sesión.
    // Usar una ruta absoluta o una lógica más robusta para la redirección.
    header('Location: /ong/public/login.php?error=Acceso no autorizado');
    exit();
}

$pdo = getDbConnection();
$is_edit = isset($_GET['id']);
$doc_id = $is_edit ? $_GET['id'] : null;

$header = null;
$details = [];
$adjuntos = []; // Initialize attachments array

if ($is_edit) {
    // Fetch header
    $stmt_header = $pdo->prepare("CALL sp_read_documento_header_by_id(?)");
    $stmt_header->execute([$doc_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);
    $stmt_header->closeCursor();

    // Fetch details
    $stmt_details = $pdo->prepare("CALL sp_read_documento_detalle_by_id(?)");
    $stmt_details->execute([$doc_id]);
    $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
    $stmt_details->closeCursor();

    // Fetch attachments
    $stmt_adjuntos = $pdo->prepare("CALL sp_read_adjuntos_by_documento_id(?)");
    $stmt_adjuntos->execute([$doc_id]);
    $adjuntos = $stmt_adjuntos->fetchAll(PDO::FETCH_ASSOC);
    $stmt_adjuntos->closeCursor();
}

// Fetch dropdown data
$proyectos = $pdo->query("CALL sp_read_proyectos_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);
$tipos_documento = $pdo->query("CALL sp_read_tipos_documento_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);
$auxiliares = $pdo->query("CALL sp_read_auxiliares_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);
// The concepts and centros_costo will now be loaded via AJAX based on the selected year.
$conceptos = [];
$centros_costo = [];

?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><?= $is_edit ? 'Editar' : 'Nuevo'; ?> Documento</h4>
        </div>
        <div class="card-body">
            <form id="documentoForm" enctype="multipart/form-data">
                <input type="hidden" name="id_documento" value="<?= htmlspecialchars($doc_id ?? '') ?>">

                <!-- Pestañas para Documento, Comentarios y Adjuntos -->
                <div class="mb-4">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="documento-tab" data-bs-toggle="tab" data-bs-target="#documento-tab-pane" type="button" role="tab" aria-controls="documento-tab-pane" aria-selected="true">Documento</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comentarios-tab" data-bs-toggle="tab" data-bs-target="#comentarios-tab-pane" type="button" role="tab" aria-controls="comentarios-tab-pane" aria-selected="false">Comentarios</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="adjuntos-tab" data-bs-toggle="tab" data-bs-target="#adjuntos-tab-pane" type="button" role="tab" aria-controls="adjuntos-tab-pane" aria-selected="false">Archivos Adjuntos</button>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-3" id="myTabContent">
                        <!-- Pestaña de Documento -->
                        <div class="tab-pane fade show active" id="documento-tab-pane" role="tabpanel" aria-labelledby="documento-tab" tabindex="0">
                            <fieldset class="border-0">
                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label for="id_tipo_documento" class="form-label">Tipo Documento</label>
                                        <select class="form-select" id="id_tipo_documento" name="id_tipo_documento" required>
                                            <?php foreach($tipos_documento as $tipo): ?>
                                            <option value="<?= $tipo['id'] ?>" <?= ($header && $header['id_tipo_documento'] == $tipo['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="serie_documento" class="form-label">Serie</label>
                                        <input type="text" class="form-control" id="serie_documento" name="serie_documento" value="<?= htmlspecialchars($header['serie_documento'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="numero_documento" class="form-label">Número</label>
                                        <input type="text" class="form-control" id="numero_documento" name="numero_documento" value="<?= htmlspecialchars($header['numero_documento'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="moneda" class="form-label">Moneda</label>
                                        <select class="form-select" id="moneda" name="moneda" required>
                                            <option value="SOLES" <?= ($header && $header['moneda'] == 'SOLES') ? 'selected' : '' ?>>Soles (S/)</option>
                                            <option value="DOLARES" <?= ($header && $header['moneda'] == 'DOLARES') ? 'selected' : '' ?>>Dólares ($)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="fecha_emision" class="form-label">Fecha Emisión</label>
                                        <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" value="<?= htmlspecialchars($header['fecha_emision'] ?? date('Y-m-d')) ?>" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="tipo_cambio" class="form-label">Tipo Cambio</label>
                                        <input type="number" step="0.0001" class="form-control" id="tipo_cambio" name="tipo_cambio" value="<?= htmlspecialchars($header['tipo_cambio'] ?? '1.0000') ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="id_auxiliar" class="form-label">Auxiliar</label>
                                        <select class="form-select" id="id_auxiliar" name="id_auxiliar" required>
                                             <option value="">Seleccione</option>
                                            <?php foreach($auxiliares as $aux): ?>
                                            <option value="<?= $aux['id'] ?>" <?= ($header && $header['id_auxiliar'] == $aux['id']) ? 'selected' : '' ?>><?= htmlspecialchars($aux['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                     <div class="col-md-4 mb-3">
                                        <label for="id_proyecto" class="form-label">Proyecto</label>
                                        <select class="form-select" id="id_proyecto" name="id_proyecto" required>
                                            <option value="">Seleccione</option>
                                            <?php foreach($proyectos as $proy): ?>
                                            <option value="<?= $proy['id'] ?>" <?= ($header && $header['id_proyecto'] == $proy['id']) ? 'selected' : '' ?>><?= htmlspecialchars($proy['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="id_sub_proyecto" class="form-label">Sub-Proyecto</label>
                                        <select class="form-select" id="id_sub_proyecto" name="id_sub_proyecto" required>
                                            <option value="">Seleccione un proyecto primero</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="id_centro_costo" class="form-label">Centro de Costo</label>
                                         <select class="form-select" id="id_centro_costo" name="id_centro_costo" required>
                                            <option value="">Seleccione</option>
                                        </select>
                                    </div>
                                </div>
                                 <div class="row">
                                     <div class="col-md-12 mb-3">
                                        <label for="glosa" class="form-label">Glosa</label>
                                        <input type="text" class="form-control" id="glosa" name="glosa" value="<?= htmlspecialchars($header['glosa'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <!-- Pestaña de Comentarios -->
                        <div class="tab-pane fade" id="comentarios-tab-pane" role="tabpanel" aria-labelledby="comentarios-tab" tabindex="0">
                            <div class="mb-3">
                                <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Añadir un comentario..." rows="3"><?= htmlspecialchars($header['observaciones'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <!-- Pestaña de Adjuntos -->
                        <div class="tab-pane fade" id="adjuntos-tab-pane" role="tabpanel" aria-labelledby="adjuntos-tab" tabindex="0">
                            <div class="mb-3">
                                <input type="file" class="form-control" id="adjuntos" name="adjuntos[]" multiple>
                            </div>
                            <?php if (!empty($adjuntos)): ?>
                                <div class="mb-3">
                                    <ul class="list-group" id="lista-adjuntos">
                                        <?php foreach ($adjuntos as $adjunto): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center" id="adjunto-<?= $adjunto['id'] ?>">
                                                <a href="../src/actions/download_attachment.php?id=<?= $adjunto['id'] ?>" target="_blank">
                                                    <?= htmlspecialchars($adjunto['nombre_original']) ?>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarAdjunto(<?= $adjunto['id'] ?>)">Eliminar</button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Detalle del Documento -->
                <fieldset class="border p-3 mb-4">
                    <!-- <legend class="w-auto px-2 h6">Detalle del Documento</legend> -->
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 10%;">Cant.</th>
                                <th style="width: 25%;">Concepto</th>
                                <th style="width: 20%;">Descripción</th>
                                <th style="width: 15%;">Dist. CC</th>
                                <th style="width: 10%;">P. Unit.</th>
                                <th style="width: 10%;">Total</th>
                                <th style="width: 5%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="detalleBody">
                            <!-- Filas de detalle se insertarán aquí -->
                        </tbody>
                    </table>
                    <button type="button" id="addRowBtn" class="btn btn-sm btn-success">Añadir Fila</button>
                </fieldset>

                <!-- Modal para Distribución de Centros de Costo -->
                <div class="modal fade" id="distribucionCCModal" tabindex="-1" aria-labelledby="distribucionCCModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="distribucionCCModalLabel">Distribuir Centro de Costo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60%;">Centro de Costo</th>
                                            <th style="width: 30%;">Porcentaje (%)</th>
                                            <th style="width: 10%;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="distribucionBody">
                                        <!-- Filas de distribución se insertarán aquí -->
                                    </tbody>
                                </table>
                                <button type="button" id="addDistribucionRowBtn" class="btn btn-sm btn-success">Añadir Centro de Costo</button>
                                <div class="mt-3">
                                    <strong>Total Porcentaje: <span id="totalPorcentaje">0.00</span>%</strong>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" id="saveDistribucionBtn" class="btn btn-primary">Guardar Distribución</button>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Totales -->
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th class="text-end">Subtotal:</th>
                                    <td class="text-end" id="subtotalDisplay">0.00</td>
                                </tr>
                                <tr>
                                    <th class="text-end">IGV (18%):</th>
                                    <td class="text-end" id="igvDisplay">0.00</td>
                                </tr>
                                <tr>
                                    <th class="text-end">Total:</th>
                                    <td class="text-end" id="totalDisplay">0.00</td>
                                </tr>
                                <tr id="totalDolaresRow">
                                    <th class="text-end">Total Dólares:</th>
                                    <td class="text-end" id="totalDolaresDisplay">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <a href="index.php?page=ingreso_documentos" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" id="submitBtn" class="btn btn-primary">Guardar Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Plantilla para la fila de detalle -->
<template id="detalleRowTemplate">
    <tr>
        <td class="item-number">1</td>
        <td><input type="number" class="form-control form-control-sm" name="cantidad" step="0.01" required></td>
        <td>
            <select class="form-select form-select-sm" name="id_concepto" required>
                <option value="">Seleccione</option>
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="descripcion"></td>
        <td>
            <button type="button" class="btn btn-sm btn-info distribucion-btn">Distribuir</button>
        </td>
        <td><input type="number" class="form-control form-control-sm" name="precio_unitario" step="0.0001" required></td>
        <td><input type="text" class="form-control form-control-sm total-row" name="precio_total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger removeRowBtn">X</button></td>
    </tr>
</template>

<template id="distribucionRowTemplate">
    <tr>
        <td>
            <select class="form-select form-select-sm" name="id_centro_costo_dist" required>
                <option value="">Seleccione un CC</option>
            </select>
        </td>
        <td><input type="number" class="form-control form-control-sm" name="porcentaje_dist" step="0.01" min="0" max="100" required></td>
        <td><button type="button" class="btn btn-sm btn-danger removeDistribucionRowBtn">X</button></td>
    </tr>
</template>

<script>
function eliminarAdjunto(idAdjunto) {
    if (!confirm('¿Está seguro de que desea eliminar este archivo adjunto? Esta acción no se puede deshacer.')) {
        return;
    }

    fetch(`../src/actions/adjuntos_process.php?action=delete&id=${idAdjunto}`, {
        method: 'GET', // O POST si se prefiere
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`adjunto-${idAdjunto}`);
            if (item) {
                item.remove();
            }
            alert(data.message);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al eliminar adjunto:', error);
        alert('Ocurrió un error de red al intentar eliminar el archivo.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // --- ELEMENT REFERENCES ---
    const form = document.getElementById('documentoForm');
    const detalleBody = document.getElementById('detalleBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const rowTemplate = document.getElementById('detalleRowTemplate');
    const distribucionRowTemplate = document.getElementById('distribucionRowTemplate');
    const headerCentroCostoSelect = document.getElementById('id_centro_costo');
    const proyectoSelect = document.getElementById('id_proyecto');
    const subProyectoSelect = document.getElementById('id_sub_proyecto');
    const fechaInput = document.getElementById('fecha_emision');
    const monedaSelect = document.getElementById('moneda');
    const tipoCambioInput = document.getElementById('tipo_cambio');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const igvDisplay = document.getElementById('igvDisplay');
    const totalDisplay = document.getElementById('totalDisplay');
    const totalDolaresDisplay = document.getElementById('totalDolaresDisplay');
    const totalDolaresRow = document.getElementById('totalDolaresRow');
    const templateConceptoSelect = rowTemplate.content.querySelector('[name="id_concepto"]');

    // Modal elements
    const distribucionModal = new bootstrap.Modal(document.getElementById('distribucionCCModal'));
    const distribucionBody = document.getElementById('distribucionBody');
    const addDistribucionRowBtn = document.getElementById('addDistribucionRowBtn');
    const saveDistribucionBtn = document.getElementById('saveDistribucionBtn');
    const totalPorcentajeSpan = document.getElementById('totalPorcentaje');
    let activeDetailRow = null; // To track which detail row is being edited

    // --- CONSTANTS AND STATE ---
    const IGV_RATE = 0.18;
    const isEditMode = <?= $is_edit ? 'true' : 'false' ?>;
    const initialDetails = <?= json_encode($details) ?>;
    const initialHeader = <?= json_encode($header) ?>;
    let centrosCostoOptions = []; // Cache for CC options

    // --- FUNCTIONS ---

    function addRow(detail = null) {
        const newRow = rowTemplate.content.cloneNode(true);
        const tr = newRow.querySelector('tr');

        // Store distribution data on the row itself
        // When loading in edit mode, detail.distribucion is a JSON string from the DB, so it must be parsed.
        const distribucionData = detail && detail.distribucion ? JSON.parse(detail.distribucion) : [];
        tr.dataset.distribucion = JSON.stringify(distribucionData);

        if (detail) {
            tr.querySelector('[name="cantidad"]').value = detail.cantidad;
            tr.querySelector('[name="precio_unitario"]').value = detail.precio_unitario;
            tr.querySelector('[name="descripcion"]').value = detail.descripcion || '';
            tr.querySelector('[name="id_concepto"]').value = detail.id_concepto;
        }

        detalleBody.appendChild(tr);
        updateItemNumbers();

        tr.querySelector('.removeRowBtn').addEventListener('click', () => {
            tr.remove();
            updateAllCalculations();
            updateItemNumbers();
        });

        tr.querySelectorAll('[name="cantidad"], [name="precio_unitario"]').forEach(input => {
            input.addEventListener('input', () => updateRowCalculations(tr));
        });

        tr.querySelector('.distribucion-btn').addEventListener('click', () => {
            openDistribucionModal(tr);
        });

        updateRowCalculations(tr);
    }

    function updateItemNumbers() {
        const rows = detalleBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelector('.item-number').textContent = index + 1;
        });
    }

    function updateRowCalculations(row) {
        const qty = parseFloat(row.querySelector('[name="cantidad"]').value) || 0;
        const price = parseFloat(row.querySelector('[name="precio_unitario"]').value) || 0;
        const total = qty * price;
        row.querySelector('.total-row').value = total.toFixed(4);
        updateAllCalculations();
    }

    function updateAllCalculations() {
        let subtotal = 0;
        detalleBody.querySelectorAll('tr').forEach(row => {
            subtotal += parseFloat(row.querySelector('.total-row').value) || 0;
        });

        const igv = subtotal * IGV_RATE;
        const total = subtotal + igv;
        const tc = parseFloat(tipoCambioInput.value) || 1;

        subtotalDisplay.textContent = subtotal.toFixed(2);
        igvDisplay.textContent = igv.toFixed(2);
        totalDisplay.textContent = total.toFixed(2);
        totalDolaresDisplay.textContent = (monedaSelect.value === 'SOLES' && tc > 0 ? total / tc : total).toFixed(2);
        updateTotalsVisibility();
    }

    function updateTotalsVisibility() {
        totalDolaresRow.style.display = (monedaSelect.value === 'SOLES') ? 'none' : 'table-row';
    }

    // --- Distribucion Modal Functions ---
    function openDistribucionModal(detailRow) {
        activeDetailRow = detailRow;
        distribucionBody.innerHTML = '';
        const distribucionData = JSON.parse(activeDetailRow.dataset.distribucion || '[]');
        if (distribucionData.length > 0) {
            distribucionData.forEach(dist => addDistribucionRow(dist));
        } else {
            addDistribucionRow(); // Add one empty row to start
        }
        updateTotalPorcentaje();
        distribucionModal.show();
    }

    function addDistribucionRow(dist = null) {
        const newRow = distribucionRowTemplate.content.cloneNode(true);
        const ccSelect = newRow.querySelector('[name="id_centro_costo_dist"]');
        updateSelectWithOptions(ccSelect, centrosCostoOptions, 'Seleccione un CC');

        if (dist) {
            ccSelect.value = dist.id_centro_costo;
            newRow.querySelector('[name="porcentaje_dist"]').value = dist.porcentaje;
        }

        newRow.querySelector('.removeDistribucionRowBtn').addEventListener('click', (e) => {
            e.target.closest('tr').remove();
            updateTotalPorcentaje();
        });

        newRow.querySelector('[name="porcentaje_dist"]').addEventListener('input', updateTotalPorcentaje);
        distribucionBody.appendChild(newRow);
    }

    function updateTotalPorcentaje() {
        let total = 0;
        distribucionBody.querySelectorAll('[name="porcentaje_dist"]').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        totalPorcentajeSpan.textContent = total.toFixed(2);
        totalPorcentajeSpan.style.color = Math.abs(total - 100) < 0.01 ? 'green' : 'red';
    }

    saveDistribucionBtn.addEventListener('click', () => {
        const total = parseFloat(totalPorcentajeSpan.textContent);
        if (Math.abs(total - 100) > 0.01) {
            alert('El porcentaje total debe ser exactamente 100%.');
            return;
        }

        const newDistribucionData = [];
        let isValid = true;
        distribucionBody.querySelectorAll('tr').forEach(row => {
            const ccId = row.querySelector('[name="id_centro_costo_dist"]').value;
            const porcentaje = row.querySelector('[name="porcentaje_dist"]').value;
            if (!ccId || !porcentaje || parseFloat(porcentaje) <= 0) {
                isValid = false;
            }
            newDistribucionData.push({ id_centro_costo: ccId, porcentaje: porcentaje });
        });

        if (!isValid) {
            alert('Por favor, complete todos los campos de la distribución con valores válidos.');
            return;
        }

        activeDetailRow.dataset.distribucion = JSON.stringify(newDistribucionData);
        distribucionModal.hide();
    });

    addDistribucionRowBtn.addEventListener('click', () => addDistribucionRow());

    // --- Data Fetching & Initialization ---
    function fetchSubProyectos(id_proyecto, selected_id = null) {
        subProyectoSelect.innerHTML = '<option value="">Cargando...</option>';
        if (!id_proyecto) {
            subProyectoSelect.innerHTML = '<option value="">Seleccione un proyecto</option>';
            return;
        }
        fetch(`../src/ajax/get_subproyectos.php?id_proyecto=${id_proyecto}`)
            .then(response => response.json())
            .then(data => {
                subProyectoSelect.innerHTML = '<option value="">Seleccione</option>' +
                    data.map(sub => `<option value="${sub.id}" ${selected_id == sub.id ? 'selected' : ''}>${htmlspecialchars(sub.nombre)}</option>`).join('');
            })
            .catch(error => {
                console.error('Error fetching subproyectos:', error);
                subProyectoSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    function fetchTipoCambio(fecha) {
        fetch(`../src/ajax/get_tipo_cambio.php?fecha=${fecha}`)
            .then(response => response.json())
            .then(data => {
                if (data.venta) {
                    tipoCambioInput.value = data.venta;
                    updateAllCalculations();
                }
            });
    }

    function htmlspecialchars(str) {
        if(typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    function updateSelectWithOptions(selectElement, options, placeholder = 'Seleccione', selectedValue = null) {
        const currentValue = selectedValue || selectElement.value;
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
        if (Array.isArray(options)) {
            options.forEach(opt => {
                const isSelected = opt.id == currentValue;
                selectElement.options[selectElement.options.length] = new Option(opt.nombre, opt.id, isSelected, isSelected);
            });
        }
        if (currentValue) selectElement.value = currentValue;
    }

    function loadDropdownData(year) {
        if (!year) return Promise.resolve();

        const fetchCentros = fetch(`../src/ajax/get_centros_costos_for_dropdown.php?año=${year}`)
            .then(response => response.ok ? response.json() : Promise.reject('Error de red'))
            .then(data => {
                centrosCostoOptions = data; // Cache CC options
                updateSelectWithOptions(headerCentroCostoSelect, data, 'Seleccione Centro de Costo');
            });

        const fetchConceptos = fetch(`../src/ajax/get_conceptos_for_dropdown.php?año=${year}`)
            .then(response => response.ok ? response.json() : Promise.reject('Error de red'))
            .then(data => {
                updateSelectWithOptions(templateConceptoSelect, data, 'Seleccione Concepto');
            });

        return Promise.all([fetchCentros, fetchConceptos]);
    }

    function initializeForm() {
        const fechaEmision = fechaInput.value;
        if (!fechaEmision) {
            if (!isEditMode) addRow();
            return;
        }

        const initialYear = new Date(fechaEmision + 'T00:00:00').getFullYear();

        loadDropdownData(initialYear).then(() => {
            // Populate concept dropdowns in existing rows before adding new ones
            const conceptSelects = document.querySelectorAll('[name="id_concepto"]');
            conceptSelects.forEach(select => {
                const currentValue = select.value;
                updateSelectWithOptions(select, templateConceptoSelect.options, 'Seleccione Concepto', currentValue);
            });

            if (isEditMode) {
                if (initialHeader.id_centro_costo) {
                    headerCentroCostoSelect.value = initialHeader.id_centro_costo;
                }
                if (initialHeader.id_proyecto) {
                    fetchSubProyectos(initialHeader.id_proyecto, initialHeader.id_sub_proyecto);
                }
                // Clear body before re-populating
                detalleBody.innerHTML = '';
                initialDetails.forEach(detail => addRow(detail));
            } else {
                addRow();
                fetchTipoCambio(fechaEmision);
            }
            updateAllCalculations();
        }).catch(error => {
            console.error("Error al inicializar el formulario:", error);
            alert("No se pudieron cargar los datos necesarios para el formulario.");
        });
    }

    // --- EVENT LISTENERS ---
    document.getElementById('numero_documento').addEventListener('blur', (e) => {
        const num = e.target.value;
        if (num && !isNaN(num) && num.length < 8) {
            e.target.value = num.padStart(8, '0');
        }
    });

    addRowBtn.addEventListener('click', () => addRow());
    monedaSelect.addEventListener('change', updateAllCalculations);
    tipoCambioInput.addEventListener('input', updateAllCalculations);
    proyectoSelect.addEventListener('change', (e) => fetchSubProyectos(e.target.value));

    fechaInput.addEventListener('change', function() {
        const date = this.value;
        if (date) {
            const year = new Date(date + 'T00:00:00').getFullYear();
            detalleBody.innerHTML = ''; // Clear existing rows
            loadDropdownData(year).then(() => {
                addRow();
                fetchTipoCambio(date);
                updateAllCalculations();
            });
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Guardando...';

        const submissionData = new FormData();
        const headerData = {};
        for (const element of form.elements) {
            if (element.name && element.type !== 'file' && !element.closest('#detalleBody') && !element.closest('#distribucionCCModal')) {
                headerData[element.name] = element.value;
            }
        }
        submissionData.append('header', JSON.stringify(headerData));

        const detailData = [];
        let formIsValid = true;
        detalleBody.querySelectorAll('tr').forEach(row => {
            const rowData = {};
            row.querySelectorAll('input, select').forEach(input => {
                if (input.name) rowData[input.name] = input.value;
            });

            const distribucion = JSON.parse(row.dataset.distribucion || '[]');
            if(distribucion.length === 0) {
                formIsValid = false;
            }
            rowData.distribucion = distribucion;
            detailData.push(rowData);
        });

        if (!formIsValid) {
            alert('Cada item del detalle debe tener una distribución de Centros de Costo definida. Haga clic en "Distribuir" para cada fila.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar Documento';
            return;
        }

        submissionData.append('details', JSON.stringify(detailData));

        const files = document.getElementById('adjuntos').files;
        for (let i = 0; i < files.length; i++) {
            submissionData.append('adjuntos[]', files[i]);
        }

        fetch('../src/actions/documentos_process.php', {
            method: 'POST',
            body: submissionData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `index.php?page=ingreso_documentos&success=${encodeURIComponent(data.message)}`;
            } else {
                alert('Error: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Documento';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error de red o de servidor.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar Documento';
        });
    });

    // --- INITIALIZATION ---
    new TomSelect('#id_auxiliar', { create: true, sortField: { field: 'text', direction: 'asc' } });
    initializeForm();
});
</script>
<?php
// Este archivo es cargado por public/index.php, que ya incluye el footer.php.
// No incluir footer.php aquí para evitar duplicar el HTML.
?>
