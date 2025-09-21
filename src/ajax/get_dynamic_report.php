<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';
$dictionary = require_once __DIR__ . '/../config/reporting_dictionary.php';

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$selectedColumns = $input['columns'] ?? [];
$filters = $input['filters'] ?? [];

// --- Validation ---
function isColumnValid($column, $dictionary) {
    foreach ($dictionary['tables'] as $table) {
        if (isset($table['columns'][$column])) {
            return true;
        }
    }
    return false;
}

foreach ($selectedColumns as $col) {
    if (!isColumnValid($col, $dictionary)) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid column selected: " . htmlspecialchars($col)]);
        exit;
    }
}
foreach ($filters as $filter) {
    if (!isColumnValid($filter['column'], $dictionary) || !in_array($filter['operator'], ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid filter provided.']);
        exit;
    }
}


// --- Query Building ---
try {
    if (empty($selectedColumns)) {
        echo json_encode(['error' => 'No columns selected.', 'data' => []]);
        exit;
    }

    // --- Logic for Prorated Calculation ---
    $isProrated = false;
    // Check if any selected column or filter column requires proration logic
    $allInvolvedColumns = array_merge($selectedColumns, array_column($filters, 'column'));
    foreach ($allInvolvedColumns as $col) {
        if (strpos($col, 'cc.') === 0 || strpos($col, 'ddd.') === 0) {
            $isProrated = true;
            break;
        }
    }

    // --- 1. SELECT clause ---
    $selectClauseParts = [];
    $groupByParts = [];
    $numericColsToProrate = ['dd.precio_total', 'dd.total_soles', 'dd.total_dolares'];

    foreach ($selectedColumns as $col) {
        // Find the friendly name for the alias
        $friendlyName = $col; // Fallback
        foreach($dictionary['tables'] as $tableInfo) {
            if(isset($tableInfo['columns'][$col])) {
                $friendlyName = $tableInfo['columns'][$col]['friendly_name'];
                break;
            }
        }

        if ($isProrated && in_array($col, $numericColsToProrate)) {
            // If we need to prorate, wrap numeric fields in SUM and apply the percentage
            $selectClauseParts[] = "SUM($col * (ddd.porcentaje / 100)) AS `$friendlyName`";
        } else {
            // Otherwise, select the column directly
            $selectClauseParts[] = "$col AS `$friendlyName`";
            if ($isProrated) {
                // If we are prorating, we must group by all non-summed columns
                $groupByParts[] = $col;
            }
        }
    }
    $selectClause = implode(', ', $selectClauseParts);


    // --- 2. FROM and JOIN clauses ---
    $fromClause = 'FROM ' . $dictionary['base_table'] . ' ' . $dictionary['tables'][$dictionary['base_table']]['alias'];
    $joinClause = '';
    $joinedTables = [$dictionary['base_table']];

    $requiredTables = [];
     foreach ($allInvolvedColumns as $col) {
        list($alias, ) = explode('.', $col);
        foreach($dictionary['tables'] as $tableName => $tableInfo) {
            if ($tableInfo['alias'] === $alias) {
                $requiredTables[] = $tableName;
            }
        }
    }
    // If we are prorating, we implicitly need the distribution table
    if ($isProrated) {
        $requiredTables[] = 'centros_costos';
    }
    $requiredTables = array_unique($requiredTables);

    // Build joins based on required tables
    foreach ($dictionary['joins'] as $parentTable => $joins) {
        foreach($joins as $childTable => $joinSql) {
            if (in_array($childTable, $requiredTables) && !in_array($childTable, $joinedTables)) {
                $joinClause .= ' ' . $joinSql;
                $joinedTables[] = $childTable;
            }
        }
    }


    // --- 3. WHERE clause ---
    $whereClause = '';
    $params = [];
    if (!empty($filters)) {
        $whereConditions = [];
        foreach ($filters as $filter) {
            $operator = $filter['operator'];
            $value = $filter['value'];
            if (strtoupper($operator) === 'LIKE' || strtoupper($operator) === 'NOT LIKE') {
                $value = '%' . $value . '%';
            }
            $whereConditions[] = $filter['column'] . ' ' . $operator . ' ?';
            $params[] = $value;
        }
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // --- 4. GROUP BY clause ---
    $groupByClause = '';
    if ($isProrated && !empty($groupByParts)) {
        $groupByClause = 'GROUP BY ' . implode(', ', $groupByParts);
    }


    // --- Execution ---
    $pdo = getDbConnection();
    $sql = "SELECT $selectClause $fromClause $joinClause $whereClause $groupByClause LIMIT 2000";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    // In production, log the error instead of echoing it.
    echo json_encode(['error' => 'An internal server error occurred.', 'details' => $e->getMessage()]);
}
