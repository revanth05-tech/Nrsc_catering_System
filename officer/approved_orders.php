<?php
/**
 * Approved Orders List
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('officer');

$pageTitle = 'Approved Orders';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$requests = fetchAll(
    "SELECT cr.*, u.name as employee_name, u.department 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE cr.status IN ('approved', 'in_progress') 
     ORDER BY cr.event_date ASC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Approved Orders</h3>
    </div>
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <p class="text-center text-muted p-6">No approved orders found.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Employee</th>
                            <th>Event</th>
                            <th>Event Date</th>
                            <th>Venue</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Approved On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($req['employee_name'] ?? 'Unknown'); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($req['department'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($req['event_name']); ?></td>
                            <td><?php echo formatDate($req['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($req['venue']); ?></td>
                            <td><?php echo formatCurrency($req['total_amount']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $req['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $req['approved_at'] ? formatDate($req['approved_at']) : '-'; ?>
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
