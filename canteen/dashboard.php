<?php
/**
 * Canteen Dashboard - Manage Orders
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('canteen');

$pageTitle = 'Canteen Dashboard';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $newStatus = sanitize($_POST['new_status'] ?? '');
    
    if ($requestId && in_array($newStatus, ['in_progress', 'completed'])) {
        executeAndGetAffected(
            "UPDATE catering_requests SET status = ? WHERE id = ?",
            [$newStatus, $requestId], "si"
        );
        redirect('dashboard.php', 'Order status updated!', 'success');
    }
}

// Get stats
$stats = [
    'approved' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'approved'")['count'] ?? 0,
    'in_progress' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'in_progress'")['count'] ?? 0,
    'today' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status IN ('approved', 'in_progress') AND event_date = CURDATE()")['count'] ?? 0,
    'completed_today' => fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'completed' AND DATE(updated_at) = CURDATE()")['count'] ?? 0,
];

// Get active orders
$orders = fetchAll(
    "SELECT cr.*, u.name as employee_name, u.department 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE cr.status IN ('approved', 'in_progress') 
     ORDER BY cr.event_date ASC, cr.event_time ASC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">New Orders</div>
            <div class="stat-value"><?php echo $stats['approved']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">In Progress</div>
            <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Today's Orders</div>
            <div class="stat-value"><?php echo $stats['today']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Completed Today</div>
            <div class="stat-value"><?php echo $stats['completed_today']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Active Orders</h3>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="text-center p-6">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <p class="text-muted mt-4">No active orders. All caught up!</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Event</th>
                            <th>Venue</th>
                            <th>Date & Time</th>
                            <th>Guests</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['request_number']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['employee_name']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['venue']); ?></td>
                            <td>
                                <strong><?php echo formatDate($order['event_date']); ?></strong><br>
                                <?php echo date('h:i A', strtotime($order['event_time'])); ?>
                            </td>
                            <td><strong><?php echo $order['guest_count']; ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="pending_orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if ($order['status'] === 'approved'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="in_progress">
                                        <button type="submit" class="btn btn-sm btn-primary">Start</button>
                                    </form>
                                <?php elseif ($order['status'] === 'in_progress'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="completed">
                                        <button type="submit" class="btn btn-sm btn-success">Complete</button>
                                    </form>
                                <?php endif; ?>
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
