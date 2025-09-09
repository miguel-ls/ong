<?php
require_once __DIR__ . '/../database.php';
session_start(); // Required to access session variables

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$pdo = getDbConnection();
$item = null;
$is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $item_id = $_GET['id'];
    $stmt = $pdo->prepare("CALL sp_read_auxiliar_by_id(?)");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><?= $is_edit ? 'Editar' : 'Nuevo'; ?> Auxiliar</h4>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            <form id="auxiliarForm" action="../src/actions/auxiliares_process.php" method="post">
                <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="razon_social_nombres" class="form-label">Razón Social o Nombres Completos</label>
                    <input type="text" class="form-control" id="razon_social_nombres" name="razon_social_nombres" value="<?= htmlspecialchars($item['razon_social_nombres'] ?? '') ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_tipo_auxiliar" class="form-label">Tipo de Auxiliar</label>
                        <select class="form-select" id="id_tipo_auxiliar" name="id_tipo_auxiliar" required>
                            <option value="">Seleccione un tipo</option>
                            <?php
                            $stmt_tipos = $pdo->query("SELECT id, nombre FROM tipos_auxiliar WHERE estado = 1 ORDER BY nombre");
                            while ($row = $stmt_tipos->fetch(PDO::FETCH_ASSOC)) {
                                $selected = (isset($item['id_tipo_auxiliar']) && $row['id'] == $item['id_tipo_auxiliar']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' {$selected}>{$row['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_tipo_documento_identidad" class="form-label">Tipo Documento Identidad</label>
                        <select class="form-select" id="id_tipo_documento_identidad" name="id_tipo_documento_identidad" required>
                            <option value="">Seleccione un tipo</option>
                            <?php
                            $stmt_docs = $pdo->prepare("CALL sp_read_tipos_documento_identidad_for_dropdown()");
                            $stmt_docs->execute();
                            while ($row = $stmt_docs->fetch(PDO::FETCH_ASSOC)) {
                                $selected = (isset($item['id_tipo_documento_identidad']) && $row['id'] == $item['id_tipo_documento_identidad']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' {$selected}>{$row['nombre']}</option>";
                            }
                            $stmt_docs->closeCursor();
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="num_doc_identidad" class="form-label">Nro. Documento</label>
                        <input type="text" class="form-control" id="num_doc_identidad" name="num_doc_identidad" value="<?= htmlspecialchars($item['num_doc_identidad'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                         <label for="ubigeo" class="form-label">Ubigeo</label>
                        <input type="text" class="form-control" id="ubigeo" name="ubigeo" value="<?= htmlspecialchars($item['ubigeo'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?= htmlspecialchars($item['direccion'] ?? '') ?>">
                </div>

                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($item['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
                    </div>
                </div>

                <?php if ($is_edit): ?>
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <option value="1" <?= (isset($item['estado']) && $item['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= (isset($item['estado']) && $item['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end">
                     <a href="index.php?page=auxiliares" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoDocSelect = document.getElementById('id_tipo_documento_identidad');
    const nroDocInput = document.getElementById('num_doc_identidad');

    function fetchLongitudAndSetMaxLength() {
        const tipoDocId = tipoDocSelect.value;
        if (!tipoDocId) {
            nroDocInput.disabled = true;
            nroDocInput.removeAttribute('maxlength');
            nroDocInput.value = '';
            return;
        }

        // We need an AJAX call to get the longitud
        // Let's create a small endpoint for this.
        // For now, let's assume we can get it from a data attribute if it exists.
        // This is a temporary solution until we fix the AJAX part.
        // The previous code had a major flaw: it couldn't get the length for existing items.

        const selectedOption = tipoDocSelect.options[tipoDocSelect.selectedIndex];
        // The SP for dropdown doesn't return longitud. We need to call the other SP.
        // This requires an AJAX call.

        // Let's create a new file for this: get_tipo_doc_longitud.php
        fetch(`../src/ajax/get_tipo_doc_longitud.php?id=${tipoDocId}`)
            .then(response => response.json())
            .then(data => {
                if (data.longitud && data.longitud > 0) {
                    nroDocInput.maxLength = data.longitud;
                    nroDocInput.disabled = false;
                } else {
                    nroDocInput.removeAttribute('maxlength');
                    nroDocInput.disabled = false; // Allow any length if not specified
                }
            })
            .catch(error => {
                console.error('Error fetching document length:', error);
                nroDocInput.disabled = false; // Enable anyway on error
            });
    }

    tipoDocSelect.addEventListener('change', function() {
        nroDocInput.value = ''; // Clear the value when type changes
        fetchLongitudAndSetMaxLength();
    });

    // Initial state
    if (tipoDocSelect.value) {
        fetchLongitudAndSetMaxLength();
    } else {
        nroDocInput.disabled = true;
    }
});
</script>
