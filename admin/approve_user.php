<?php
/**
 * Approve User Handler
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userCode = $_POST['user_code'] ?? ($_GET['code'] ?? null);
$action = $_POST['action'] ?? ($_GET['action'] ?? 'approve');
$isAjax = isset($_POST['ajax']) || isset($_GET['ajax']);

if ($userCode) {
    if ($action === 'reject') {
        $status = 'rejected';
        $logAction = 'user_rejection';
        $logDetails = "Rejected user code: $userCode";
        $successMsg = "User rejected successfully.";
        $failMsg = "Failed to reject user.";
    } else {
        $status = 'active';
        $logAction = 'user_approval';
        $logDetails = "Approved user code: $userCode";
        $successMsg = "User approved successfully.";
        $failMsg = "Failed to approve user.";
    }

    $result = executeAndGetAffected(
        "UPDATE users SET status = ? WHERE userid = ?",
        [$status, $userCode],
        "ss"
    );

    if ($result !== false) {
        // Log activity (Internal ID for performer session is still used for database integrity unless told otherwise, but logic refers to code)
        insertAndGetId(
            "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$_SESSION['user_id'], $logAction, $logDetails, $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            "isss"
        );
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $successMsg]);
            exit();
        }
        
        $_SESSION['flash_message'] = $successMsg;
        $_SESSION['flash_type'] = "success";
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $failMsg]);
            exit();
        }
        $_SESSION['flash_message'] = $failMsg;
        $_SESSION['flash_type'] = "error";
    }
} else {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid User Code.']);
        exit();
    }
}

// Redirect back to referring page or manage_users
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? "manage_users.php";
header("Location: " . $redirectUrl);
exit();
