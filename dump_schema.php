<?php
require_once 'config/db.php';
$conn = getConnection();
$result = $conn->query("DESCRIBE users");
while($row = $result->fetch_assoc()) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
}
?>
