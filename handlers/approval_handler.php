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
            echo json_encode(['success' => true, 'message' => 'Request rejected']);
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
