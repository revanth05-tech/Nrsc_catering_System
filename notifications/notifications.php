<?php
/**
 * Universal Notifications Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Notifications';
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

// Mark all as read when opening the page
if ($userId > 0) {
    executeAndGetAffected("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId], "i");
}

// Handle clearing notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_notifications'])) {
    if ($userId > 0) {
        executeAndGetAffected("DELETE FROM notifications WHERE user_id = ?", [$userId], "i");
        redirect('notifications.php', 'All notifications cleared.', 'success');
    }
}

// Fetch notifications
$notifications = fetchAll(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
    [$userId], "i"
);

// Admin specific: Fetch pending user approvals to show as priority
$pendingUsers = [];
if ($userRole === 'admin') {
    $pendingUsers = fetchAll("SELECT * FROM users WHERE status = 'inactive' ORDER BY created_at DESC");
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header flex-between">
        <div>
            <h3 class="card-title">Notifications</h3>
            <p class="text-muted" style="font-size: 0.85rem;">Stay updated with system activities</p>
        </div>
        <div class="header-actions" style="display: flex; gap: 10px; align-items: center;">
            <?php if (!empty($notifications)): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear all notifications? This action cannot be undone.');">
                    <button type="submit" name="clear_notifications" class="btn btn-sm btn-danger" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <i class="fa-solid fa-trash-can mr-1"></i> Clear All
                    </button>
                </form>
                <span class="badge badge-primary"><?php echo count($notifications); ?> Recent</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if ($userRole === 'admin' && !empty($pendingUsers)): ?>
            <div style="background: var(--warning-50); padding: 1.5rem; border-bottom: 2px solid var(--warning-100);">
                <h5 style="color: var(--warning-700); margin-bottom: 1rem; display: flex; align-items: center;">
                    <i class="fa-solid fa-user-clock mr-2" style="font-size: 1.2rem;"></i> 
                    Pending User Approvals (Action Required)
                </h5>
                <div class="notification-list">
                    <?php foreach ($pendingUsers as $user): ?>
                        <div class="notification-item unread" style="background: white; border-radius: 8px; margin-bottom: 0.75rem; border: 1px solid var(--warning-200); box-shadow: var(--shadow-sm);">
                            <div class="notification-content">
                                <div class="notification-message">
                                    <strong>New Signup:</strong> <?php echo htmlspecialchars($user['name']); ?> has requested an account as <strong><?php echo ROLE_LABELS[$user['role']] ?? ucfirst($user['role']); ?></strong>.
                                </div>
                                <div class="notification-time">
                                    <i class="fa-regular fa-clock mr-1"></i> <?php echo formatDate($user['created_at'], 'd M Y, h:i A'); ?>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <button onclick="document.getElementById('trigger-admin-modal').click()" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-user-check mr-1"></i> Review
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($notifications) && ($userRole !== 'admin' || empty($pendingUsers))): ?>
            <div class="text-center py-10" style="padding: 4rem 2rem;">
                <div style="font-size: 4rem; color: var(--gray-700); margin-bottom: 1.5rem; opacity: 0.5;">
                    <i class="fa-solid fa-bell-slash"></i>
                </div>
                <h4>No Notifications</h4>
                <p class="text-muted">You're all caught up! New alerts will appear here.</p>
            </div>
        <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon-box">
                            <?php 
                                $iconClass = 'fa-bell';
                                if (stripos($notif['message'], 'approved') !== false) $iconClass = 'fa-circle-check';
                                if (stripos($notif['message'], 'rejected') !== false) $iconClass = 'fa-circle-xmark';
                                if (stripos($notif['message'], 'completed') !== false) $iconClass = 'fa-clipboard-check';
                                if (stripos($notif['message'], 'new catering request') !== false) $iconClass = 'fa-file-invoice';
                            ?>
                            <i class="fa-solid <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notification-time">
                                <i class="fa-regular fa-clock mr-1"></i> <?php echo formatDate($notif['created_at'], 'd M Y, h:i A'); ?>
                            </div>
                        </div>
                        <?php if ($notif['link']): ?>
                        <div class="notification-actions">
                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn btn-sm btn-outline">
                                View Details <i class="fa-solid fa-chevron-right ml-1" style="font-size: 0.7rem;"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-list {
    display: flex;
    flex-direction: column;
}
.notification-item {
    display: flex;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-800);
    transition: all 0.2s ease;
}
.notification-item:hover {
    background: var(--gray-900);
}
.notification-item.unread {
    background: #f0f7ff;
    border-left: 4px solid var(--primary-500);
}
.notification-icon-box {
    width: 40px;
    height: 40px;
    background: var(--gray-800);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.25rem;
    color: var(--gray-400);
    flex-shrink: 0;
}
.unread .notification-icon-box {
    background: var(--primary-100);
    color: var(--primary-600);
}
.notification-content {
    flex: 1;
}
.notification-message {
    color: var(--gray-50);
    font-weight: 500;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}
.notification-time {
    font-size: 0.8rem;
    color: var(--gray-400);
}
.notification-actions {
    margin-left: 1.5rem;
}
.mr-2 { margin-right: 0.5rem; }
.ml-1 { margin-left: 0.25rem; }
.mr-1 { margin-right: 0.25rem; }
.badge-primary {
    background: var(--primary-500);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
