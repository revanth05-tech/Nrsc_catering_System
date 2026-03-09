<?php
/**
 * View Request Details
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$requestId = (int)($_GET['id'] ?? 0);

if (!$requestId) {
    die('Invalid request');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$whereClause = "cr.id = ?";
$params = [$requestId];
$types = "i";

// Security check logic per role
if ($userRole === 'employee') {
    $whereClause .= " AND cr.employee_id = ?";
    $params[] = $userId;
    $types .= "i";
} elseif ($userRole === 'officer') {
    $whereClause .= " AND cr.approving_officer_id = ?";
    $params[] = $userId;
    $types .= "i";
} elseif ($userRole === 'canteen') {
    $whereClause .= " AND cr.status IN ('approved', 'in_progress', 'completed')";
}

// Admin has full access, no additional where clauses needed.

$request = fetchOne(
    "SELECT cr.*, u.name as employee_name, u.department, u.email 
     FROM catering_requests cr 
     JOIN users u ON cr.employee_id = u.id 
     WHERE $whereClause",
    $params, $types
);

if (!$request) {
    redirect('../index.php', 'Request not found or access denied', 'error');
}

$pageTitle = 'View Request Details';

// Get request items
$requestItems = fetchAll(
    "SELECT ri.*, mi.item_name, mi.category FROM request_items ri 
     JOIN menu_items mi ON ri.item_id = mi.id 
     WHERE ri.request_id = ?",
    [$requestId], "i"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3>Request Details: <?php echo htmlspecialchars($request['request_number']); ?></h3>
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
                        <input type="text" value="<?php echo htmlspecialchars($request['department'] ?? 'N/A'); ?>" readonly>
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
                        <input type="text" value="<?php echo !empty($request['meeting_time']) ? date('h:i A', strtotime($request['meeting_time'])) : 'N/A'; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Guests</label>
                        <input type="text" value="<?php echo $request['guest_count']; ?>" readonly>
                    </div>
                </div>
                <?php if (!empty($request['purpose'])): ?>
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
                            <?php if (empty($requestItems)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No items found in this request.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requestItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo CATEGORY_LABELS[$item['category']] ?? $item['category']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td><?php echo formatCurrency($item['subtotal'] ?? ($item['quantity'] * $item['unit_price'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
            
            <?php if (!empty($request['special_instructions'])): ?>
            <div class="form-section">
                <h4 class="form-section-title">Special Instructions</h4>
                <p><?php echo nl2br(htmlspecialchars($request['special_instructions'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
            <div class="alert alert-error mt-4">
                <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
            </div>
            <?php endif; ?>
            
            <div class="flex-between mt-6">
                <!-- Javascript history.back to easily return to whoever requested it -->
                <button onclick="history.back()" class="btn btn-secondary">Back</button>
                <a href="../reports/export_pdf.php?request_id=<?php echo $requestId; ?>" class="btn btn-primary" target="_blank">
                    <i class="fa-solid fa-download me-2"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
