<?php
/**
 * Approval Handler - AJAX endpoints for officer approvals
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication and role
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'officer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'approve':
        $id = (int)($_POST['id'] ?? 0);
        $request = fetchOne("SELECT * FROM catering_requests WHERE id = ? AND status = 'pending'", [$id], "i");
        
        if ($request) {
            executeAndGetAffected(
                "UPDATE catering_requests SET status = 'approved', approving_officer_id = ?, approved_at = NOW() WHERE id = ?",
                [$_SESSION['user_id'], $id], "ii"
            );

            // Notify Employee
            insertAndGetId(
                "INSERT INTO notifications (user_id, role, message, link) VALUES (?, 'employee', ?, ?)",
                [$request['employee_id'], "Your request #{$request['request_number']} has been approved.", "/catering_system/employee/my_reqs.php"],
                "iss"
            );

            // Notify Canteen Staff
            $canteenStaff = fetchAll("SELECT id FROM users WHERE role = 'canteen'");
            $specialNotes = !empty($request['special_instructions']) ? " Notes: " . $request['special_instructions'] : "";
            foreach ($canteenStaff as $staff) {
                insertAndGetId(
                    "INSERT INTO notifications (user_id, role, message, link) VALUES (?, 'canteen', ?, ?)",
                    [$staff['id'], "New approved request #{$request['request_number']} for preparation.$specialNotes", "/catering_system/canteen/dashboard.php"],
                    "iss"
                );
            }
            echo json_encode(['success' => true, 'message' => 'Request approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        }
        break;
        
    case 'reject':
        $id = (int)($_POST['id'] ?? 0);
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
            exit();
        }
        
        $request = fetchOne("SELECT * FROM catering_requests WHERE id = ? AND status = 'pending'", [$id], "i");
        
        if ($request) {
            executeAndGetAffected(
                "UPDATE catering_requests SET status = 'rejected', approving_officer_id = ?, rejection_reason = ? WHERE id = ?",
                [$_SESSION['user_id'], $reason, $id], "isi"
            );

            // Notify Employee
            insertAndGetId(
                "INSERT INTO notifications (user_id, role, message, link) VALUES (?, 'employee', ?, ?)",
                [$request['employee_id'], "Your request #{$request['request_number']} has been rejected. Reason: $reason", "/catering_system/employee/my_reqs.php"],
                "iss"
            );
            echo json_encode(['success' => true, 'message' => 'Request rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        }
        break;
        
    case 'return':
        $id = (int)($_POST['id'] ?? 0);
        $reason = sanitize($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Return reason is required']);
            exit();
        }
        
        $request = fetchOne("SELECT * FROM catering_requests WHERE id = ? AND status = 'pending'", [$id], "i");
        
        if ($request) {
            executeAndGetAffected(
                "UPDATE catering_requests SET status = 'returned', return_reason = ? WHERE id = ?",
                [$reason, $id], "si"
            );

            // Notify Employee
            insertAndGetId(
                "INSERT INTO notifications (user_id, role, message, link) VALUES (?, 'employee', ?, ?)",
                [$request['employee_id'], "Your request #{$request['request_number']} has been returned for clarification. Reason: $reason", "/catering_system/employee/edit_request.php?id=$id"],
                "iss"
            );
            echo json_encode(['success' => true, 'message' => 'Request returned for clarification']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        }
        break;
        
    case 'get_pending_count':
        $count = fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'pending'")['count'] ?? 0;
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
