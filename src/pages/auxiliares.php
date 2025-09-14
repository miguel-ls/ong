<?php
require_once __DIR__ . '/../database.php';

// session_start() is removed as it's likely called in a global header or index file.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php?error=Acceso no autorizado');
    exit();
}

$pdo = getDbConnection();

// Filtering parameters
$filter_nombre = $_GET['nombre'] ?? null;
$filter_num_doc = $_GET['num_doc'] ?? null;
$filter_tipo_aux = $_GET['tipo_aux'] ?? null;
$filter_tipo_erp = $_GET['tipo_erp'] ?? null;
$filter_codigo_erp = $_GET['codigo_erp'] ?? null;

$items = [];
$tipos_auxiliar = [];

try {
    // First query: get the main list of items
    $stmt = $pdo->prepare("CALL sp_read_all_auxiliares(?, ?, ?, ?, ?)");
    $stmt->execute([$filter_nombre, $filter_num_doc, $filter_tipo_aux, $filter_tipo_erp, $filter_codigo_erp]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    while ($stmt->nextRowset());
    $stmt->closeCursor();

    // Second query: get the types for the filter dropdown
    $stmt_tipos = $pdo->prepare("SELECT id, nombre FROM tipos_auxiliar WHERE estado = 1 ORDER BY nombre");
    $stmt_tipos->execute();
    $tipos_auxiliar = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
    $stmt_tipos->closeCursor();

} catch (PDOException $e) {
    die("Error al obtener datos para la página de auxiliares: " . $e->getMessage());
}
?>

<style>
    .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .table th { background-color: #004a99; color: white; }
    .table tr:nth-child(even) { background-color: #f2f2f2; }
    .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white !important; display: inline-block; border: none; cursor: pointer; }
    .btn-edit { background-color: #ffc107; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; display: inline-block; margin-bottom: 20px; }
    .btn-migrate { background-color: #ffc107; /* Light Orange */ color: black; }
    .filter-form { background-color: #eef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form .form-group label { margin-bottom: 5px; font-weight: bold; }
    .filter-form .form-group input, .filter-form .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-width: 200px; }
    .btn-filter { background-color: #005cb3; color: white; padding: 8px 15px; }
    .badge { padding: 5px 8px; border-radius: 4px; color: white; font-weight: bold; }
    .bg-success { background-color: #28a745; }
    .bg-danger { background-color: #dc3545; }
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
    .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }

    /* --- START MODAL FIX --- */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        display: none; /* Hide by default */
        width: 100%;
        height: 100%;
        overflow: auto;
        outline: 0;
        background-color: rgba(0,0,0,0.5);
    }
    .modal.show {
        display: block; /* Show when JS adds this class */
    }
    .modal-dialog {
        position: relative;
        margin: 1.75rem auto;
        max-width: 500px;
    }
    .modal-content {
        background-color: #fff;
        border: 1px solid rgba(0,0,0,.2);
        border-radius: .3rem;
        padding: 1rem;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: .5rem;
        margin-bottom: 1rem;
    }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        border-top: 1px solid #dee2e6;
        padding-top: .5rem;
        margin-top: 1rem;
    }
    .btn-close {
      cursor: pointer;
      background: transparent;
      border: 0;
      font-size: 1.5rem;
      font-weight: 700;
      line-height: 1;
      color: #000;
      text-shadow: 0 1px 0 #fff;
      opacity: .5;
    }
    /* --- END MODAL FIX --- */
</style>

<header>
    <h1>Gestión de Auxiliares</h1>
</header>
<section>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php elseif (isset($_GET['error'])): ?>
         <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <a href="index.php?page=auxiliares_form" class="btn btn-add">Nuevo Auxiliar</a>
    <button type="button" id="migrateBtn" class="btn btn-migrate">Migrar Auxiliares</button>

    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="auxiliares">
        <div class="form-group">
            <label for="nombre">Nombre / Razón Social</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="num_doc">Nro. Documento</label>
            <input type="text" id="num_doc" name="num_doc" value="<?= htmlspecialchars($filter_num_doc ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="tipo_aux">Tipo Auxiliar</label>
            <select id="tipo_aux" name="tipo_aux">
                <option value="">Todos</option>
                <?php foreach ($tipos_auxiliar as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= ($filter_tipo_aux == $tipo['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="tipo_erp">Tipo ERP</label>
            <input type="text" id="tipo_erp" name="tipo_erp" value="<?= htmlspecialchars($filter_tipo_erp ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="codigo_erp">Código ERP</label>
            <input type="text" id="codigo_erp" name="codigo_erp" value="<?= htmlspecialchars($filter_codigo_erp ?? '') ?>">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-filter">Filtrar</button>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Tipo de Auxiliar</th>
                <th>Razón Social / Nombre</th>
                <th>Tipo Doc.</th>
                <th>Nro. Documento</th>
                <th>Teléfono</th>
                <th>Tipo ERP</th>
                <th>Código ERP</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="9" style="text-align: center;">No se encontraron auxiliares.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nombre_tipo_auxiliar']) ?></td>
                    <td><?= htmlspecialchars($item['razon_social_nombres']) ?></td>
                    <td><?= htmlspecialchars($item['tipo_doc_identidad']) ?></td>
                    <td><?= htmlspecialchars($item['num_doc_identidad']) ?></td>
                    <td><?= htmlspecialchars($item['telefono']) ?></td>
                    <td><?= htmlspecialchars($item['TipoERP']) ?></td>
                    <td><?= htmlspecialchars($item['CodigoERP']) ?></td>
                    <td><span class="badge <?= $item['estado'] ? 'bg-success' : 'bg-danger' ?>"><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td>
                        <a href="index.php?page=auxiliares_form&id=<?= $item['id'] ?>" class="btn btn-edit" title="Editar">Editar</a>
                        <button class="btn btn-delete" data-id="<?= $item['id'] ?>" title="Eliminar">Eliminar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        ¿Está seguro de que desea eliminar este auxiliar? Si no tiene documentos asociados, la eliminación será permanente.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- START DELETE LOGIC ---
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteModalElement = document.getElementById('deleteConfirmModal');

    if (deleteModalElement && typeof bootstrap !== 'undefined') {
        const deleteModal = new bootstrap.Modal(deleteModalElement);

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const auxiliarId = this.getAttribute('data-id');
                const deleteUrl = `../src/actions/auxiliares_process.php?action=delete&id=${auxiliarId}`;
                confirmDeleteBtn.setAttribute('href', deleteUrl);
                deleteModal.show();
            });
        });
    }
    // --- END DELETE LOGIC ---

    // --- START MIGRATE LOGIC ---
    const migrateBtn = document.getElementById('migrateBtn');
    migrateBtn.addEventListener('click', function() {
        // Show some visual feedback that the process has started
        migrateBtn.disabled = true;
        migrateBtn.textContent = 'Migrando...';

        const apiData = {
            Emp_cCodigo: "<?= defined('Emp_cCodigo') ? Emp_cCodigo : '' ?>",
            Pan_cAnio: "<?= defined('Pan_cAnio') ? Pan_cAnio : '' ?>"
        };

        fetch('http://localhost:1880/maestros/migrarauxiliares', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(apiData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data && typeof data.data.inserted !== 'undefined') {
                showAlertModal(`Se migraron ${data.data.inserted} registros.`);
            } else {
                showAlertModal('No se encontraron registros para migrar.');
            }
        })
        .catch(error => {
            console.error('Error en la migración:', error);
            showAlertModal('Ocurrió un error al intentar la migración. Verifique la consola para más detalles.');
        })
        .finally(() => {
            // Restore button state
            migrateBtn.disabled = false;
            migrateBtn.textContent = 'Migrar Auxiliares';
        });
    });
    // --- END MIGRATE LOGIC ---
});
</script>
