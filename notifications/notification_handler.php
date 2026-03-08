<?php
/**
 * Notification Handler Utilities
 */
require_once __DIR__ . '/../config/db.php';

/**
 * Insert a new notification for a user
 * @param int $userId Target user ID
 * @param string $role Targeted user role (for reference)
 * @param string $message Notification message
 * @param string $link Optional link to related page
 * @return int|bool Last insert ID or false
 */
function insertNotification($userId, $role, $message, $link = '') {
    return insertAndGetId(
        "INSERT INTO notifications (user_id, role, message, link) VALUES (?, ?, ?, ?)",
        [$userId, $role, $message, $link],
        "isss"
    );
}

/**
 * Mark a notification as read
 * @param int $notifId Notification ID
 * @return int Affected rows
 */
function markAsRead($notifId) {
    return executeAndGetAffected(
        "UPDATE notifications SET is_read = 1 WHERE id = ?",
        [$notifId],
        "i"
    );
}

/**
 * Mark all notifications as read for a user
 * @param int $userId User ID
 * @return int Affected rows
 */
function markAllAsRead($userId) {
    return executeAndGetAffected(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
        [$userId],
        "i"
    );
}
?>
