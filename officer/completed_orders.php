<?php
/**
 * Completed Orders List
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('officer');

$pageTitle = 'Completed Orders';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$requests = fetchAll(
    "SELECT cr.*, u.name as employee_name, u.department 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE cr.status = 'completed' 
     ORDER BY cr.updated_at DESC 
     LIMIT 50"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Completed Orders</h3>
    </div>
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <p class="text-center text-muted p-6">No completed orders yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Employee</th>
                            <th>Event</th>
                            <th>Event Date</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Completed On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['event_name']); ?></td>
                            <td><?php echo formatDate($req['event_date']); ?></td>
                            <td><?php echo $req['guest_count']; ?></td>
                            <td><?php echo formatCurrency($req['total_amount']); ?></td>
                            <td><?php echo formatDate($req['updated_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
