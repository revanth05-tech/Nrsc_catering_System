<?php
/**
 * Officer Profile Page
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('officer');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Page title
$pageTitle = 'Officer Profile';

// Ensure session exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load layout
include __DIR__ . '/../includes/header.php';

// Load profile section safely
$profileSection = __DIR__ . '/../sections/officer/profile.php';

if (file_exists($profileSection)) {
    include $profileSection;
} else {
    echo "<div class='alert alert-error'>Profile section not found.</div>";
}

// Load footer
include __DIR__ . '/../includes/footer.php';
?>
