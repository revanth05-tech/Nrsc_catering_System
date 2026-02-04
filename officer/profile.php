<?php
/**
 * Officer Profile Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('officer');

$pageTitle = 'Officer Profile';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

include __DIR__ . '/../includes/header.php';

// Include the profile fragment
include __DIR__ . '/../sections/officer/profile.php';

include __DIR__ . '/../includes/footer.php';
?>
