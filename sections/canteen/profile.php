<?php
// ===============================
// SAFE PROFILE SECTION (CANTEEN)
// ===============================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

// Validate session
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-error'>Session expired. Please login again.</div>";
    return;
}

$userId = $_SESSION['user_id'];

$userData = fetchOne(
    "SELECT id, userid, full_name, email, department, role 
     FROM users 
     WHERE id = ?",
    [$userId],
    "i"
);

if (!$userData) {
    echo "<div class='alert alert-error'>User not found.</div>";
    return;
}

/* -----------------------------
   SAFE VARIABLE INITIALIZATION
--------------------------------*/

$fullName   = $userData['full_name'] ?? 'User';
$email      = $userData['email'] ?? 'N/A';
$department = $userData['department'] ?? 'General';
$userid     = $userData['userid'] ?? 'N/A';
$role       = ucfirst($userData['role'] ?? 'Canteen');

$initials = strtoupper(substr($fullName, 0, 2));
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div class="card profile-card">
        <div class="card-body p-0">

            <!-- Header -->
            <div style="background: linear-gradient(135deg, var(--primary-50) 0%, var(--white) 100%);
                        padding: 3rem 2rem;
                        text-align: center;
                        border-bottom: 1px solid var(--gray-700);">

                <div class="avatar-xl mb-4"
                     style="margin: 0 auto;
                            width: 100px;
                            height: 100px;
                            font-size: 2.5rem;
                            background: white;
                            color: var(--primary-600);
                            border: 4px solid var(--primary-100);">
                    <?php echo htmlspecialchars($initials); ?>
                </div>

                <h2 class="text-2xl font-bold mb-1" style="color: var(--gray-50);">
                    <?php echo htmlspecialchars($fullName); ?>
                </h2>

                <div class="badge badge-primary"
                     style="font-size: 0.8rem; padding: 4px 12px; opacity: 0.8;">
                    <?php echo htmlspecialchars($role); ?>
                </div>
            </div>

            <!-- Details -->
            <div class="p-6">
                <div style="display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                            gap: 1.5rem;
                            margin-bottom: 2rem;">

                    <div>
                        <label class="text-xs uppercase font-bold text-muted mb-1">
                            User ID
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($userid); ?>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs uppercase font-bold text-muted mb-1">
                            Email
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($email); ?>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs uppercase font-bold text-muted mb-1">
                            Department
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($department); ?>
                        </div>
                    </div>

                </div>

                <div style="border-top: 1px solid var(--gray-200);
                            padding-top: 1.5rem;
                            display: flex;
                            gap: 1rem;
                            justify-content: center;">

                    <a href="/catering_system/auth/change_pass.php"
                       class="btn btn-secondary">
                        Change Password
                    </a>

                    <a href="/catering_system/auth/logout.php"
                       class="btn btn-danger-outline"
                       style="border: 1px solid var(--error-500);
                              color: var(--error-500);
                              background: transparent;">
                        Sign Out
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
