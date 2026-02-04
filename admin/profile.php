<?php
/**
 * Admin Profile Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Admin Profile';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

include __DIR__ . '/../includes/header.php';

// Include the profile fragment
include __DIR__ . '/../sections/admin/profile.php';

include __DIR__ . '/../includes/footer.php';
?>
