<?php
// sections/employee/profile.php - Profile Fragment

$user = fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']], "i");
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header text-center">
        <div class="avatar-xl mb-4" style="margin: 0 auto;">
            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
        </div>
        <h2 class="text-2xl text-white mb-1"><?php echo htmlspecialchars($user['username']); ?></h2>
        <p class="text-primary uppercase tracking-widest text-sm font-bold"><?php echo $_SESSION['role']; ?></p>
    </div>
    
    <div class="card-body">
        <div class="form-group mb-6">
            <label class="text-xs uppercase text-muted">Email Address</label>
            <div class="text-lg text-white font-mono"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
        
        <div class="form-group mb-6">
            <label class="text-xs uppercase text-muted">Role</label>
            <div class="text-lg text-white font-mono"><?php echo ucfirst($user['role']); ?></div>
        </div>

        <div class="border-t border-gray-700 pt-6 mt-6">
            <h4 class="text-sm font-bold text-white mb-4">Account Actions</h4>
            
            <div class="d-flex flex-column gap-3">
                <a href="../auth/change_pass.php" class="btn btn-secondary w-full justify-content-center">
                    Change Password
                </a>
                
                <a href="../auth/logout.php" class="btn btn-danger w-full justify-content-center">
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</div>
