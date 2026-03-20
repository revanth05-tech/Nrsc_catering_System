<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NRSC Catering Services - Login">
    <title>Login - NRSC Catering Services</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/catering_system/assets/css/main.css?v=2">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo" style="background: transparent; box-shadow: none; transform: none; width: auto; height: auto;">
                    <img src="/catering_system/assets/images/isroLogo.png" alt="NRSC Logo" style="width: 100px; height: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                </div>
                <h2>NRSC Catering Services</h2>
                <p>Sign in to manage catering requests</p>
            </div>
            
            <?php
            session_start();
            if (isset($_SESSION['flash_message'])):
            ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'error'; ?>">
                <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="/catering_system/auth/login.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="employee_code">Employee Code</label>
                    <input type="text" id="employee_code" name="employee_code" placeholder="Enter NRSC Employee Code (e.g. NR01234)" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="/catering_system/auth/signup.php" style="color: var(--primary-600); font-weight: 600; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Register here</a></p>
                <p style="margin-top: 15px;">National Remote Sensing Centre &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>