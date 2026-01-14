<?php
/**
 * Request Handler - AJAX endpoints for catering requests
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_items':
        $category = sanitize($_GET['category'] ?? '');
        $where = $category ? "WHERE category = ? AND is_available = 1" : "WHERE is_available = 1";
        $params = $category ? [$category] : [];
        $types = $category ? "s" : "";
        
        $items = fetchAll("SELECT * FROM menu_items $where ORDER BY item_name", $params, $types);
        echo json_encode(['success' => true, 'items' => $items]);
        break;
        
    case 'get_request':
        $id = (int)($_GET['id'] ?? 0);
        $request = fetchOne(
            "SELECT cr.*, u.name as employee_name FROM catering_requests cr 
             JOIN users u ON cr.employee_id = u.id WHERE cr.id = ?",
            [$id], "i"
        );
        
        if ($request) {
            $items = fetchAll(
                "SELECT ri.*, mi.item_name FROM request_items ri 
                 JOIN menu_items mi ON ri.item_id = mi.id 
                 WHERE ri.request_id = ?",
                [$id], "i"
            );
            $request['items'] = $items;
            echo json_encode(['success' => true, 'request' => $request]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
        break;
        
    case 'cancel_request':
        $id = (int)($_POST['id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 0;
        
        $request = fetchOne(
            "SELECT * FROM catering_requests WHERE id = ? AND employee_id = ? AND status = 'pending'",
            [$id, $userId], "ii"
        );
        
        if ($request) {
            executeAndGetAffected(
                "UPDATE catering_requests SET status = 'cancelled' WHERE id = ?",
                [$id], "i"
            );
            echo json_encode(['success' => true, 'message' => 'Request cancelled']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel this request']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
