<?php
// layout/app.php - Master Layout for ConvoManage Style

// Ensure Variables
$pageTitle = $pageTitle ?? 'Dashboard';
$role = $_SESSION['role'] ?? 'employee';
$section = $section ?? 'home';
$userInitials = isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U';
$userName = $_SESSION['user_name'] ?? 'User';

// Define Navigation Pills based on Role
$navItems = [];

switch($role) {
    case 'employee':
        $navItems = [
            'home' => 'Dashboard',
            'form' => 'New Request',
            'history' => 'My History',
            'profile' => 'Profile'
        ];
        break;
    case 'officer':
        $navItems = [
            'home' => 'Dashboard',
            'approvals' => 'Pending Approvals',
            'history' => 'History',
            'profile' => 'Profile'
        ];
        break;
    case 'canteen':
        $navItems = [
            'home' => 'Dashboard',
            'orders' => 'Live Orders',
            'history' => 'Order History',
            'profile' => 'Profile'
        ];
        break;
    case 'admin':
        $navItems = [
            'home' => 'Dashboard',
            'users' => 'Users',
            'menu' => 'Menu Items',
            'reports' => 'Reports',
            'profile' => 'Profile'
        ];
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NRSC Catering</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/catering_system/assets/css/main.css?v=2">
    <link rel="stylesheet" href="/catering_system/assets/css/convo.css?v=2">
</head>
<body class="convo-layout">
    
    <!-- LEFT SIDEBAR: PROFILE & IDENTITY -->
    <aside class="convo-sidebar">
        <div class="brand" style="text-align: center;">
            <img src="/catering_system/assets/images/isroLogo.png" alt="NRSC" style="width: 70px; height: auto; margin-bottom: 12px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
            <div class="logo-box">NRSC CATERING</div>
        </div>
        
        <div class="user-profile-large">
            <div class="avatar-xl">
                <?php echo $userInitials; ?>
            </div>
            <h3 class="user-name"><?php echo htmlspecialchars($userName); ?></h3>
            <p class="user-role"><?php echo ucfirst($role); ?></p>
        </div>

        <div class="sidebar-actions">
            <a href="?section=profile" class="btn btn-ghost <?php echo $section === 'profile' ? 'active' : ''; ?>">
                My Profile
            </a>
            <a href="/catering_system/auth/change_pass.php" class="btn btn-ghost">
                Change Password
            </a>
            <div style="flex-grow: 1;"></div> <!-- Spacer -->
            <a href="/catering_system/auth/logout.php" class="btn btn-ghost text-danger">
                Logout
            </a>
        </div>
    </aside>

    <!-- RIGHT CONTENT AREA -->
    <main class="convo-main">
        
        <!-- TOP NAV: PILLS -->
        <header class="convo-header">
            <h1 style="font-size: 1.5rem; margin: 0; color: var(--gray-50);"><?php echo $pageTitle; ?></h1>
            
            <nav class="nav-pills-container">
                <?php foreach($navItems as $key => $label): ?>
                    <a href="?section=<?php echo $key; ?>" 
                       class="nav-pill <?php echo $section === $key ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </header>

        <!-- DYNAMIC CONTENT SECTION -->
        <div class="convo-content">
            <?php 
                // Display Flash Messages if any
                if (function_exists('displayFlashMessage')) {
                    echo displayFlashMessage();
                }

                // Load the specific section fragment
                $safeSection = preg_replace('/[^a-z0-9_]/', '', $section);
                $file = __DIR__ . "/../sections/{$role}/{$safeSection}.php";
                
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "
                    <div class='card'>
                        <div class='card-body text-center'>
                            <h3 class='text-muted'>Section under construction</h3>
                            <p>The requested section '{$safeSection}' is not yet available in the new design.</p>
                            
                            <!-- Fallback Check -->
                            <p class='text-sm mt-4'>Debug: Looking for file at {$file}</p>
                        </div>
                    </div>";
                }
            ?>
        </div>
    </main>

    <script src="/catering_system/assets/js/main.js"></script>
</body>
</html>
