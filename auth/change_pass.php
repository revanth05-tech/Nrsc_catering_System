<?php
/**
 * Change Password Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Change Password';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    
    // Get current user
    $user = fetchOne("SELECT * FROM users WHERE userid = ?", [$_SESSION['userid']], "s");
    
    if (!$user) {
        $error = 'User not found.';
    } elseif (!password_verify($currentPass, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'New passwords do not match.';
    } else {
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
        $result = executeAndGetAffected(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashedPass, $user['id']],
            "si"
        );
        
        if ($result) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3>Change Password</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
