<?php
/**
 * NRSC Catering System - Configuration File
 * Contains global settings and constants
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'NRSC Catering Services');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/catering_system');

// Date/Time settings
date_default_timezone_set('Asia/Kolkata');

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Pagination settings
define('ITEMS_PER_PAGE', 10);

// Status labels with colors
define('STATUS_LABELS', [
    'pending' => ['label' => 'Pending', 'class' => 'status-pending', 'color' => '#f39c12'],
    'approved' => ['label' => 'Approved', 'class' => 'status-approved', 'color' => '#27ae60'],
    'rejected' => ['label' => 'Rejected', 'class' => 'status-rejected', 'color' => '#e74c3c'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'status-progress', 'color' => '#3498db'],
    'completed' => ['label' => 'Completed', 'class' => 'status-completed', 'color' => '#2ecc71'],
    'cancelled' => ['label' => 'Cancelled', 'class' => 'status-cancelled', 'color' => '#95a5a6']
]);

// Role labels
define('ROLE_LABELS', [
    'employee' => 'Employee',
    'officer' => 'Approving Officer',
    'canteen' => 'Canteen Staff',
    'admin' => 'Administrator'
]);

// Category labels
define('CATEGORY_LABELS', [
    'breakfast' => 'Breakfast',
    'lunch' => 'Lunch',
    'snacks' => 'Snacks',
    'dinner' => 'Dinner',
    'beverages' => 'Beverages'
]);

/**
 * Generate unique request number
 */
function generateRequestNumber() {
    return 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd M Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime((string)$date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}
?>
