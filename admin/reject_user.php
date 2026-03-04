<?php
/**
 * Reject/Delete User Handler
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int)($_GET['id'] ?? 0);

if ($userId > 0) {
    // We'll delete for rejection to keep the inactive list clean
    $result = executeAndGetAffected(
        "DELETE FROM users WHERE id = ? AND status = 'inactive'",
        [$userId],
        "i"
    );

    if ($result) {
        // Log activity
        insertAndGetId(
            "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$_SESSION['user_id'], 'user_rejection', "Rejected/Deleted user ID: $userId", $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            "isss"
        );
        
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'User rejected successfully.']);
            exit();
        }

        $_SESSION['flash_message'] = "User rejected and removed successfully.";
        $_SESSION['flash_type'] = "success";
    } else {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to reject user.']);
            exit();
        }
        $_SESSION['flash_message'] = "Failed to reject user.";
        $_SESSION['flash_type'] = "error";
    }
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid User ID.']);
    exit();
}

header("Location: manage_users.php");
exit();
