<?php
/**
 * Saved Requests - View and manage saved (new) requests
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$pageTitle = 'Saved Requests';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'] ?? 0;
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);

    if ($requestId > 0) {
        if ($action === 'delete') {
            // Re-verify it belongs to user and is new
            $check = fetchOne("SELECT id FROM catering_requests WHERE id=? AND employee_id=? AND status='new'", [$requestId, $userId], "ii");
            if ($check) {
                executeQuery("DELETE FROM catering_requests WHERE id=? AND employee_id=?", [$requestId, $userId], "ii");
                $success = "Saved request deleted successfully.";
            } else {
                $error = "Unauthorized or invalid request.";
            }
        } elseif ($action === 'submit_saved') {
            $check = fetchOne("SELECT id, request_number, requesting_person, approving_officer_code FROM catering_requests WHERE id=? AND employee_id=? AND status='new'", [$requestId, $userId], "ii");
            if ($check) {
                executeQuery("UPDATE catering_requests SET status='pending' WHERE id=? AND employee_id=?", [$requestId, $userId], "ii");
                
                // Notify officer
                $targetOfficerCode = $check['approving_officer_code'];
                if ($targetOfficerCode) {
                    $reqNumber = $check['request_number'];
                    $reqPerson = $check['requesting_person'];
                    insertAndGetId(
                        "INSERT INTO notifications (user_code, role, message, link) VALUES (?, 'officer', ?, ?)",
                        [$targetOfficerCode, "New catering request #$reqNumber submitted by $reqPerson.", "/catering_system/officer/dashboard.php"],
                        "sss"
                    );
                }
                
                redirect('my_reqs.php', "Request #{$check['request_number']} submitted successfully!", 'success');
            } else {
                $error = "Unauthorized or invalid request.";
            }
        }
    }
}

// Fetch saved requests
$requests = fetchAll(
    "SELECT * FROM catering_requests WHERE employee_id = ? AND status = 'new' ORDER BY created_at DESC",
    [$userId], "i"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between mb-4">
    <h2>Saved Requests</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<style>
/* Modern Action Buttons Styling */
.action-buttons {
    display: flex !important;
    gap: 10px !important;
    align-items: center !important;
    justify-content: flex-start !important;
}

.action-btn {
    font-size: 13px !important;
    font-weight: 500 !important;
    padding: 6px 16px !important;
    border-radius: 20px !important;
    border: none !important;
    cursor: pointer !important;
    transition: all 0.25s ease !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.2 !important;
    outline: none !important;
}

.action-btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important;
    color: white !important;
}

.btn-edit {
    background: #e8f0ff !important;
    color: #2563eb !important;
}

.btn-edit:hover {
    background: #2563eb !important;
}

.btn-submit {
    background: #e8fff1 !important;
    color: #16a34a !important;
}

.btn-submit:hover {
    background: #16a34a !important;
}

.btn-delete {
    background: #ffeaea !important;
    color: #dc2626 !important;
}

.btn-delete:hover {
    background: #dc2626 !important;
}

/* Ensure forms don't break the layout */
.action-buttons form {
    margin: 0 !important;
    padding: 0 !important;
    display: contents !important;
}

/* Fix for button height */
button.action-btn {
    height: auto !important;
    min-height: unset !important;
}
</style>

<div class="card">
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div class="text-center p-6">
                <p class="text-muted mb-4">No saved requests found.</p>
                <a href="new_request.php" class="btn btn-primary">Create New Request</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Request Number</th>
                            <th>Meeting Name</th>
                            <th>Meeting Date</th>
                            <th>Area</th>
                            <th>Service Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['meeting_name']); ?></td>
                            <td><?php echo formatDate($req['meeting_date']); ?></td>
                            <td><?php echo htmlspecialchars($req['area']); ?></td>
                            <td><?php echo formatDate($req['service_date']); ?></td>
                            <td><?php echo formatCurrency($req['total_amount']); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    New
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_request.php?id=<?php echo $req['id']; ?>" class="action-btn btn-edit">Edit</a>
                                    
                                    <form method="POST" onsubmit="return confirm('Submit this request for approval?');">
                                        <input type="hidden" name="action" value="submit_saved">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" class="action-btn btn-submit">Submit</button>
                                    </form>
                                    
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this saved request?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" class="action-btn btn-delete">Delete</button>
                                    </form>
                                </div>
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
