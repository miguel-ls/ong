<?php
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$pdo = getDbConnection();
$item = null;
$is_edit = false;

// Repopulate form from session data if it exists (e.g., after validation error)
$form_data = $_SESSION['form_data'] ?? null;
if ($form_data) {
    unset($_SESSION['form_data']);
}

try {
    if (isset($_GET['id'])) {
        $is_edit = true;
        $item_id = $_GET['id'];
        $stmt = $pdo->prepare("CALL sp_read_auxiliar_by_id(?)");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        while ($stmt->nextRowset());
        $stmt->closeCursor();
    }

    $stmt_tipos = $pdo->prepare("SELECT id, nombre FROM tipos_auxiliar WHERE estado = 1 ORDER BY nombre");
    $stmt_tipos->execute();
    $tipos_auxiliar = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
    $stmt_tipos->closeCursor();

    $stmt_docs = $pdo->prepare("SELECT id, nombre, codigo FROM tipos_documento_identidad WHERE estado = 1 ORDER BY nombre");
    $stmt_docs->execute();
    $tipos_doc_identidad = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
    $stmt_docs->closeCursor();

} catch (PDOException $e) {
    die("Error fatal al cargar datos del formulario: " . $e->getMessage());
}
?>

<style>
    .form-container { max-width: 800px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .form-row { display: flex; gap: 20px; align-items: flex-end; }
    .form-row .form-group { flex: 1; }
    .input-with-button { display: flex; gap: 5px; }
    .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; }
    .btn-sunat { background-color: #ffc107; color: black; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; }
    .btn-cancel { background-color: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
    .form-actions { text-align: right; margin-top: 20px; }
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }

    /* --- START MODAL FIX --- */
    .modal {
        position: fixed; top: 0; left: 0; z-index: 1050; display: none;
        width: 100%; height: 100%; overflow: auto; outline: 0; background-color: rgba(0,0,0,0.5);
    }
    .modal.show { display: block; }
    .modal-dialog { position: relative; margin: 1.75rem auto; max-width: 500px; }
    .modal-content { background-color: #fff; border: 1px solid rgba(0,0,0,.2); border-radius: .3rem; padding: 1rem; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #dee2e6; padding-bottom: .5rem; margin-bottom: 1rem; }
    .modal-footer { display: flex; justify-content: flex-end; gap: .5rem; border-top: 1px solid #dee2e6; padding-top: .5rem; margin-top: 1rem; }
    .btn-close { cursor: pointer; background: transparent; border: 0; font-size: 1.5rem; }
    /* --- END MODAL FIX --- */
</style>

<header>
    <h1><?= $is_edit ? 'Editar' : 'Nuevo'; ?> Auxiliar</h1>
</header>

<section class="form-container">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form id="auxiliarForm" action="../src/actions/auxiliares_process.php" method="post">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>
        <!-- Form fields... -->
        <div class="form-row">
            <div class="form-group">
                <label for="id_tipo_auxiliar">Tipo de Auxiliar</label>
                <select id="id_tipo_auxiliar" name="id_tipo_auxiliar">
                    <option value="">Seleccione un tipo</option>
                    <?php
                        $selected_tipo_auxiliar = $form_data['id_tipo_auxiliar'] ?? $item['id_tipo_auxiliar'] ?? null;
                        foreach ($tipos_auxiliar as $tipo):
                    ?>
                        <option value="<?= $tipo['id'] ?>" <?= ($tipo['id'] == $selected_tipo_auxiliar) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="id_tipo_documento_identidad">Tipo Documento Identidad</label>
                <select id="id_tipo_documento_identidad" name="id_tipo_documento_identidad">
                    <option value="" data-codigo="">Seleccione un tipo</option>
                     <?php
                        $selected_tipo_doc = $form_data['id_tipo_documento_identidad'] ?? $item['id_tipo_documento_identidad'] ?? null;
                        foreach ($tipos_doc_identidad as $doc):
                     ?>
                        <option value="<?= $doc['id'] ?>" data-codigo="<?= htmlspecialchars($doc['codigo']) ?>" <?= ($doc['id'] == $selected_tipo_doc) ? 'selected' : '' ?>><?= htmlspecialchars($doc['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex-grow: 2;">
                <label for="num_doc_identidad">Nro. Documento</label>
                <div class="input-with-button">
                    <input type="text" id="num_doc_identidad" name="num_doc_identidad" value="<?= htmlspecialchars($form_data['num_doc_identidad'] ?? $item['num_doc_identidad'] ?? '') ?>">
                    <button type="button" id="sunatBtn" class="btn btn-sunat">SUNAT</button>
                </div>
            </div>
            <div class="form-group" style="flex-grow: 1;">
                 <label for="ubigeo">Ubigeo</label>
                <input type="text" id="ubigeo" name="ubigeo" value="<?= htmlspecialchars($form_data['ubigeo'] ?? $item['ubigeo'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="razon_social_nombres">Razón Social o Nombres Completos</label>
            <input type="text" id="razon_social_nombres" name="razon_social_nombres" value="<?= htmlspecialchars($form_data['razon_social_nombres'] ?? $item['razon_social_nombres'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($form_data['direccion'] ?? $item['direccion'] ?? '') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($form_data['telefono'] ?? $item['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? $item['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="TipoERP">Tipo ERP</label>
                <input type="text" id="TipoERP" name="TipoERP" value="<?= htmlspecialchars($form_data['TipoERP'] ?? $item['TipoERP'] ?? '') ?>" maxlength="1">
            </div>
            <div class="form-group">
                <label for="CodigoERP">Código ERP</label>
                <input type="text" id="CodigoERP" name="CodigoERP" value="<?= htmlspecialchars($form_data['CodigoERP'] ?? $item['CodigoERP'] ?? '') ?>" maxlength="5">
            </div>
        </div>
        <?php if ($is_edit): ?>
        <div class="form-group">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <?php $selected_estado = $form_data['estado'] ?? $item['estado'] ?? '1'; ?>
                <option value="1" <?= ($selected_estado == 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($selected_estado == 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-actions">
             <a href="index.php?page=auxiliares" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-submit">Guardar</button>
        </div>
    </form>
</section>

<script>
window.addEventListener('load', function() {
    // --- MODAL TRIGGER FROM URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const errorModalMessage = urlParams.get('error_modal');
    if (errorModalMessage && window.showAlertModal) {
        window.showAlertModal(errorModalMessage);
    }

    // --- FORM ELEMENTS ---
    const tipoDocSelect = document.getElementById('id_tipo_documento_identidad');
    const nroDocInput = document.getElementById('num_doc_identidad');
    const sunatBtn = document.getElementById('sunatBtn');
    const razonSocialInput = document.getElementById('razon_social_nombres');
    const direccionInput = document.getElementById('direccion');
    const ubigeoInput = document.getElementById('ubigeo');

    // --- LOGIC ---
    function fetchLongitudAndSetMaxLength() {
        const tipoDocId = tipoDocSelect.value;
        if (!tipoDocId) {
            nroDocInput.disabled = true;
            nroDocInput.removeAttribute('maxlength');
            nroDocInput.value = '';
            return;
        }

        fetch(`../src/ajax/get_tipo_doc_longitud.php?id=${tipoDocId}`)
            .then(response => response.json())
            .then(data => {
                nroDocInput.disabled = false;
                if (data.longitud && data.longitud > 0) {
                    nroDocInput.maxLength = data.longitud;
                } else {
                    nroDocInput.removeAttribute('maxlength');
                }
            })
            .catch(error => {
                console.error('Error fetching document length:', error);
                nroDocInput.disabled = false;
            });
    }

    tipoDocSelect.addEventListener('change', function() {
        nroDocInput.value = '';
        fetchLongitudAndSetMaxLength();
    });

    sunatBtn.addEventListener('click', function() {
        const tipo = tipoDocSelect.value; // Use the dropdown's value (the ID)
        const numero = nroDocInput.value.trim();

        // Use the correct IDs ('3' and '4'), as per user's final correction
        if ((tipo !== '3' && tipo !== '4') || !numero) {
            window.showAlertModal('Por favor, seleccione un tipo de documento (DNI/RUC) y ingrese un número.');
            return;
        }

        sunatBtn.textContent = '...';
        sunatBtn.disabled = true;

        // Send the ID to the backend proxy
        fetch(`../src/ajax/get_sunat_data.php?tipo=${tipo}&numero=${numero}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.error || `Error HTTP ${response.status}`) });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                // Use correct IDs to parse the response
                if (tipo === '3') { // DNI
                    razonSocialInput.value = `${data.nombres} ${data.apellidoPaterno} ${data.apellidoMaterno}`.trim();
                } else { // RUC (ID '4')
                    razonSocialInput.value = data.nombre || '';
                    direccionInput.value = `${data.direccion || ''} - ${data.departamento || ''} - ${data.provincia || ''} - ${data.distrito || ''}`;
                    ubigeoInput.value = data.ubigeo || '';
                }
            })
            .catch(error => {
                showAlertModal(`Error al consultar los datos: ${error.message}`);
            })
            .finally(() => {
                sunatBtn.textContent = 'SUNAT';
                sunatBtn.disabled = false;
            });
    });

    // Initial state on page load
    if (!errorModalMessage) {
        fetchLongitudAndSetMaxLength();
    }

    // --- FORM SUBMISSION VALIDATION ---
    const auxiliarForm = document.getElementById('auxiliarForm');
    auxiliarForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Stop submission initially

        const tipoAuxiliar = document.getElementById('id_tipo_auxiliar').value;
        const tipoDoc = document.getElementById('id_tipo_documento_identidad').value;
        const numDoc = document.getElementById('num_doc_identidad').value.trim();
        const razonSocial = document.getElementById('razon_social_nombres').value.trim();

        if (!tipoAuxiliar || !tipoDoc || !numDoc || !razonSocial) {
            window.showAlertModal('Por favor, complete todos los campos obligatorios.');
            return;
        }

        // If validation passes, submit the form
        auxiliarForm.submit();
    });
});
</script>
