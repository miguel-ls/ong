<?php
header('Content-Type: application/json');
$dictionary = require_once __DIR__ . '/../config/reporting_dictionary.php';

// We only need to send the column metadata to the frontend, not the join logic.
$columnsForFrontend = [];
foreach ($dictionary['tables'] as $tableName => $tableInfo) {
    foreach($tableInfo['columns'] as $columnKey => $columnInfo) {
        $columnsForFrontend[] = [
            'key' => $columnKey,
            'friendly_name' => $columnInfo['friendly_name'],
            'type' => $columnInfo['type']
        ];
    }
}

echo json_encode($columnsForFrontend);
