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
            $conn = getConnection();
            
            // Re-verify it belongs to user and is new
            $check = fetchOne("SELECT id FROM catering_requests WHERE id=? AND employee_id=? AND status='new'", [$requestId, $userId], "ii");
            if ($check) {
                executeQuery("DELETE FROM catering_requests WHERE id=? AND employee_id=?", [$requestId, $userId], "ii");
                $success = "Saved request deleted successfully.";
            } else {
                $error = "Unauthorized or invalid request.";
            }
        } elseif ($action === 'submit_saved') {
            $check = fetchOne("SELECT id, request_number, requesting_person, approving_officer_id FROM catering_requests WHERE id=? AND employee_id=? AND status='new'", [$requestId, $userId], "ii");
            if ($check) {
                executeQuery("UPDATE catering_requests SET status='pending' WHERE id=? AND employee_id=?", [$requestId, $userId], "ii");
                
                // Notify officer
                $targetOfficerId = $check['approving_officer_id'];
                if ($targetOfficerId) {
                    $reqNumber = $check['request_number'];
                    $reqPerson = $check['requesting_person'];
                    insertAndGetId(
                        "INSERT INTO notifications (user_id, role, message, link) VALUES (?, 'officer', ?, ?)",
                        [$targetOfficerId, "New catering request #$reqNumber submitted by $reqPerson.", "/catering_system/officer/dashboard.php"],
                        "iss"
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
                                <div class="btn-group" role="group">
                                    <a href="edit_request.php?id=<?php echo $req['id']; ?>" class="btn btn-primary btn-sm mx-1 rounded">Edit</a>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Submit this request for approval?');">
                                        <input type="hidden" name="action" value="submit_saved">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm mx-1 rounded">Submit</button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this saved request?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm mx-1 rounded">Delete</button>
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
