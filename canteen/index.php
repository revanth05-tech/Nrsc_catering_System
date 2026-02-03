<?php
// canteen/index.php - Canteen Controller
require_once '../config/config.php';
require_once '../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'canteen') {
    header("Location: ../auth/login.php");
    exit();
}

$role = 'canteen';
$section = $_GET['section'] ?? 'home';

$allowed_sections = ['home', 'orders', 'history', 'profile'];
if (!in_array($section, $allowed_sections)) $section = 'home';

$titles = [
    'home' => 'Canteen Dashboard',
    'orders' => 'Pending Orders',
    'history' => 'Order History',
    'profile' => 'My Profile'
];
$pageTitle = $titles[$section];

// Helper for Flash
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

require_once '../layout/app.php';
?>
