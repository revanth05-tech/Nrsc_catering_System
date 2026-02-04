<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user info if logged in
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']], "i");
}

$pageTitle = $pageTitle ?? 'Dashboard';
$userInitials = $currentUser ? strtoupper(substr($currentUser['name'], 0, 2)) : 'U';
$userRole = ROLE_LABELS[$_SESSION['role'] ?? 'employee'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NRSC Catering Services Management System">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <!-- Stylesheets with Cache Busting -->
    <!-- Stylesheets with Cache Busting -->
    <link rel="stylesheet" href="/catering_system/assets/css/main.css?v=1">
    <link rel="stylesheet" href="/catering_system/assets/css/dashboard.css?v=1">
    <link rel="stylesheet" href="/catering_system/assets/css/convo.css?v=1">
    
    <style>
        .field-error { color: var(--error-500); font-size: 0.75rem; display: block; margin-top: 4px; }
        input.error, select.error, textarea.error { border-color: var(--error-500); }
    </style>
</head>
<body>
    <div class="convo-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="sidebar-overlay"></div>
        
        <main class="convo-main">
            <!-- Modern Glass Header -->
            <header class="convo-header">
                <div class="header-left d-flex align-items-center gap-4">
                    <button class="mobile-menu-btn" aria-label="Toggle menu" style="color: var(--gray-400);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div>
                        <h1 class="page-title" style="color: var(--gray-50); font-size: 1.25rem; margin: 0;"><?php echo $pageTitle; ?></h1>
                        <p class="text-xs text-muted mb-0" style="margin-top: 2px;">NRSC Catering Management</p>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-dropdown">
                        <span class="text-sm font-medium mr-3 text-muted hidden-xs">
                            <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>
                        </span>
                        <div class="user-dropdown-avatar">
                            <?php echo $userInitials; ?>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="convo-content">
                <?php echo displayFlashMessage(); ?>
