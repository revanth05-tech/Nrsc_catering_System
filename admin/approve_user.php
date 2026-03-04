<?php
/**
 * Approve User Handler
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int)($_GET['id'] ?? 0);

if ($userId > 0) {
    $result = executeAndGetAffected(
        "UPDATE users SET status = 'active' WHERE id = ?",
        [$userId],
        "i"
    );

    if ($result) {
        // Log activity
        insertAndGetId(
            "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$_SESSION['user_id'], 'user_approval', "Approved user ID: $userId", $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            "isss"
        );
        
        $_SESSION['flash_message'] = "User approved successfully.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Failed to approve user.";
        $_SESSION['flash_type'] = "error";
    }
}

header("Location: manage_users.php");
exit();
