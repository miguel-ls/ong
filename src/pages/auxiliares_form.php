<?php
require_once __DIR__ . '/../database.php';

if ($_SESSION['user_role'] !== 'administrador') {
    echo "<p>Acceso denegado.</p>";
    exit();
}

$item = null;
$is_edit = false;
$tipos_auxiliar = [];
$tipos_documento_identidad = [];

try {
    $pdo = getDbConnection();

    // Obtener tipos de auxiliar para el dropdown
    $stmt_tipos_aux = $pdo->prepare("CALL sp_read_all_tipos_auxiliar(?, ?)");
    $stmt_tipos_aux->execute([null, null]);
    $tipos_auxiliar = $stmt_tipos_aux->fetchAll();
    $stmt_tipos_aux->closeCursor();

    // Obtener tipos de documento de identidad para el dropdown
    $pdo = getDbConnection(); // Re-establish connection
    $stmt_tipos_doc = $pdo->prepare("CALL sp_read_tipos_documento_identidad_for_dropdown()");
    $stmt_tipos_doc->execute();
    $tipos_documento_identidad = $stmt_tipos_doc->fetchAll();
    $stmt_tipos_doc->closeCursor();

    if (isset($_GET['id'])) {
        $is_edit = true;
        $item_id = $_GET['id'];

        // Re-establish connection to prevent "MySQL server has gone away" errors on long-running pages
        $pdo = getDbConnection();

        $stmt_item = $pdo->prepare("CALL sp_read_auxiliar_by_id(?)");
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch();
        $stmt_item->closeCursor();
    }
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<style>
    .form-container { max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-submit { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
</style>

<header>
    <h1><?= $is_edit ? 'Editar' : 'Añadir' ?> Auxiliar</h1>
</header>
<section class="form-container">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_doc_type'): ?>
        <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">
            <strong>Error:</strong> Por favor, seleccione un Tipo de Documento de Identidad válido.
        </div>
    <?php endif; ?>
    <form action="../src/actions/auxiliares_process.php" method="POST">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'create' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="id_tipo_auxiliar">Tipo de Auxiliar</label>
            <select id="id_tipo_auxiliar" name="id_tipo_auxiliar" required>
                <option value="">Seleccione un tipo</option>
                <?php foreach ($tipos_auxiliar as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= (isset($item['id_tipo_auxiliar']) && $item['id_tipo_auxiliar'] == $tipo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="id_tipo_documento_identidad">Tipo Documento Identidad</label>
            <select id="id_tipo_documento_identidad" name="id_tipo_documento_identidad" required>
                <option value="">Seleccione un tipo</option>
                <?php foreach ($tipos_documento_identidad as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= (isset($item['id_tipo_documento_identidad']) && $item['id_tipo_documento_identidad'] == $tipo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="num_doc_identidad">Número Documento</label>
            <div style="display: flex; align-items: center;">
                <input type="text" id="num_doc_identidad" name="num_doc_identidad" value="<?= htmlspecialchars($item['num_doc_identidad'] ?? '') ?>" required style="flex-grow: 1;">
                <button type="button" id="btn-sunat" class="btn-submit" style="margin-left: 10px; flex-shrink: 0;">SUNAT</button>
            </div>
        </div>
        <div class="form-group">
            <label for="razon_social_nombres">Razón Social / Nombres</label>
            <input type="text" id="razon_social_nombres" name="razon_social_nombres" value="<?= htmlspecialchars($item['razon_social_nombres'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($item['direccion'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($item['telefono'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="ubigeo">Ubigeo</label>
            <input type="text" id="ubigeo" name="ubigeo" value="<?= htmlspecialchars($item['ubigeo'] ?? '') ?>" maxlength="6">
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

        <button type="submit" class="btn-submit"><?= $is_edit ? 'Actualizar' : 'Crear' ?> Auxiliar</button>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSunat = document.getElementById('btn-sunat');
    const tipoDocSelect = document.getElementById('id_tipo_documento_identidad');
    const numDocInput = document.getElementById('num_doc_identidad');

    // --- Funciones de Lógica ---
    function updateSunatButtonVisibility() {
        const selectedOption = tipoDocSelect.options[tipoDocSelect.selectedIndex];
        const selectedText = selectedOption ? selectedOption.text.toUpperCase() : '';

        if (selectedText.includes('RUC') || selectedText.includes('DNI')) {
            btnSunat.style.display = 'inline-block';
        } else {
            btnSunat.style.display = 'none';
        }
    }

    async function updateDocumentLength() {
        const selectedId = tipoDocSelect.value;
        // No limpiar el maxlength aquí, solo quitarlo si no hay tipo
        if (!selectedId) {
            numDocInput.removeAttribute('maxlength');
            return;
        }

        try {
            const response = await fetch(`../src/ajax/get_tipo_documento_identidad_longitud.php?id=${selectedId}`);
            if (!response.ok) {
                numDocInput.removeAttribute('maxlength');
                return;
            }

            const data = await response.json();
            if (data && data.longitud && data.longitud > 0) {
                numDocInput.maxLength = data.longitud;
            } else {
                numDocInput.removeAttribute('maxlength');
            }
        } catch (error) {
            console.error('Error al obtener la longitud del tipo de documento:', error);
            numDocInput.removeAttribute('maxlength');
        }
    }

    // --- Event Listeners ---
    tipoDocSelect.addEventListener('change', function() {
        numDocInput.value = ''; // Limpiar el valor del número solo en el cambio manual
        updateSunatButtonVisibility();
        updateDocumentLength();
    });

    // --- Carga Inicial ---
    // Llamar a las funciones para establecer el estado inicial de la página
    updateSunatButtonVisibility();
    updateDocumentLength();

    // Listener para el clic en el botón SUNAT
    btnSunat.addEventListener('click', async function() {
        const selectedOption = tipoDocSelect.options[tipoDocSelect.selectedIndex];
        const docType = selectedOption ? selectedOption.text.toUpperCase() : '';
        const docNumber = numDocInput.value;

        let apiUrl = '';
        let validationRegex = null;
        let validationMessage = '';

        if (docType.includes('RUC')) {
            apiUrl = `../src/ajax/get_ruc.php?numero=${docNumber}`;
            validationRegex = /^\d{11}$/;
            validationMessage = 'Por favor, ingrese un número de RUC válido de 11 dígitos.';
        } else if (docType.includes('DNI')) {
            apiUrl = `../src/ajax/get_dni.php?numero=${docNumber}`;
            validationRegex = /^\d{8}$/;
            validationMessage = 'Por favor, ingrese un número de DNI válido de 8 dígitos.';
        } else {
            return; // No hacer nada si el tipo de documento no es RUC o DNI
        }

        if (!validationRegex.test(docNumber)) {
            alert(validationMessage);
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                throw new Error(errorData?.error || `No se pudo obtener una respuesta de la API para ${docType}.`);
            }

            const data = await response.json();
            if (data.error) {
                throw new Error(data.error);
            }

            const razonSocialInput = document.getElementById('razon_social_nombres');
            const direccionInput = document.getElementById('direccion');
            const ubigeoInput = document.getElementById('ubigeo');

            if (razonSocialInput && data.nombre) {
                razonSocialInput.value = data.nombre;
            }

            let fullDireccion = [data.direccion, data.departamento, data.provincia, data.distrito]
                .filter(Boolean)
                .join(' - ');
            if (direccionInput) {
                direccionInput.value = fullDireccion;
            }

            if (ubigeoInput && data.ubigeo) {
                ubigeoInput.value = data.ubigeo;
            }

        } catch (error) {
            console.error(`Error al consultar ${docType}:`, error);
            alert(`Error al consultar ${docType}: ${error.message}`);
        } finally {
            this.disabled = false;
            this.textContent = 'SUNAT';
        }
    });
});
</script>
