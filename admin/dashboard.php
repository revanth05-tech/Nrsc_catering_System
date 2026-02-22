<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Get stats
$stats = [
    'total_users' => fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'total_items' => fetchOne("SELECT COUNT(*) as count FROM menu_items")['count'] ?? 0,
    'total_requests' => fetchOne("SELECT COUNT(*) as count FROM catering_requests")['count'] ?? 0,
    'pending_requests' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'pending'")['count'] ?? 0,
    'this_month_rearea' => fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM catering_requests WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())")['total'] ?? 0,
];

// Recent activity
$recentActivity = fetchAll(
    "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10"
);

// Recent requests
$recentRequests = fetchAll(
    "SELECT cr.*, u.name as employee_name FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     ORDER BY cr.created_at DESC LIMIT 5"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Menu Items</div>
            <div class="stat-value"><?php echo $stats['total_items']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value"><?php echo $stats['total_requests']; ?></div>
            <div class="stat-change"><?php echo $stats['pending_requests']; ?> pending</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">This Month Rearea</div>
            <div class="stat-value"><?php echo formatCurrency($stats['this_month_rearea']); ?></div>
        </div>
    </div>
</div>

<div class="quick-actions">
    <a href="manage_users.php" class="quick-action-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <line x1="20" y1="8" x2="20" y2="14"></line>
            <line x1="23" y1="11" x2="17" y2="11"></line>
        </svg>
        Manage Users
    </a>
    <a href="manage_items.php" class="quick-action-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add Menu Item
    </a>
    <a href="reports.php" class="quick-action-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
        </svg>
        View Reports
    </a>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Recent Requests</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recentRequests)): ?>
                <p class="text-muted text-center">No requests yet.</p>
            <?php else: ?>
                <div class="request-list">
                    <?php foreach ($recentRequests as $req): ?>
                    <div class="request-item">
                        <div class="request-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            </svg>
                        </div>
                        <div class="request-details">
                            <div class="request-title"><?php echo htmlspecialchars($req['meeting_name']); ?></div>
                            <div class="request-meta">
                                <?php echo htmlspecialchars($req['employee_name']); ?> • <?php echo formatDate($req['meeting_date']); ?>
                            </div>
                        </div>
                        <div class="request-status">
                            <span class="badge badge-<?php echo $req['status']; ?>">
                                <?php echo ucfirst($req['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Recent Activity</h3>
        </div>
        <div class="card-body">
            <div class="activity-feed">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center">No activity yet.</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon info">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text"><?php echo htmlspecialchars($activity['action']); ?></div>
                            <div class="activity-time"><?php echo formatDate($activity['created_at'], 'd M, h:i A'); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
