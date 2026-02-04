<?php
// Unified Profile Section
$userId = $_SESSION['user_id'];
$userData = fetchOne("SELECT * FROM users WHERE id = ?", [$userId], "i");

if (!$userData) {
    echo "<div class='alert alert-error'>User data not found.</div>";
    return;
}
?>

<div style="max-width: 700px; margin: 0 auto;">
    <!-- Main Profile Card -->
    <div class="card profile-card">
        <div class="card-body p-0">
            <!-- Header / Cover -->
            <div style="background: linear-gradient(135deg, var(--primary-50) 0%, var(--white) 100%); padding: 3rem 2rem; text-align: center; border-bottom: 1px solid var(--gray-700);">
                <div class="avatar-xl mb-4" style="margin: 0 auto; width: 100px; height: 100px; font-size: 2.5rem; background: white; color: var(--primary-600); border: 4px solid var(--primary-100);">
                    <?php echo strtoupper(substr($userData['name'] ?? 'U', 0, 2)); ?>
                </div>
                <h2 class="text-2xl font-bold mb-1" style="color: var(--gray-50);">
                    <?php echo htmlspecialchars($userData['name']); ?>
                </h2>
                <div class="badge badge-primary" style="font-size: 0.8rem; padding: 4px 12px; opacity: 0.8;">
                    <?php echo isset($userData['role']) ? ucfirst($userData['role']) : 'User'; ?>
                </div>
            </div>

            <!-- Details Section -->
            <div class="p-6">
                <!-- Grid Information -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    
                    <div class="info-group">
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1" style="letter-spacing: 0.05em;">User ID / Username</label>
                        <div class="text-base font-medium" style="color: var(--gray-100);">
                            <?php echo htmlspecialchars($userData['userid'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1" style="letter-spacing: 0.05em;">Email Address</label>
                        <div class="text-base font-medium" style="color: var(--gray-100);">
                            <?php echo htmlspecialchars($userData['email'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1" style="letter-spacing: 0.05em;">Department</label>
                        <div class="text-base font-medium" style="color: var(--gray-100);">
                            <?php echo htmlspecialchars($userData['department'] ?? 'General'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1" style="letter-spacing: 0.05em;">Designation</label>
                        <div class="text-base font-medium" style="color: var(--gray-100);">
                            <?php echo htmlspecialchars($userData['designation'] ?? 'Employee'); ?>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="border-top: 1px solid var(--gray-200); padding-top: 1.5rem; display: flex; gap: 1rem; justify-content: center;">
                    <a href="/catering_system/auth/change_pass.php" class="btn btn-secondary">
                        Change Password
                    </a>
                    <a href="/catering_system/auth/logout.php" class="btn btn-danger-outline" style="border: 1px solid var(--error-500); color: var(--error-500); background: transparent;">
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
