<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';

// El frontend enviará la acción como un parámetro GET, ej: /plantillas_reporte_process.php?action=obtener_todas
$action = $_GET['action'] ?? '';

try {
    $pdo = getDbConnection();

    switch ($action) {
        // Devuelve una lista simple de todas las plantillas para poblar el dropdown
        case 'obtener_todas':
            $stmt = $pdo->query("SELECT id, nombre_plantilla FROM reporte_plantillas ORDER BY nombre_plantilla ASC");
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'templates' => $templates]);
            break;

        // Devuelve las columnas de una plantilla específica para cargarla en el editor
        case 'obtener_una':
            $id = $_GET['id'] ?? 0;
            if (empty($id)) {
                throw new Exception("ID de plantilla no proporcionado.");
            }
            $stmt = $pdo->prepare("SELECT columnas FROM reporte_plantillas WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$template) {
                throw new Exception("Plantilla no encontrada.");
            }
            // Las columnas se guardan como un string JSON, así que lo decodificamos antes de enviarlo
            echo json_encode(['success' => true, 'columns' => json_decode($template['columnas'])]);
            break;

        // Guarda una nueva plantilla o actualiza una existente si el nombre ya existe
        case 'guardar':
            $input = json_decode(file_get_contents('php://input'), true);
            $name = $input['name'] ?? '';
            $columns = $input['columns'] ?? [];

            if (empty($name) || !isset($columns)) { // Permitir guardar una plantilla vacía
                throw new Exception("El nombre de la plantilla es obligatorio.");
            }

            $jsonColumns = json_encode($columns);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error interno al procesar la lista de columnas.");
            }

            // Usamos INSERT ... ON DUPLICATE KEY UPDATE para crear o actualizar.
            // Esto funciona porque la columna 'nombre_plantilla' tiene una restricción UNIQUE.
            $sql = "INSERT INTO reporte_plantillas (nombre_plantilla, columnas) VALUES (?, ?) ON DUPLICATE KEY UPDATE columnas = VALUES(columnas)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $jsonColumns]);

            // Obtenemos el ID, ya sea el nuevo insertado o el del registro actualizado.
            $lastId = $pdo->lastInsertId();
            if ($lastId == 0) {
                $stmt = $pdo->prepare("SELECT id FROM reporte_plantillas WHERE nombre_plantilla = ?");
                $stmt->execute([$name]);
                $lastId = $stmt->fetchColumn();
            }

            echo json_encode(['success' => true, 'message' => 'Plantilla guardada correctamente.', 'new_id' => $lastId]);
            break;

        // Elimina una plantilla por su ID
        case 'eliminar':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;

            if (empty($id)) {
                throw new Exception("ID de plantilla no proporcionado para eliminar.");
            }

            $stmt = $pdo->prepare("DELETE FROM reporte_plantillas WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Plantilla eliminada correctamente.']);
            } else {
                throw new Exception("No se encontró la plantilla para eliminar.");
            }
            break;

        default:
            throw new Exception("Acción no válida o no especificada.");
            break;
    }

} catch (Exception $e) {
    http_response_code(400); // Bad Request o Internal Server Error son apropiados
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
