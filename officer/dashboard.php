<?php
/**
 * Officer Dashboard - Pending Approvals
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('officer');

$pageTitle = 'Approving Officer Dashboard';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Get stats
$stats = [
    'pending' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'pending'")['count'] ?? 0,
    'approved_today' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'approved' AND DATE(approved_at) = CURDATE()")['count'] ?? 0,
    'total_approved' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status IN ('approved', 'in_progress', 'completed')")['count'] ?? 0,
];

// Get pending requests
$pendingRequests = fetchAll(
    "SELECT cr.*, u.name as employee_name, u.department 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE cr.status = 'pending' 
     ORDER BY cr.created_at ASC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Pending Approvals</div>
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
            <div class="stat-label">Approved Today</div>
            <div class="stat-value"><?php echo $stats['approved_today']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Approved</div>
            <div class="stat-value"><?php echo $stats['total_approved']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Pending Approval Requests</h3>
    </div>
    <div class="card-body">
        <?php if (empty($pendingRequests)): ?>
            <div class="text-center p-6">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <p class="text-muted mt-4">No pending requests. All caught up!</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Employee</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $req): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($req['employee_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($req['department']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($req['event_name']); ?></td>
                            <td><?php echo formatDate($req['event_date']); ?></td>
                            <td><?php echo $req['guest_count']; ?></td>
                            <td><?php echo formatCurrency($req['total_amount']); ?></td>
                            <td><?php echo formatDate($req['created_at'], 'd M'); ?></td>
                            <td>
                                <a href="update_status.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary">Review</a>
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
