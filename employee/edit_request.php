<?php
/**
 * Edit/View Request
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$requestId = (int)($_GET['id'] ?? 0);
$viewOnly = isset($_GET['view']);

if (!$requestId) {
    redirect('my_reqs.php', 'Invalid request', 'error');
}

// Get request
$request = fetchOne(
    "SELECT * FROM catering_requests WHERE id = ? AND employee_id = ?",
    [$requestId, $_SESSION['user_id']],
    "ii"
);

if (!$request) {
    redirect('my_reqs.php', 'Request not found', 'error');
}

// Can only edit pending requests
if ($request['status'] !== 'pending') {
    $viewOnly = true;
}

$pageTitle = $viewOnly ? 'View Request' : 'Edit Request';

// Get request items
$requestItems = fetchAll(
    "SELECT ri.*, mi.item_name, mi.category FROM request_items ri 
     JOIN menu_items mi ON ri.item_id = mi.id 
     WHERE ri.request_id = ?",
    [$requestId], "i"
);

// Get all menu items for editing
$menuItems = fetchAll("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, item_name");
$categories = CATEGORY_LABELS;

// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    if ($request['status'] === 'pending') {
        executeAndGetAffected(
            "UPDATE catering_requests SET status = 'cancelled' WHERE id = ?",
            [$requestId], "i"
        );
        redirect('my_reqs.php', 'Request cancelled successfully', 'success');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3><?php echo $viewOnly ? 'Request Details' : 'Edit Request'; ?></h3>
        </div>
        <div class="card-body">
            <div class="flex-between mb-6">
                <div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($request['request_number']); ?></h4>
                    <small class="text-muted">Created on <?php echo formatDate($request['created_at'], 'd M Y, h:i A'); ?></small>
                </div>
                <span class="badge badge-<?php echo $request['status']; ?>" style="font-size:14px;padding:8px 16px;">
                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                </span>
            </div>
            
            <div class="form-section">
                <h4 class="form-section-title">Event Details</h4>
                
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label>Event Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['meeting_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Venue</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['area']); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-row three-cols">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="text" value="<?php echo formatDate($request['meeting_date']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Time</label>
                        <input type="text" value="<?php echo date('h:i A', strtotime($request['meeting_time'])); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Guests</label>
                        <input type="text" value="<?php echo $request['guest_count']; ?>" readonly>
                    </div>
                </div>
                
                <?php if ($request['purpose']): ?>
                <div class="form-group">
                    <label>Purpose</label>
                    <textarea readonly><?php echo htmlspecialchars($request['purpose']); ?></textarea>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-section">
                <h4 class="form-section-title">Order Items</h4>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requestItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo CATEGORY_LABELS[$item['category']] ?? $item['category']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                <td><?php echo formatCurrency($item['subtotal']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;font-weight:600;">Total Amount:</td>
                                <td style="font-weight:700;color:var(--primary-600);">
                                    <?php echo formatCurrency($request['total_amount']); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <?php if ($request['special_instructions']): ?>
            <div class="form-section">
                <h4 class="form-section-title">Special Instructions</h4>
                <p><?php echo nl2br(htmlspecialchars($request['special_instructions'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($request['rejection_reason']): ?>
            <div class="alert alert-error">
                <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
            </div>
            <?php endif; ?>
            
            <div class="flex-between mt-6">
                <a href="my_reqs.php" class="btn btn-secondary">Back to List</a>
                <?php if ($request['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="cancel_request" class="btn btn-danger" 
                            data-confirm="Are you sure you want to cancel this request?">
                        Cancel Request
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
