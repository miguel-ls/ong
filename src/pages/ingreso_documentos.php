<?php
require_once __DIR__ . '/../database.php';

$documentos = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_all_documentos()");
    $stmt->execute();
    $documentos = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si el SP no existe, no es un error fatal, la tabla simplemente estará vacía.
    // En un futuro, se podría manejar este error de forma más explícita.
    if ($e->getCode() !== '42000') { // 42000 es el código para 'Syntax error or access violation'
      die("Error al obtener los documentos: " . $e->getMessage());
    }
}
?>

<style>
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; }
    .table th { background-color: #004a99; color: white; }
    .table tr:nth-child(even) { background-color: #f2f2f2; }
    .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; }
    .btn-edit { background-color: #ffc107; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; display: inline-block; margin-bottom: 20px; }
</style>

<header>
    <h1>Ingreso de Documentos</h1>
</header>
<section>
    <a href="index.php?page=ingreso_documentos_form" class="btn btn-add">Añadir Nuevo Documento</a>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha Emisión</th>
                <th>Tipo Documento</th>
                <th>Serie y Número</th>
                <th>Auxiliar</th>
                <th>Moneda</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documentos)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No hay documentos registrados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($documentos as $doc): ?>
                <tr>
                    <td><?= htmlspecialchars($doc['id']) ?></td>
                    <td><?= htmlspecialchars($doc['fecha_emision']) ?></td>
                    <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                    <td><?= htmlspecialchars($doc['serie_documento'] . '-' . $doc['numero_documento']) ?></td>
                    <td><?= htmlspecialchars($doc['auxiliar']) ?></td>
                    <td><?= htmlspecialchars($doc['moneda']) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format($doc['total'], 2)) ?></td>
                    <td>
                        <a href="index.php?page=ingreso_documentos_form&id=<?= $doc['id'] ?>" class="btn btn-edit">Editar</a>
                        <a href="../src/actions/documentos_process.php?action=delete&id=<?= $doc['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Está seguro de que quiere eliminar este documento?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
