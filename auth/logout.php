<?php
/**
 * Logout Handler
 */
session_start();

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    insertAndGetId(
        "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$_SESSION['user_id'], 'logout', 'User logged out', $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
        "isss"
    );
}

// Destroy session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
header("Location: ../index.php");
exit();
?>
