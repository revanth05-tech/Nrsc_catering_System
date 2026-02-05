<?php
/**
 * Manage Users
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// Handle add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $userid = sanitize($_POST['userid'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $role = sanitize($_POST['role'] ?? 'employee');
        $password = $_POST['password'] ?? '';
        
        if (empty($userid) || empty($name) || empty($password)) {
            $error = 'Username, Full Name, and Password are required.';
        } else {
            $existing = fetchOne("SELECT id FROM users WHERE username = ?", [$userid], "s");
            if ($existing) {
                $error = 'Username already exists.';
            } else {
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                $result = insertAndGetId(
                    "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)",
                    [$userid, $hashedPass, $name, $role],
                    "ssss"
                );
                if ($result) {
                    $success = 'User created successfully!';
                } else {
                    $error = 'Failed to create user.';
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'inactive';
        executeAndGetAffected("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId], "si");
        $success = 'User status updated.';
    } elseif ($action === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPass = password_hash('password', PASSWORD_DEFAULT);
        executeAndGetAffected("UPDATE users SET password = ? WHERE id = ?", [$newPass, $userId], "si");
        $success = 'Password reset to "password".';
    }
}

// Get users
$users = fetchAll("SELECT * FROM users ORDER BY role, full_name");

include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="flex-between mb-6">
    <div></div>
    <button onclick="document.getElementById('add-user-modal').style.display='block'" class="btn btn-primary">
        Add New User
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'approved' : 'progress'; ?>">
                                <?php echo ROLE_LABELS[$user['role']] ?? $user['role']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-warning" data-confirm="Reset password to 'password'?">Reset Pass</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;padding:20px;">
    <div style="max-width:500px;margin:50px auto;background:white;border-radius:var(--radius-xl);overflow:hidden;">
        <div class="card-header flex-between">
            <h3 style="margin:0;">Add New User</h3>
            <button onclick="document.getElementById('add-user-modal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="userid" required placeholder="e.g., emp002">
                </div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="employee">Employee</option>
                        <option value="officer">Approving Officer</option>
                        <option value="canteen">Canteen Staff</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="flex-between">
                    <button type="button" onclick="document.getElementById('add-user-modal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
