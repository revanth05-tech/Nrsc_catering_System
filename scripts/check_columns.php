<?php
require_once __DIR__ . '/../config/db.php';

$conn = getConnection();
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "Columns in 'users' table:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error describing users table: " . $conn->error . "\n";
}
?>
