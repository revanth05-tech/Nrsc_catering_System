<?php
/**
 * Database Alignment and Credential Reset Script
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/config.php';

$conn = getConnection();

echo "Starting database alignment...\n";

// 1. Get current columns
$result = $conn->query("SHOW COLUMNS FROM users");
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo "Current columns: " . implode(', ', $columns) . "\n";

// 2. Standardize Login Column (prefer 'userid')
if (in_array('username', $columns) && !in_array('userid', $columns)) {
    echo "Renaming 'username' to 'userid'...\n";
    $conn->query("ALTER TABLE users CHANGE username userid VARCHAR(50) NOT NULL UNIQUE");
} elseif (!in_array('userid', $columns) && !in_array('username', $columns)) {
    echo "Adding 'userid' column...\n";
    $conn->query("ALTER TABLE users ADD userid VARCHAR(50) NOT NULL UNIQUE AFTER id");
}

// 3. Standardize Name Column (use 'full_name' as requested and as code expects)
if (in_array('name', $columns) && !in_array('full_name', $columns)) {
    echo "Renaming 'name' to 'full_name'...\n";
    $conn->query("ALTER TABLE users CHANGE name full_name VARCHAR(100) NOT NULL");
} elseif (!in_array('full_name', $columns) && !in_array('name', $columns)) {
    echo "Adding 'full_name' column...\n";
    $conn->query("ALTER TABLE users ADD full_name VARCHAR(100) NOT NULL AFTER password");
}

// 4. Ensure other columns exist
$required = [
    'email' => "VARCHAR(100)",
    'role' => "ENUM('employee', 'officer', 'canteen', 'admin') NOT NULL DEFAULT 'employee'",
    'department' => "VARCHAR(100)",
    'status' => "ENUM('active', 'inactive') DEFAULT 'active'",
];

foreach ($required as $col => $definition) {
    if (!in_array($col, $columns) && !in_array($col, ['full_name', 'userid'])) { // already handled
        echo "Adding missing column '$col'...\n";
        $conn->query("ALTER TABLE users ADD $col $definition");
    }
}

echo "Column alignment completed.\n";

// 5. Reset credentials
echo "Resetting passwords and ensuring test accounts exist...\n";
$testUsers = [
    'admin' => ['pass' => 'admin123', 'role' => 'admin', 'name' => 'System Administrator'],
    'officer1' => ['pass' => 'officer123', 'role' => 'officer', 'name' => 'Approving Officer'],
    'canteen1' => ['pass' => 'canteen123', 'role' => 'canteen', 'name' => 'Canteen Manager'],
    'emp001' => ['pass' => 'emp123', 'role' => 'employee', 'name' => 'Test Employee']
];

foreach ($testUsers as $uid => $data) {
    $hashed = password_hash($data['pass'], PASSWORD_DEFAULT);
    
    // Check if user exists (using standardized 'userid' column)
    $stmt = $conn->prepare("SELECT id FROM users WHERE userid = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        echo "Updating password for $uid...\n";
        $stmt = $conn->prepare("UPDATE users SET password = ?, full_name = ?, role = ?, status = 'active' WHERE userid = ?");
        $stmt->bind_param("ssss", $hashed, $data['name'], $data['role'], $uid);
        $stmt->execute();
    } else {
        echo "Creating account for $uid...\n";
        $stmt = $conn->prepare("INSERT INTO users (userid, password, full_name, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssss", $uid, $hashed, $data['name'], $data['role']);
        $stmt->execute();
    }
}

echo "Database alignment and credential reset finished successfully.\n";
?>
