<?php
/**
 * Authentication Guard
 * Include this file at the top of protected pages
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['userid']) || !isset($_SESSION['role'])) {
    header("Location: /catering_system/index.php");
    exit();
}

// Optional: Role-based access control
function requireRole($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: /catering_system/index.php?error=unauthorized");
        exit();
    }
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentRole() {
    return $_SESSION['role'] ?? null;
}
?>
