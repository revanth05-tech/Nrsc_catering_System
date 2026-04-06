<?php
/**
 * User Approval View
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'User Approval Details';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    redirect('manage_users.php', 'Invalid user ID.', 'error');
}

$user = fetchOne("SELECT * FROM users WHERE id = ? AND status = 'inactive'", [$userId], "i");

if (!$user) {
    redirect('manage_users.php', 'User not found or already processed.', 'error');
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 20px auto;">
    <div class="card-header">
        <h3 class="card-title">Pending Signup Request</h3>
    </div>
    
    <div class="card-body">
        <div style="display: flex; align-items: center; margin-bottom: 2rem;">
            <div style="width: 64px; height: 64px; background: var(--primary-100); color: var(--primary-600); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1.5rem; font-size: 1.5rem; font-weight: 700;">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1.25rem; color: var(--gray-50);"><?php echo htmlspecialchars($user['name']); ?></h4>
                <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--primary-600); font-weight: 600;"><?php echo ROLE_LABELS[$user['role']] ?? ucfirst($user['role']); ?></p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; text-align: left; margin-bottom: 2rem;">
            <div>
                <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem; display: block;">Employee Code</label>
                <div style="font-weight: 600; font-size: 1rem;"><?php echo htmlspecialchars($user['userid']); ?></div>
            </div>
            <div>
                <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem; display: block;">Department</label>
                <div style="font-weight: 600; font-size: 1rem;"><?php echo htmlspecialchars($user['department'] ?? 'General'); ?></div>
            </div>
            <div style="grid-column: span 2;">
                <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem; display: block;">Email Address</label>
                <div style="font-weight: 600; font-size: 1rem; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <div style="grid-column: span 2;">
                <label style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-400); margin-bottom: 0.25rem; display: block;">Requested On</label>
                <div style="font-weight: 600; font-size: 1rem;"><?php echo formatDate($user['created_at'], 'd M Y, h:i A'); ?></div>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <a href="approve_user.php?id=<?php echo $user['id']; ?>" class="btn btn-success" style="flex: 1; justify-content: center;" onclick="return confirm('Are you sure you want to approve this user?');">
                <i class="fa-solid fa-check mr-2"></i> Approve User
            </a>
            <a href="reject_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger" style="flex: 1; justify-content: center;" onclick="return confirm('Are you sure you want to reject this user?');">
                <i class="fa-solid fa-xmark mr-2"></i> Reject User
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
