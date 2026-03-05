<?php
/**
 * Login Handler - NRSC Catering System
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit();
}

// Get and sanitize inputs
$userid = sanitize($_POST['userid'] ?? '');
$password = $_POST['password'] ?? '';
$role = sanitize($_POST['role'] ?? '');

// Validate inputs
if (empty($userid) || empty($password) || empty($role)) {
    redirect('../index.php?error=empty', 'Please fill in all fields', 'error');
}

/**
 * Requirement: Use prepared statements
 * Requirement: Validate user by userid
 */
$user = fetchOne(
    "SELECT * FROM users WHERE userid = ?",
    [$userid],
    "s"
);

/**
 * Requirement: Verify password using password_verify()
 */
if ($user && password_verify($password, $user['password'])) {
    
    // Check if role matches the selected role
    if ($user['role'] !== $role) {
        redirect('../index.php?error=role', 'Incorrect role selected for this user.', 'error');
    }

    // Check user account status
    if ($user['status'] === 'inactive') {
        redirect('../index.php?error=pending', 'Your account is pending admin approval.', 'warning');
    }

    /**
     * Requirement: Start session on successful login (session_start() is at top)
     * Requirement: Store user id, role, and name in session
     */
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
    
    /**
     * Requirement: Redirect users based on role
     */
    switch ($user['role']) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        case 'officer':
            header("Location: ../officer/dashboard.php");
            break;
        case 'canteen':
            header("Location: ../canteen/dashboard.php");
            break;
        case 'employee':
            header("Location: ../employee/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();

} else {
    /**
     * Requirement: Show proper error message for invalid login
     */
    redirect('../index.php?error=invalid', 'Invalid User ID or password. Please try again.', 'error');
}
?>

