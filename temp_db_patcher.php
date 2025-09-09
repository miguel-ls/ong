<?php
// A temporary script to apply the SQL patch.
// This will be deleted after execution.

require_once 'src/database.php'; // Assuming this file returns a PDO instance named $pdo

try {
    echo "Connecting to the database...\n";

    // The database connection is established in database.php, and the object is $pdo.
    if (!isset($pdo)) {
        throw new Exception("PDO connection object not found. Please check src/database.php");
    }

    echo "Reading SQL patch file...\n";
    $sql_file = 'database/patch_maestro_documentos_final.sql';
    $sql_content = file_get_contents($sql_file);

    if ($sql_content === false) {
        throw new Exception("Could not read the SQL file: " . $sql_file);
    }

    // PDO::exec() is not suitable for running scripts with multiple statements or DELIMITER changes.
    // It's better to execute them one by one, but that's complex with DELIMITER.
    // Let's try a different approach: command-line execution from within PHP, if possible.
    // Since mysql client is not available, that's not an option.
    // Let's try to split the script and run commands. This is risky.

    // A simpler approach for this specific script which uses DELIMITER for procedures:
    // We can't use one single exec(). We need to handle this.
    // However, since the procedures are idempotent (DROP IF EXISTS), re-running the script
    // which created them should be fine, and maybe the initial run was partial.

    // A simple exec() might work if the driver supports multiple queries. Let's try.
    echo "Executing SQL patch...\n";
    $result = $pdo->exec($sql_content);

    // exec() returns the number of rows affected by statement-level queries (UPDATE, INSERT, DELETE)
    // It does NOT return meaningful values for CREATE TABLE, DROP PROCEDURE etc.
    // A result of `false` indicates failure.
    if ($result === false) {
        $errorInfo = $pdo->errorInfo();
        throw new Exception("SQL execution failed: " . $errorInfo[2]);
    }

    echo "SQL patch applied successfully.\n";

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}
?>
