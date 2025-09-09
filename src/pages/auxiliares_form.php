<?php
require_once __DIR__ . '/../database.php';

// session_start() is removed.
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

// Pre-fetch data for dropdowns
$tipos_auxiliar = $pdo->query("SELECT id, nombre FROM tipos_auxiliar WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tipos_doc_identidad = $pdo->query("CALL sp_read_tipos_documento_identidad_for_dropdown()")->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    .form-container { max-width: 800px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .form-row { display: flex; gap: 20px; }
    .form-row .form-group { flex: 1; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-cancel { background-color: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
    .form-actions { text-align: right; margin-top: 20px; }
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
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

        <div class="form-group">
            <label for="razon_social_nombres">Razón Social o Nombres Completos</label>
            <input type="text" id="razon_social_nombres" name="razon_social_nombres" value="<?= htmlspecialchars($item['razon_social_nombres'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="id_tipo_auxiliar">Tipo de Auxiliar</label>
                <select id="id_tipo_auxiliar" name="id_tipo_auxiliar" required>
                    <option value="">Seleccione un tipo</option>
                    <?php foreach ($tipos_auxiliar as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>" <?= (isset($item['id_tipo_auxiliar']) && $row['id'] == $item['id_tipo_auxiliar']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="id_tipo_documento_identidad">Tipo Documento Identidad</label>
                <select id="id_tipo_documento_identidad" name="id_tipo_documento_identidad" required>
                    <option value="">Seleccione un tipo</option>
                     <?php foreach ($tipos_doc_identidad as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= (isset($item['id_tipo_documento_identidad']) && $doc['id'] == $item['id_tipo_documento_identidad']) ? 'selected' : '' ?>><?= htmlspecialchars($doc['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="num_doc_identidad">Nro. Documento</label>
                <input type="text" id="num_doc_identidad" name="num_doc_identidad" value="<?= htmlspecialchars($item['num_doc_identidad'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                 <label for="ubigeo">Ubigeo</label>
                <input type="text" id="ubigeo" name="ubigeo" value="<?= htmlspecialchars($item['ubigeo'] ?? '') ?>">
            </div>
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

    // Initial state on page load
    fetchLongitudAndSetMaxLength();
});
</script>
