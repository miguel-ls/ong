<?php
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$pdo = getDbConnection();
$item = null;
$is_edit = false;

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
        <!-- Form fields... -->
        <div class="form-row">
            <div class="form-group">
                <label for="id_tipo_auxiliar">Tipo de Auxiliar</label>
                <select id="id_tipo_auxiliar" name="id_tipo_auxiliar" required>
                    <option value="">Seleccione un tipo</option>
                    <?php foreach ($tipos_auxiliar as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>" <?= (isset($item['id_tipo_auxiliar']) && $tipo['id'] == $item['id_tipo_auxiliar']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="id_tipo_documento_identidad">Tipo Documento Identidad</label>
                <select id="id_tipo_documento_identidad" name="id_tipo_documento_identidad" required>
                    <option value="" data-codigo="">Seleccione un tipo</option>
                     <?php foreach ($tipos_doc_identidad as $doc): ?>
                        <option value="<?= $doc['id'] ?>" data-codigo="<?= htmlspecialchars($doc['codigo']) ?>" <?= (isset($item['id_tipo_documento_identidad']) && $doc['id'] == $item['id_tipo_documento_identidad']) ? 'selected' : '' ?>><?= htmlspecialchars($doc['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex-grow: 2;">
                <label for="num_doc_identidad">Nro. Documento</label>
                <div class="input-with-button">
                    <input type="text" id="num_doc_identidad" name="num_doc_identidad" value="<?= htmlspecialchars($item['num_doc_identidad'] ?? '') ?>" required>
                    <button type="button" id="sunatBtn" class="btn btn-sunat">SUNAT</button>
                </div>
            </div>
            <div class="form-group" style="flex-grow: 1;">
                 <label for="ubigeo">Ubigeo</label>
                <input type="text" id="ubigeo" name="ubigeo" value="<?= htmlspecialchars($item['ubigeo'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="razon_social_nombres">Razón Social o Nombres Completos</label>
            <input type="text" id="razon_social_nombres" name="razon_social_nombres" value="<?= htmlspecialchars($item['razon_social_nombres'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($item['direccion'] ?? '') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($item['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
            </div>
        </div>
        <?php if ($is_edit): ?>
        <div class="form-group">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" required>
                <option value="1" <?= (isset($item['estado']) && $item['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= (isset($item['estado']) && $item['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-actions">
             <a href="index.php?page=auxiliares" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-submit">Guardar</button>
        </div>
    </form>
</section>

<!-- Reusable Alert Modal HTML -->
<div class="modal" id="alertModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="alertModalLabel">Aviso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="alertModalBody">
        <!-- Message will be inserted here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- MODAL HELPER ---
    let alertModalInstance;
    function showAlertModal(message) {
        const modalElement = document.getElementById('alertModal');
        if (!modalElement) {
            alert(message); // Fallback if modal HTML is missing
            return;
        }

        // Assuming Bootstrap JS is loaded, as other modals on the site work.
        // Removing the typeof bootstrap check to force usage.
        if (!alertModalInstance) {
         if (typeof bootstrap === 'undefined') {
            alert(message); // Fallback if Bootstrap JS is not loaded
            return;
        }
            alertModalInstance = new bootstrap.Modal(modalElement);
        }
        document.getElementById('alertModalBody').textContent = message;
        alertModalInstance.show();
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

        // Use the IDs for validation, as per user's fix
        if ((tipo !== '1' && tipo !== '6') || !numero) {
            showAlertModal('Por favor, seleccione un tipo de documento (DNI/RUC) y ingrese un número.');
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

                // Check the ID to determine how to parse the response
                if (tipo === '1') { // DNI
                    razonSocialInput.value = `${data.nombres} ${data.apellidoPaterno} ${data.apellidoMaterno}`.trim();
                } else { // RUC (ID '6')
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
    fetchLongitudAndSetMaxLength();
});
</script>
