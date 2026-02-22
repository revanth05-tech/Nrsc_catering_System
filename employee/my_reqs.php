<?php
/**
 * My Requests - View all employee requests
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$pageTitle = 'My Requests';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'] ?? 0;

// Get filter
$statusFilter = $_GET['status'] ?? '';
$whereClause = "employee_id = ?";
$params = [$userId];
$types = "i";

if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled'])) {
    $whereClause .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$requests = fetchAll(
    "SELECT * FROM catering_requests WHERE $whereClause ORDER BY created_at DESC",
    $params, $types
);

include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between mb-6">
    <div>
        <a href="new_request.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Request
        </a>
    </div>
    <div class="d-flex gap-2">
        <a href="?status=" class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
        <a href="?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
        <a href="?status=approved" class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">Approved</a>
        <a href="?status=completed" class="btn btn-sm <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Completed</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div class="text-center p-6">
                <p class="text-muted mb-4">No requests found.</p>
                <a href="new_request.php" class="btn btn-primary">Create Your First Request</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Event</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['meeting_name']); ?></td>
                            <td>
                                <?php echo formatDate($req['meeting_date']); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($req['meeting_time'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($req['area']); ?></td>
                            <td><?php echo $req['guest_count']; ?></td>
                            <td><?php echo formatCurrency($req['total_amount']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $req['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <a href="edit_request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <?php else: ?>
                                    <a href="edit_request.php?id=<?php echo $req['id']; ?>&view=1" class="btn btn-sm btn-secondary">View</a>
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
