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
$name = sanitize($_POST['name'] ?? '');
$employee_code = sanitize($_POST['employee_code'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($name) || empty($employee_code)) {
    redirect('../index.php?error=empty', 'Please fill in all necessary fields', 'error');
}

// Attempt to find user in registration system by code
$user = fetchOne(
    "SELECT * FROM users WHERE userid = ?",
    [$employee_code],
    "s"
);

// Auto-register from VIMIS if not found locally
if (!$user) {
    $vimis = fetchOne("SELECT * FROM VIMIS_EMPLOYEE WHERE EMPLOYEECODE = ?", [$employee_code], "s");
    if ($vimis) {
        $hashedCmd = password_hash($employee_code, PASSWORD_DEFAULT);
        $newId = insertAndGetId(
            "INSERT INTO users (userid, password, name, role, status) VALUES (?, ?, ?, 'employee', 'active')",
            [$employee_code, $hashedCmd, $vimis['EMPLOYEENAME']],
            "sss"
        );
        if ($newId) {
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$newId], "i");
        }
    }
}

if ($user) {
    // Validate Name and Password (must match code) matches
    $nameMatch = (strtolower(trim($user['name'])) === strtolower(trim($name)));
    $passMatch = (trim($password) === trim($employee_code));
    
    if (!$nameMatch || !$passMatch) {
        redirect('../index.php?error=mismatch', 'Invalid name, employee code, or password. Please try again.', 'error');
    }
    // Check if user is active
    if ($user['status'] != 'active') {
        die("Account not approved by admin");
    }

    /**
     * Requirement: Skip password check for Demo mode OR verify if set
     * In a real system, you would do: 
     * if (!password_verify($password, $user['password'])) { ... }
     */
    
    /**
     * Store in session
     */
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_code'] = $user['userid'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['login_time'] = time();
    // Log activity
    insertAndGetId(
        "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$user['id'], 'login', 'User logged in via local table (Demo)', $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
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
     * Requirement: Handle "Invalid Credentials" error
     */
    redirect('../index.php?error=invalid', 'Invalid credentials or role selection. Please try again.', 'error');
}
?>

