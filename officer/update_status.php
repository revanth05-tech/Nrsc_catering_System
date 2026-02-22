<?php
/**
 * Review and Update Request Status
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('officer');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$requestId = (int)($_GET['id'] ?? 0);

if (!$requestId) {
    redirect('dashboard.php', 'Invalid request', 'error');
}

// Get request with employee info
$request = fetchOne(
    "SELECT cr.*, u.name as employee_name, u.department, u.email 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE cr.id = ?",
    [$requestId], "i"
);

if (!$request) {
    redirect('dashboard.php', 'Request not found', 'error');
}

$pageTitle = 'Review Request';

// Get request items
$requestItems = fetchAll(
    "SELECT ri.*, mi.item_name, mi.category FROM request_items ri 
     JOIN menu_items mi ON ri.item_id = mi.id 
     WHERE ri.request_id = ?",
    [$requestId], "i"
);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reason = sanitize($_POST['rejection_reason'] ?? '');
    
    if ($action === 'approve') {
        executeAndGetAffected(
            "UPDATE catering_requests SET status = 'approved', approving_officer_id = ?, approved_at = NOW() WHERE id = ?",
            [$_SESSION['user_id'], $requestId], "ii"
        );
        redirect('dashboard.php', 'Request approved successfully!', 'success');
    } elseif ($action === 'reject' && $reason) {
        executeAndGetAffected(
            "UPDATE catering_requests SET status = 'rejected', approving_officer_id = ?, rejection_reason = ? WHERE id = ?",
            [$_SESSION['user_id'], $reason, $requestId], "isi"
        );
        redirect('dashboard.php', 'Request rejected.', 'success');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3>Review Request: <?php echo htmlspecialchars($request['request_number']); ?></h3>
        </div>
        <div class="card-body">
            <div class="flex-between mb-6">
                <div>
                    <span class="badge badge-<?php echo $request['status']; ?>" style="font-size:14px;padding:8px 16px;">
                        <?php echo ucfirst($request['status']); ?>
                    </span>
                </div>
                <small class="text-muted">Submitted: <?php echo formatDate($request['created_at'], 'd M Y, h:i A'); ?></small>
            </div>
            
            <div class="form-section">
                <h4 class="form-section-title">Employee Information</h4>
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['employee_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['department']); ?>" readonly>
                    </div>
                </div>
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
                <h4 class="form-section-title">Order Details</h4>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Price</th>
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
                                <td colspan="4" style="text-align:right;font-weight:600;">Total:</td>
                                <td style="font-weight:700;font-size:1.2em;color:var(--primary-600);">
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
            
            <?php if ($request['status'] === 'pending'): ?>
            <div class="form-section" style="background:var(--gray-50);padding:20px;border-radius:var(--radius-lg);">
                <h4 class="form-section-title">Take Action</h4>
                
                <form method="POST" id="approval-form">
                    <div class="form-group" id="rejection-reason-group" style="display:none;">
                        <label for="rejection_reason">Reason for Rejection *</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" 
                                  placeholder="Please provide a reason for rejecting this request..."></textarea>
                    </div>
                    
                    <div class="d-flex gap-4">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Approve Request
                        </button>
                        <button type="button" id="show-reject" class="btn btn-danger btn-lg">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Reject Request
                        </button>
                        <button type="submit" name="action" value="reject" id="confirm-reject" class="btn btn-danger btn-lg" style="display:none;">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
            document.getElementById('show-reject').addEventListener('click', function() {
                document.getElementById('rejection-reason-group').style.display = 'block';
                document.getElementById('confirm-reject').style.display = 'inline-flex';
                this.style.display = 'none';
                document.getElementById('rejection_reason').required = true;
            });
            </script>
            <?php endif; ?>
            
            <div class="mt-6">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
