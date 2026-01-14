<?php
/**
 * Employee Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$pageTitle = 'Employee Dashboard';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Get stats for current user
$userId = $_SESSION['user_id'] ?? 0;
$stats = [
    'total' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ?", [$userId], "i")['count'] ?? 0,
    'pending' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ? AND status = 'pending'", [$userId], "i")['count'] ?? 0,
    'approved' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ? AND status = 'approved'", [$userId], "i")['count'] ?? 0,
    'completed' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ? AND status = 'completed'", [$userId], "i")['count'] ?? 0,
];

// Get recent requests
$recentRequests = fetchAll(
    "SELECT * FROM catering_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5",
    [$userId], "i"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?php echo $stats['approved']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?php echo $stats['completed']; ?></div>
        </div>
    </div>
</div>

<div class="quick-actions">
    <a href="new_request.php" class="quick-action-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="16"></line>
            <line x1="8" y1="12" x2="16" y2="12"></line>
        </svg>
        Create New Request
    </a>
    <a href="my_reqs.php" class="quick-action-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        View All Requests
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Requests</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recentRequests)): ?>
            <p class="text-center text-muted">No requests yet. <a href="new_request.php">Create your first request</a></p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRequests as $req): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['event_name']); ?></td>
                            <td><?php echo formatDate($req['event_date']); ?></td>
                            <td><?php echo $req['guest_count']; ?></td>
                            <td><?php echo formatCurrency($req['total_amount']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $req['status']; ?>">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
