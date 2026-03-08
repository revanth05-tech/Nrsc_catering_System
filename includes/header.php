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

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

        .notification-icon {
            position: relative;
            margin-right: 20px;
            color: var(--gray-400);
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .notification-icon:hover {
            color: var(--primary-500);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
            font-weight: bold;
            border: 2px solid white;
            min-width: 18px;
            text-align: center;
        }
    </style>
</head>
<body>

<?php
// Fetch unread notifications for all users
$notificationCount = 0;
if (isset($_SESSION['user_id'])) {
    $notif = fetchOne("SELECT COUNT(*) as active_count FROM notifications WHERE user_id = ? AND is_read = 0", [$_SESSION['user_id']], "i");
    $notificationCount = $notif['active_count'] ?? 0;
}

// Fetch inactive users for admin notification (keep for compatibility)
$inactiveCount = 0;
$pendingUsersList = [];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $pendingUsersList = fetchAll("SELECT id, name, userid, email, department, role, created_at FROM users WHERE status = 'inactive' ORDER BY created_at DESC");
    $inactiveCount = count($pendingUsersList);
}

// Total badge count
$totalBadgeCount = $notificationCount + $inactiveCount;
?>

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

            <div class="header-right" style="display: flex; align-items: center;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo APP_URL; ?>/notifications/notifications.php" class="notification-icon" title="Notifications">
                        <i class="fa-solid fa-envelope"></i>
                        <?php if ($totalBadgeCount > 0): ?>
                            <span class="notification-badge"><?php echo $totalBadgeCount; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $inactiveCount > 0): ?>
                    <!-- Hidden button to keep the modal trigger for now if we want to link it from notifications.php -->
                    <button id="trigger-admin-modal" style="display:none;" onclick="document.getElementById('pending-users-modal').style.display='flex'"></button>
                    
                    <!-- Pending Approvals Modal Overlay -->
                    <div id="pending-users-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:10000; align-items:center; justify-content:center; min-height:100vh; backdrop-filter: blur(6px); padding: 20px;">
                        <!-- The Modal Popup Card -->
                        <div class="card" style="width: 100%; max-width: 550px; max-height: 90vh; margin: auto; padding: 25px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); border-radius: 12px; border: none; overflow-y: auto; background: white; position: relative;">
                            <div class="card-header flex-between" style="padding: 0 0 1.5rem 0; border-bottom: 1px solid #f1f5f9; background: transparent;">
                                <h3 style="margin:0; font-size: 1.5rem; color: var(--gray-50);">Pending Approvals</h3>
                                <button onclick="document.getElementById('pending-users-modal').style.display='none'" style="background:none; border:none; font-size:1.75rem; cursor:pointer; color:var(--gray-400); padding: 0; line-height: 1;">&times;</button>
                            </div>
                            <div class="card-body" style="padding: 1.5rem 0; background: transparent;">
                                <?php if ($inactiveCount === 0): ?>
                                    <div class="text-center py-10">
                                        <div style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;">
                                            <i class="fa-solid fa-user-check"></i>
                                        </div>
                                        <p class="text-muted">No pending user approvals at the moment.</p>
                                    </div>
                                <?php else: ?>
                                    <div id="pending-cards-container" style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                                        <?php foreach ($pendingUsersList as $user): ?>
                                        <div class="approval-card" id="pending-user-row-<?php echo $user['id']; ?>" style="width: 100%; max-width: 450px; background: white; border-radius: 12px; box-shadow: var(--shadow-md); overflow: hidden; border: 1px solid var(--gray-700); transition: all 0.3s ease;">
                                            <div style="padding: 1.5rem; text-align: center; border-bottom: 1px solid #f1f5f9; background: linear-gradient(to bottom, #f8fafc, #ffffff);">
                                                <div style="width: 64px; height: 64px; background: var(--primary-100); color: var(--primary-600); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; font-weight: 700;">
                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                </div>
                                                <h4 style="margin: 0; font-size: 1.25rem; color: var(--gray-50);"><?php echo htmlspecialchars($user['name']); ?></h4>
                                                <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--primary-600); font-weight: 600;"><?php echo ROLE_LABELS[$user['role']] ?? ucfirst($user['role']); ?></p>
                                            </div>
                                            
                                            <div style="padding: 1.5rem;">
                                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; text-align: left;">
                                                    <div>
                                                        <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem;">User ID</label>
                                                        <div style="font-weight: 600; font-size: 0.875rem;"><?php echo htmlspecialchars($user['userid']); ?></div>
                                                    </div>
                                                    <div>
                                                        <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem;">Department</label>
                                                        <div style="font-weight: 600; font-size: 0.875rem;"><?php echo htmlspecialchars($user['department'] ?? 'General'); ?></div>
                                                    </div>
                                                    <div style="grid-column: span 2;">
                                                        <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem;">Email Address</label>
                                                        <div style="font-weight: 600; font-size: 0.875rem; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></div>
                                                    </div>
                                                    <div style="grid-column: span 2;">
                                                        <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem;">Requested On</label>
                                                        <div style="font-weight: 600; font-size: 0.875rem;"><?php echo formatDate($user['created_at'], 'd M Y, h:i A'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                                <button onclick="processUserAction('approve', <?php echo $user['id']; ?>)" class="btn btn-success btn-block" style="padding: 0.75rem;">
                                                    <i class="fa-solid fa-check mr-2"></i> Approve
                                                </button>
                                                <button onclick="processUserAction('reject', <?php echo $user['id']; ?>)" class="btn btn-danger btn-block" style="padding: 0.75rem;">
                                                    <i class="fa-solid fa-xmark mr-2"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-center" style="padding: 1rem 0 0 0; background: transparent; border-top: 1px solid #f1f5f9;">
                                <button type="button" onclick="document.getElementById('pending-users-modal').style.display='none'" class="btn btn-secondary" style="min-width: 140px; border-radius: 8px;">Done</button>
                            </div>
                        </div>
                    </div>

                    <script>
                    function processUserAction(action, userId) {
                        const actionLabel = action === 'approve' ? 'approve' : 'reject';
                        if (!confirm('Are you sure you want to ' + actionLabel + ' this registration?')) return;

                        // Disable buttons in the card to prevent double clicks
                        const card = document.getElementById('pending-user-row-' + userId);
                        const buttons = card.querySelectorAll('button');
                        buttons.forEach(btn => btn.disabled = true);
                        card.style.opacity = '0.7';

                        fetch(`/catering_system/admin/${action}_user.php?id=${userId}&ajax=1`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Smoothly remove the card
                                    card.style.transform = 'scale(0.95)';
                                    card.style.opacity = '0';
                                    card.style.transition = 'all 0.3s ease';
                                    
                                    setTimeout(() => {
                                        card.remove();
                                        
                                        // Update the notification badge
                                        const badge = document.querySelector('.notification-badge');
                                        if (badge) {
                                            let count = parseInt(badge.textContent);
                                            count--;
                                            if (count > 0) {
                                                badge.textContent = count;
                                            } else {
                                                badge.remove();
                                            }
                                        }

                                        // Check if container is now empty
                                        const container = document.getElementById('pending-cards-container');
                                        if (container && container.children.length === 0) {
                                            document.querySelector('#pending-users-modal .card-body').innerHTML = 
                                                '<div class="text-center py-10"><div style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"><i class="fa-solid fa-user-check"></i></div><p class="text-muted">No pending user approvals at the moment.</p></div>';
                                        }
                                    }, 300);
                                } else {
                                    alert('Error: ' + data.message);
                                    buttons.forEach(btn => btn.disabled = false);
                                    card.style.opacity = '1';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while processing the request.');
                                buttons.forEach(btn => btn.disabled = false);
                                card.style.opacity = '1';
                            });
                    }
                    </script>
                <?php endif; ?>

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
