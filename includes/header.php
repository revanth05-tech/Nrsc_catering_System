<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Fetch Current User
|--------------------------------------------------------------------------
*/
$currentUser = null;

if (isset($_SESSION['user_id'])) {
    $currentUser = fetchOne(
        "SELECT * FROM users WHERE id = ?",
        [$_SESSION['user_id']],
        "i"
    );
}

/*
|--------------------------------------------------------------------------
| Safe Variables
|--------------------------------------------------------------------------
*/
$pageTitle = $pageTitle ?? 'Dashboard';

$userName = $currentUser['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));

$userRoleKey = $_SESSION['role'] ?? 'employee';
$userRole = ROLE_LABELS[$userRoleKey] ?? 'User';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NRSC Catering Services Management System">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/catering_system/assets/css/main.css">
    <link rel="stylesheet" href="/catering_system/assets/css/dashboard.css">
    <link rel="stylesheet" href="/catering_system/assets/css/convo.css">
    <link rel="stylesheet" href="/catering_system/assets/css/request_form.css">

    <style>
        .field-error {
            color: var(--error-500);
            font-size: 0.75rem;
            display: block;
            margin-top: 4px;
        }

        input.error,
        select.error,
        textarea.error {
            border-color: var(--error-500);
        }
    </style>
</head>
<body>

<div class="convo-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="sidebar-overlay"></div>

    <main class="convo-main">

        <!-- Header -->
        <header class="convo-header">
            <div class="header-left">
                <button class="mobile-menu-btn" aria-label="Toggle menu">
                    ☰
                </button>

                <div>
                    <h1 class="page-title">
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </h1>
                    <p class="text-muted" style="font-size: 0.8rem;">
                        NRSC Catering Management
                    </p>
                </div>
            </div>

            <div class="header-right">
                <div class="user-dropdown">
                    <span class="user-name">
                        <?php echo htmlspecialchars($userName); ?>
                    </span>

                    <div class="user-dropdown-avatar">
                        <?php if (!empty($currentUser['profile_image'])): ?>
                            <img src="/catering_system/<?php echo htmlspecialchars($currentUser['profile_image']); ?>" 
                                 alt="Profile" 
                                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo $userInitials; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="convo-content">
            <?php
            if (function_exists('displayFlashMessage')) {
                echo displayFlashMessage();
            }
            ?>
