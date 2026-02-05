<?php
header('Content-Type: text/plain');
require_once 'config/db.php';
$conn = getConnection();

echo "Starting database fix for: " . DB_NAME . "\n";

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop all existing tables
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $table = $row[0];
        if ($conn->query("DROP TABLE `$table`")) {
            echo "Dropped table: $table\n";
        } else {
            echo "Failed to drop table: $table - " . $conn->error . "\n";
        }
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$sqlFile = __DIR__ . '/database/nrsc_catering.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Basic split by semicolon. This works for simple SQL files.
// For complex ones with procedures, we might need a better parser.
$queries = explode(';', $sql);

echo "Executing SQL queries...\n";
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if ($conn->query($query) === TRUE) {
        // Echo first line of query
        echo "OK: " . substr(str_replace(["\n", "\r"], " ", $query), 0, 100) . "...\n";
    } else {
        echo "ERROR: " . $conn->error . "\n";
        echo "QUERY: " . substr($query, 0, 200) . "...\n";
    }
}

echo "\nDatabase fix complete.\n";
?>
