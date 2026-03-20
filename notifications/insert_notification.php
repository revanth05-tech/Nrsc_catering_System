<?php
/**
 * AJAX / POST Wrapper for inserting notifications
 */
require_once __DIR__ . '/notification_handler.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_code'])) {
    $userId = (int)($_POST['target_user_id'] ?? 0);
    $role = $_POST['target_role'] ?? '';
    $message = $_POST['message'] ?? '';
    $link = $_POST['link'] ?? '';

    if ($userId && $message) {
        $result = insertNotification($userId, $role, $message, $link);
        echo json_encode(['success' => (bool)$result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    }
}
?>
