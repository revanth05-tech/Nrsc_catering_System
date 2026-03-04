<?php
/**
 * Manage Users & Approval - NRSC Catering System
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'User Management';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Handle manual add user
if (isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $uid = sanitize($_POST['userid']);
    $nm = sanitize($_POST['name']);
    $em = sanitize($_POST['email']);
    $rl = sanitize($_POST['role']);
    $dept = sanitize($_POST['department'] ?? '');
    $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $existing = fetchOne("SELECT id FROM users WHERE userid = ?", [$uid], "s");
    if (!$existing) {
        $res = insertAndGetId(
            "INSERT INTO users (userid, name, email, role, department, password, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
            [$uid, $nm, $em, $rl, $dept, $pw],
            "ssssss"
        );
        if ($res) {
            redirect('manage_users.php', 'User created successfully.', 'success');
        } else {
            $_SESSION['flash_message'] = "Failed to create user.";
            $_SESSION['flash_type'] = "error";
        }
    } else {
        $_SESSION['flash_message'] = "User ID already exists.";
        $_SESSION['flash_type'] = "error";
    }
}

// Handle flash messages
$success = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Fetch Pending Users (status = 'inactive')
$pendingUsers = fetchAll("SELECT * FROM users WHERE status = 'inactive' ORDER BY created_at DESC");

// Fetch Active Users (status = 'active')
$activeUsers = fetchAll("SELECT * FROM users WHERE status = 'active' AND role != 'admin' ORDER BY name ASC");

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-<?php echo $flash_type; ?>">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>


<!-- Active Users Section -->
<div class="section-container">
    <div class="flex-between mb-4">
        <h2 class="section-title">Active Users</h2>
        <button onclick="document.getElementById('add-user-modal').style.display='block'" class="btn btn-primary btn-sm">
            + Add New User
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeUsers as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['userid']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-approved">
                                    <?php echo ROLE_LABELS[$user['role']] ?? ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-completed">Active</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;backdrop-filter: blur(4px);">
    <div style="max-width:500px;margin:50px auto;background:white;border-radius:12px;box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);overflow:hidden;">
        <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid #eee;">
            <h3 style="margin:0;">Create New User</h3>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <form action="manage_users.php" method="POST">
                <input type="hidden" name="action" value="add_manual">
                
                <div class="form-group">
                    <label>User ID *</label>
                    <input type="text" name="userid" required>
                </div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="employee">Employee</option>
                            <option value="officer">Officer</option>
                            <option value="canteen">Canteen</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department">
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="flex-between" style="margin-top: 1.5rem;">
                    <button type="button" onclick="document.getElementById('add-user-modal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

