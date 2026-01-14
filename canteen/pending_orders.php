<?php
/**
 * View Order Details
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('canteen');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    redirect('dashboard.php', 'Invalid order', 'error');
}

$order = fetchOne(
    "SELECT cr.*, u.name as employee_name, u.department, u.phone 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE cr.id = ?",
    [$orderId], "i"
);

if (!$order) {
    redirect('dashboard.php', 'Order not found', 'error');
}

$pageTitle = 'Order Details';

// Get items
$items = fetchAll(
    "SELECT ri.*, mi.item_name, mi.category FROM request_items ri 
     JOIN menu_items mi ON ri.item_id = mi.id 
     WHERE ri.request_id = ?
     ORDER BY mi.category",
    [$orderId], "i"
);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = sanitize($_POST['new_status'] ?? '');
    if (in_array($newStatus, ['in_progress', 'completed'])) {
        executeAndGetAffected(
            "UPDATE catering_requests SET status = ? WHERE id = ?",
            [$newStatus, $orderId], "si"
        );
        redirect('pending_orders.php?id=' . $orderId, 'Status updated!', 'success');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between mb-6">
    <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    <div class="d-flex gap-2">
        <?php if ($order['status'] === 'approved'): ?>
            <form method="POST">
                <input type="hidden" name="new_status" value="in_progress">
                <button type="submit" class="btn btn-primary">Start Preparation</button>
            </form>
        <?php elseif ($order['status'] === 'in_progress'): ?>
            <form method="POST">
                <input type="hidden" name="new_status" value="completed">
                <button type="submit" class="btn btn-success">Mark as Completed</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header flex-between">
        <h3><?php echo htmlspecialchars($order['request_number']); ?></h3>
        <span class="badge badge-<?php echo $order['status']; ?>" style="font-size:14px;padding:8px 16px;">
            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
        </span>
    </div>
    <div class="card-body">
        <div class="form-row three-cols">
            <div>
                <label class="text-muted">Event</label>
                <p class="font-semibold"><?php echo htmlspecialchars($order['event_name']); ?></p>
            </div>
            <div>
                <label class="text-muted">Date & Time</label>
                <p class="font-semibold"><?php echo formatDate($order['event_date']); ?> at <?php echo date('h:i A', strtotime($order['event_time'])); ?></p>
            </div>
            <div>
                <label class="text-muted">Venue</label>
                <p class="font-semibold"><?php echo htmlspecialchars($order['venue']); ?></p>
            </div>
        </div>
        
        <div class="form-row two-cols mt-4">
            <div>
                <label class="text-muted">Requested By</label>
                <p class="font-semibold"><?php echo htmlspecialchars($order['employee_name']); ?> (<?php echo htmlspecialchars($order['department']); ?>)</p>
            </div>
            <div>
                <label class="text-muted">Number of Guests</label>
                <p class="font-semibold" style="font-size:var(--text-2xl);color:var(--primary-600);"><?php echo $order['guest_count']; ?></p>
            </div>
        </div>
        
        <?php if ($order['special_instructions']): ?>
        <div class="mt-4" style="background:var(--warning-100);padding:15px;border-radius:var(--radius-lg);border-left:4px solid var(--warning-500);">
            <label class="font-semibold" style="color:var(--warning-500);">Special Instructions</label>
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Order Items</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                        <td><?php echo CATEGORY_LABELS[$item['category']] ?? $item['category']; ?></td>
                        <td style="font-size:var(--text-lg);font-weight:700;"><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatCurrency($item['unit_price']); ?></td>
                        <td><?php echo formatCurrency($item['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:right;font-weight:600;">Total Order Value:</td>
                        <td style="font-size:var(--text-xl);font-weight:700;color:var(--primary-600);">
                            <?php echo formatCurrency($order['total_amount']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
