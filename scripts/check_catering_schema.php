<?php
require_once __DIR__ . '/../config/db.php';
$conn = getConnection();
$result = $conn->query("DESCRIBE catering_requests");
if ($result) {
    echo "Columns in 'catering_requests' table:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error describing catering_requests table: " . $conn->error . "\n";
}
?>
