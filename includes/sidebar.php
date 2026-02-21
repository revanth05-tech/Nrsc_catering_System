<?php
$role = $_SESSION['role'] ?? 'employee';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="convo-sidebar">
    <div class="brand" style="text-align: center;">
        <img src="/catering_system/assets/images/isroLogo.png" alt="NRSC" style="width: 60px; height: auto; margin-bottom: 12px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
        <div class="logo-box">NRSC CATERING</div>
    </div>
    
    <div class="user-profile-large">
        <div class="avatar-xl">
             <?php echo strtoupper(substr($_SESSION['userid'] ?? 'U', 0, 2)); ?>
        </div>
        <h3 class="user-name"><?php echo htmlspecialchars($_SESSION['userid'] ?? 'User'); ?></h3>
        <p class="user-role"><?php echo ROLE_LABELS[$role] ?? 'User'; ?></p>
        
        <a href="/catering_system/profile.php" class="btn btn-sm btn-secondary mt-4 w-full" style="justify-content: center;">
            My Profile
        </a>
    </div>

    <div class="sidebar-actions">
        <?php if ($role === 'employee'): ?>
            <a href="/catering_system/employee/dashboard.php" class="btn btn-ghost <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="/catering_system/employee/new_request.php" class="btn btn-ghost <?php echo $currentPage === 'new_request.php' ? 'active' : ''; ?>">
                New Request
            </a>
            <a href="/catering_system/employee/my_reqs.php" class="btn btn-ghost <?php echo $currentPage === 'my_reqs.php' ? 'active' : ''; ?>">
                My Request History
            </a>

        
        <?php elseif ($role === 'officer'): ?>
            <a href="/catering_system/officer/dashboard.php" class="btn btn-ghost <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="/catering_system/officer/approved_orders.php" class="btn btn-ghost <?php echo $currentPage === 'approved_orders.php' ? 'active' : ''; ?>">
                Approved Orders
            </a>
            <a href="/catering_system/officer/completed_orders.php" class="btn btn-ghost <?php echo $currentPage === 'completed_orders.php' ? 'active' : ''; ?>">
                Completed Orders
            </a>

            
        <?php elseif ($role === 'canteen'): ?>
            <a href="/catering_system/canteen/dashboard.php" class="btn btn-ghost <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="/catering_system/canteen/pending_orders.php" class="btn btn-ghost <?php echo $currentPage === 'pending_orders.php' ? 'active' : ''; ?>">
                Pending Orders
            </a>

            
        <?php elseif ($role === 'admin'): ?>
            <a href="/catering_system/admin/dashboard.php" class="btn btn-ghost <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="/catering_system/admin/manage_users.php" class="btn btn-ghost <?php echo $currentPage === 'manage_users.php' ? 'active' : ''; ?>">
                Manage Users
            </a>
            <a href="/catering_system/admin/manage_items.php" class="btn btn-ghost <?php echo $currentPage === 'manage_items.php' ? 'active' : ''; ?>">
                Menu Items
            </a>
            <a href="/catering_system/admin/reports.php" class="btn btn-ghost <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                Reports
            </a>

        <?php endif; ?>
        
        <div style="margin: 10px 0; border-top: 1px solid var(--gray-700);"></div>
        
        <a href="/catering_system/auth/change_pass.php" class="btn btn-ghost">
            Change Password
        </a>
        <a href="/catering_system/auth/logout.php" class="btn btn-ghost text-danger">
            Logout
        </a>
    </div>
</aside>
