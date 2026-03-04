<?php
/**
 * Login Handler
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit();
}

$userid   = sanitize($_POST['userid'] ?? '');
$password = $_POST['password'] ?? '';
$role     = sanitize($_POST['role'] ?? '');

// Validate inputs
if (empty($userid) || empty($password) || empty($role)) {
    redirect('../index.php?error=empty', 'Please fill in all fields', 'error');
}

// Find user in database
$user = fetchOne(
    "SELECT * FROM users WHERE userid = ? AND role = ?",
    [$userid, $role],
    "ss"
);

if ($user && password_verify($password, $user['password'])) {
    // Check user status
    if ($user['status'] === 'inactive') {
        redirect('../index.php?error=pending', 'Your account is pending admin approval. Please contact the administrator.', 'warning');
        exit();
    }

    // Successful login (must be active)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['userid'] = $user['userid'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    // Log activity
    insertAndGetId(
        "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$user['id'], 'login', 'User logged in successfully', $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
        "isss"
    );
    
    // Redirect based on role
    switch ($role) {
        case 'employee':
            header("Location: ../employee/dashboard.php");
            break;
        case 'officer':
            header("Location: ../officer/dashboard.php");
            break;
        case 'canteen':
            header("Location: ../canteen/dashboard.php");
            break;
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
} else {
    // Failed login
    redirect('../index.php?error=invalid', 'Invalid credentials. Please try again.', 'error');
}
?>
