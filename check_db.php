<?php
header('Content-Type: text/plain');
require_once 'config/db.php';
$conn = getConnection();

echo "Database: " . DB_NAME . "\n\n";

$tables_res = $conn->query("SHOW TABLES");
if ($tables_res) {
    if ($tables_res->num_rows === 0) {
        echo "No tables found in " . DB_NAME . "\n";
    }
    while ($t_row = $tables_res->fetch_array()) {
        $table = $t_row[0];
        echo "Table: $table\n";
        echo "------------------\n";
        $result = $conn->query("DESCRIBE `$table`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                echo $row['Field'] . " | " . $row['Type'] . "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
