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
    <link rel="stylesheet" href="/catering_system/assets/css/main.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
                    </svg>
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
                    <label for="userid">User ID</label>
                    <input type="text" id="userid" name="userid" placeholder="Enter your User ID" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Login As</label>
                    <select id="role" name="role" required>
                        <option value="">-- Select Role --</option>
                        <option value="employee">Employee</option>
                        <option value="officer">Approving Officer</option>
                        <option value="canteen">Canteen Staff</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
            </form>
            
            <div class="login-footer">
                <p>National Remote Sensing Centre &copy; <?php echo date('Y'); ?></p>
                <p style="margin-top:10px;font-size:11px;color:#999;">
                    Demo: admin/password, officer1/password, emp001/password
                </p>
            </div>
        </div>
    </div>
</body>
</html>