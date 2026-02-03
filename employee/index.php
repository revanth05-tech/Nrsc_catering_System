<?php
// employee/index.php - Main Controller for Employee Module
require_once '../config/config.php';
require_once '../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

$role = 'employee';
$section = $_GET['section'] ?? 'home';

// Whitelist sections
$allowed_sections = ['home', 'form', 'history', 'profile'];
if (!in_array($section, $allowed_sections)) $section = 'home';

// Page Titles
$titles = [
    'home' => 'Employee Dashboard',
    'form' => 'New Catering Request',
    'history' => 'My Request History',
    'profile' => 'My Profile'
];
$pageTitle = $titles[$section];

// Helper to show flash messages (if not already in config)
if (!function_exists('displayFlashMessage')) {
    function displayFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            return "<div class='alert alert-{$type}'>{$msg}</div>";
        }
        return '';
    }
}

// Load the Master Layout
require_once '../layout/app.php';
?>
