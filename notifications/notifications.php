<?php
/**
 * Universal Notifications Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Notifications';
$userCode = $_SESSION['user_code'] ?? '';
$userRole = $_SESSION['role'] ?? '';

// Mark all as read when opening the page
if ($userCode) {
    executeAndGetAffected("UPDATE notifications SET is_read = 1 WHERE user_code = ?", [$userCode], "s");
}

// Handle clearing notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_notifications'])) {
    if ($userCode) {
        executeAndGetAffected("DELETE FROM notifications WHERE user_code = ?", [$userCode], "s");
        redirect('notifications.php', 'All notifications cleared.', 'success');
    }
}

// Fetch notifications
$whereClause = ($userRole === 'admin') ? "user_code = ? OR role = 'admin'" : "user_code = ?";
$notifications = fetchAll(
    "SELECT * FROM notifications WHERE {$whereClause} ORDER BY created_at DESC LIMIT 50",
    [$userCode], "s"
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
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon-box">
                            <?php 
                                $iconClass = 'fa-bell';
                                if (stripos($notification['message'], 'approved') !== false) $iconClass = 'fa-circle-check';
                                if (stripos($notification['message'], 'rejected') !== false) $iconClass = 'fa-circle-xmark';
                                if (stripos($notification['message'], 'completed') !== false) $iconClass = 'fa-clipboard-check';
                                if (stripos($notification['message'], 'new catering request') !== false) $iconClass = 'fa-file-invoice';
                            ?>
                            <i class="fa-solid <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-time">
                                <i class="fa-regular fa-clock mr-1"></i> <?php echo formatDate($notification['created_at'], 'd M Y, h:i A'); ?>
                            </div>
                        </div>
                        <?php if ($notification['link']): ?>
                        <div class="notification-actions">
                            <button class="btn btn-sm btn-outline view-details-btn" data-code="<?php echo $notification['user_code']; ?>" style="white-space: nowrap;">
                                View Details &rarr;
                            </button>
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
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(6px);
  z-index: 9999;
}

.modal-content {
  background: rgba(255, 255, 255, 0.95);
  width: 440px;
  margin: 6% auto;
  padding: 25px 28px;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  backdrop-filter: blur(10px);
  animation: fadeInUp 0.3s ease;
  position: relative;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-content h2, .modal-content h3 {
  font-size: 22px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 15px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 12px;
}

.modal-content p {
  font-size: 14.5px;
  margin: 10px 0;
  color: #334155;
  line-height: 1.5;
}

.modal-content strong {
  color: #0f172a;
  font-weight: 600;
}

.modal-content button {
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
}

button[value="approve"] {
  background: #16a34a;
  color: white;
  flex: 2;
}

button[value="approve"]:hover {
  background: #15803d;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
}

button[value="reject"] {
  background: transparent;
  color: #dc2626;
  flex: 1;
}

button[value="reject"]:hover {
  color: #b91c1c;
  text-decoration: underline;
  background: rgba(220, 38, 38, 0.05);
}

.modal-content form {
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.close {
  position: absolute;
  right: 20px;
  top: 20px;
  font-size: 20px;
  cursor: pointer;
  color: #64748b;
  transition: color 0.2s;
  line-height: 1;
  z-index: 10;
}

.close:hover {
  color: #0f172a;
}

</style>

<div id="approvalModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <div id="modalBody">
        Loading...
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.view-details-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    let userCode = this.getAttribute('data-code');

    fetch('get_user_details.php?code=' + userCode)
      .then(res => res.text())
      .then(data => {
        document.getElementById('modalBody').innerHTML = data;
        document.getElementById('approvalModal').style.display = 'block';
      });
  });
});

document.querySelector('.close').onclick = function() {
  document.getElementById('approvalModal').style.display = 'none';
};

// Optional: Close modal if clicking outside the content box
window.onclick = function(event) {
  let modal = document.getElementById('approvalModal');
  if (event.target == modal) {
    modal.style.display = 'none';
  }
};
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
