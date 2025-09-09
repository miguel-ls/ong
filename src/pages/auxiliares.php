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

$items = [];
$tipos_auxiliar = [];

try {
    // First query: get the main list of items
    $stmt = $pdo->prepare("CALL sp_read_all_auxiliares(?, ?, ?)");
    $stmt->execute([$filter_nombre, $filter_num_doc, $filter_tipo_aux]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // THE CRITICAL FIX:
    // Some drivers/environments require explicitly clearing all potential subsequent rowsets
    // from a stored procedure call before another query can be made on the same connection.
    // The do-while loop with nextRowset() is the most robust way to do this.
    while ($stmt->nextRowset());

    $stmt->closeCursor();

    // Second query: get the types for the filter dropdown
    $stmt_tipos = $pdo->prepare("SELECT id, nombre FROM tipos_auxiliar WHERE estado = 1 ORDER BY nombre");
    $stmt_tipos->execute();
    $tipos_auxiliar = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
    $stmt_tipos->closeCursor();

} catch (PDOException $e) {
    // Provide a user-friendly error message
    die("Error al obtener datos para la página de auxiliares: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Gestión de Auxiliares</h4>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                 <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end mb-3">
                <a href="index.php?page=auxiliares_form" class="btn btn-primary">Nuevo Auxiliar</a>
            </div>

            <!-- Filter Form -->
            <form action="index.php" method="GET" class="filter-form mb-4">
                <input type="hidden" name="page" value="auxiliares">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="nombre" class="form-label">Nombre / Razón Social</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($filter_nombre ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="num_doc" class="form-label">Nro. Documento</label>
                        <input type="text" class="form-control" id="num_doc" name="num_doc" value="<?= htmlspecialchars($filter_num_doc ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="tipo_aux" class="form-label">Tipo Auxiliar</label>
                        <select class="form-select" id="tipo_aux" name="tipo_aux">
                            <option value="">Todos</option>
                            <?php foreach ($tipos_auxiliar as $tipo): ?>
                                <option value="<?= $tipo['id'] ?>" <?= ($filter_tipo_aux == $tipo['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info w-100">Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Razón Social / Nombre</th>
                            <th>Tipo Doc.</th>
                            <th>Nro. Documento</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="6" class="text-center">No se encontraron auxiliares.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['razon_social_nombres']) ?></td>
                                <td><?= htmlspecialchars($item['tipo_doc_identidad']) ?></td>
                                <td><?= htmlspecialchars($item['num_doc_identidad']) ?></td>
                                <td><?= htmlspecialchars($item['telefono']) ?></td>
                                <td><span class="badge <?= $item['estado'] ? 'bg-success' : 'bg-danger' ?>"><?= $item['estado'] ? 'Activo' : 'Inactivo' ?></span></td>
                                <td>
                                    <a href="index.php?page=auxiliares_form&id=<?= $item['id'] ?>" class="btn btn-sm btn-warning me-2" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $item['id'] ?>" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    const deleteModalElement = document.getElementById('deleteConfirmModal');
    if (deleteModalElement) {
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
});
</script>
