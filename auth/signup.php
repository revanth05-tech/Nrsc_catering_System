<?php
/**
 * User Registration - NRSC Catering System
 */
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $name = sanitize($_POST['name'] ?? '');
    $userid = sanitize($_POST['userid'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $role = sanitize($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($name) || empty($userid) || empty($email) || empty($role) || empty($password)) {
        $errors[] = "Please fill in all required fields.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Check if employee code already exists
    $existingUser = fetchOne("SELECT id FROM users WHERE userid = ?", [$userid], "s");
    if ($existingUser) {
        $errors[] = "Employee Code already exists. Please check again.";
    }

    // Check if email already exists
    $existingEmail = fetchOne("SELECT id FROM users WHERE email = ?", [$email], "s");
    if ($existingEmail) {
        $errors[] = "Email address is already registered.";
    }

    if (empty($errors)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database
        $sql = "INSERT INTO users (name, userid, email, phone, department, role, password, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'inactive')";
        
        $params = [$name, $userid, $email, $phone, $department, $role, $hashedPassword];
        $types = "sssssss";
        
        $userId = insertAndGetId($sql, $params, $types);
        
        if ($userId) {
            // Log activity
            insertAndGetId(
                "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                [$userId, 'registration', 'New user registered (pending approval)', $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
                "isss"
            );

            // Notify Admins
            $admins = fetchAll("SELECT userid FROM users WHERE role = 'admin'");
            foreach ($admins as $admin) {
                insertAndGetId(
                    "INSERT INTO notifications (user_code, role, message, link) VALUES (?, 'admin', ?, ?)",
                    [$admin['userid'], "New user registration: $name requires approval.", "/catering_system/notifications/notifications.php"],
                    "sss"
                );
            }
            
            $_SESSION['flash_message'] = "Registration submitted successfully. Your account will be activated after admin approval.";
            $_SESSION['flash_type'] = "success";
            header("Location: ../index.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NRSC Catering Services - Sign Up">
    <title>Sign Up - NRSC Catering Services</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/catering_system/assets/css/main.css?v=2">
    <style>
        .signup-container {
            max-width: 550px !important;
        }
        .register-link {
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .register-link:hover {
            text-decoration: underline;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container signup-container">
            <div class="login-header">
                <div class="login-logo" style="background: transparent; box-shadow: none; transform: none; width: auto; height: auto;">
                    <img src="/catering_system/assets/images/isroLogo.png" alt="NRSC Logo" style="width: 80px; height: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                </div>
                <h2>Create Account</h2>
                <p>Register for NRSC Catering Services</p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="signup.php">
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="userid">NRSC Employee Code *</label>
                        <input type="text" id="userid" name="userid" placeholder="e.g. NR01234" value="<?php echo htmlspecialchars($_POST['userid'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="Enter email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" placeholder="Enter department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="role">Account Role *</label>
                        <select id="role" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="employee" <?php echo ($_POST['role'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="officer" <?php echo ($_POST['role'] ?? '') === 'officer' ? 'selected' : ''; ?>>Approving Officer</option>
                            <option value="canteen" <?php echo ($_POST['role'] ?? '') === 'canteen' ? 'selected' : ''; ?>>Canteen Staff</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">Register Now</button>
            </form>
            
            <div class="login-footer">
                <p>Already have an account? <a href="/catering_system/index.php" class="register-link" style="color: var(--primary-600);">Login here</a></p>
                <p style="margin-top: 15px;">National Remote Sensing Centre &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
