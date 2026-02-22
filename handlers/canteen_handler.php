<?php
/**
 * Canteen Handler - AJAX endpoints for canteen operations
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication and role
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'canteen') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'start_order':
        $id = (int)($_POST['id'] ?? 0);
        $request = fetchOne("SELECT * FROM catering_requests WHERE id = ? AND status = 'approved'", [$id], "i");
        
        if ($request) {
            executeAndGetAffected(
                "UPDATE catering_requests SET status = 'in_progress' WHERE id = ?",
                [$id], "i"
            );
            echo json_encode(['success' => true, 'message' => 'Order started']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found or not approved']);
        }
        break;
        
    case 'complete_order':
        $id = (int)($_POST['id'] ?? 0);
        $request = fetchOne("SELECT * FROM catering_requests WHERE id = ? AND status = 'in_progress'", [$id], "i");
        
        if ($request) {
            executeAndGetAffected(
                "UPDATE catering_requests SET status = 'completed' WHERE id = ?",
                [$id], "i"
            );
            echo json_encode(['success' => true, 'message' => 'Order completed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found or not in progress']);
        }
        break;
        
    case 'get_orders_count':
        $approved = fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'approved'")['count'] ?? 0;
        $inProgress = fetchOne("SELECT COUNT(*) as count FROM catering_requests WHERE status = 'in_progress'")['count'] ?? 0;
        echo json_encode(['success' => true, 'approved' => $approved, 'in_progress' => $inProgress]);
        break;
        
    case 'get_today_orders':
        $orders = fetchAll(
            "SELECT cr.*, u.name as employee_name FROM catering_requests cr 
             JOIN users u ON cr.employee_id = u.id 
             WHERE cr.meeting_date = CURDATE() AND cr.status IN ('approved', 'in_progress') 
             ORDER BY cr.meeting_time ASC"
        );
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
