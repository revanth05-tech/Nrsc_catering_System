<?php
// sections/employee/home.php - Dashboard Fragment

// 1. Logic (Keep PHP Logic Intact)
$userId = $_SESSION['user_id'] ?? 0;
$stats = [
    'total' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ?", [$userId], "i")['count'] ?? 0,
    'pending' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ? AND status = 'pending'", [$userId], "i")['count'] ?? 0,
    'approved' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ? AND status = 'approved'", [$userId], "i")['count'] ?? 0,
    'completed' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE employee_id = ? AND status = 'completed'", [$userId], "i")['count'] ?? 0,
];

$recentRequests = fetchAll(
    "SELECT * FROM catering_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5",
    [$userId], "i"
);
?>

<!-- 2. View (ConvoManage Structure) -->
<h2 class="mb-6" style="color: white; font-size: 1.25rem;">Overview</h2>

<!-- Stats Grid -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Requests</div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="text-muted text-xs">All time history</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--accent-500);">
        <div class="stat-label text-warning">Pending</div>
        <div class="stat-value"><?php echo $stats['pending']; ?></div>
        <div class="text-muted text-xs">Awaiting approval</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--success-500);">
        <div class="stat-label text-success">Approved</div>
        <div class="stat-value"><?php echo $stats['approved']; ?></div>
        <div class="text-muted text-xs">Ready for processing</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="d-flex gap-2 mb-6">
    <a href="?section=form" class="btn btn-primary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        New Request
    </a>
    <a href="?section=history" class="btn btn-secondary">
        View All History
    </a>
</div>

<!-- Recent Activity Table -->
<div class="table-wrapper">
    <div class="table-header-row">
        <h3 class="mb-0 text-base font-semibold text-white">Recent Requests</h3>
    </div>
    
    <?php if (empty($recentRequests)): ?>
        <div class="p-6 text-center text-muted">
            <p>No recent requests found.</p>
        </div>
    <?php else: ?>
        <table class="w-full">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentRequests as $req): ?>
                <tr>
                    <td>
                        <span class="font-mono text-xs text-primary"><?php echo htmlspecialchars($req['request_number']); ?></span>
                    </td>
                    <td>
                        <div class="font-medium text-white"><?php echo htmlspecialchars($req['meeting_name']); ?></div>
                        <div class="text-xs text-muted"><?php echo $req['guest_count']; ?> Guests</div>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($req['meeting_date'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $req['status']; ?>">
                            <?php echo ucfirst($req['status']); ?>
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="view_request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-secondary">
                            View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
