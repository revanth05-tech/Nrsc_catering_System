<?php
// sections/employee/history.php - Request History Fragment

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

function getStatusUrl($status) {
    return "?section=history" . ($status ? "&status=$status" : "");
}
?>

<div class="d-flex flex-between align-items-center mb-6">
    <h2 class="mb-0 text-xl font-bold text-white">My Request History</h2>
    
    <div class="d-flex gap-2 bg-gray-800 p-1 rounded-full border border-gray-700">
        <a href="<?php echo getStatusUrl(''); ?>" 
           class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-ghost'; ?>">All</a>
        
        <a href="<?php echo getStatusUrl('pending'); ?>" 
           class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-ghost'; ?>">Pending</a>
        
        <a href="<?php echo getStatusUrl('approved'); ?>" 
           class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-primary' : 'btn-ghost'; ?>">Approved</a>
           
        <a href="<?php echo getStatusUrl('completed'); ?>" 
           class="btn btn-sm <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-ghost'; ?>">Completed</a>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-header-row">
        <h3 class="mb-0 text-base font-semibold text-white">
            <?php echo $statusFilter ? ucfirst($statusFilter) . ' Requests' : 'All Requests'; ?>
        </h3>
        <span class="text-sm text-muted">Total: <?php echo count($requests); ?></span>
    </div>

    <?php if (empty($requests)): ?>
        <div class="p-10 text-center">
            <div class="mb-4">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-600)" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <p class="text-muted mb-4">No requests found matching this filter.</p>
            <?php if ($statusFilter): ?>
                <a href="?section=history" class="btn btn-secondary text-sm">Clear Filter</a>
            <?php else: ?>
                <a href="?section=form" class="btn btn-primary">Create Your First Request</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="w-full">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Event Details</th>
                    <th>Venue</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td class="font-mono text-primary text-xs">
                        <?php echo htmlspecialchars($req['request_number']); ?>
                    </td>
                    <td>
                        <div class="font-bold text-white"><?php echo htmlspecialchars($req['meeting_name']); ?></div>
                        <div class="text-xs text-muted">
                            <?php echo date('M d, Y', strtotime($req['meeting_date'])); ?> • 
                            <?php echo date('h:i A', strtotime($req['meeting_time'])); ?>
                        </div>
                        <div class="text-xs text-muted"><?php echo $req['guest_count']; ?> guests</div>
                    </td>
                    <td><?php echo htmlspecialchars($req['area']); ?></td>
                    <td class="font-mono text-white">
                        ₹<?php echo number_format($req['total_amount'], 2); ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $req['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                        </span>
                    </td>
                    <td class="text-right">
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
    <?php endif; ?>
</div>
