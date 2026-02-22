<?php
require_once __DIR__ . '/../config/db.php';

$conn = getConnection();

// Rename full_name to name if full_name exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
if ($result->num_rows > 0) {
    echo "Renaming 'full_name' to 'name'...\n";
    $sql = "ALTER TABLE users CHANGE full_name name VARCHAR(100) NOT NULL";
    if ($conn->query($sql)) {
        echo "Successfully renamed 'full_name' to 'name'.\n";
    } else {
        echo "Error renaming column: " . $conn->error . "\n";
    }
} else {
    echo "'full_name' column not found.\n";
    
    // Check if name already exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
    if ($result->num_rows == 0) {
        echo "Critial: Neither 'full_name' nor 'name' exists in users table!\n";
    } else {
        echo "'name' column already exists.\n";
    }
}

echo "Database schema update completed.\n";
?>
