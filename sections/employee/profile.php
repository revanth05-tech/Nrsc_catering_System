<?php
// Unified Profile Section
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-error'>User not logged in.</div>";
    return;
}

$userId = $_SESSION['user_id'];
$userData = fetchOne("SELECT * FROM users WHERE id = ?", [$userId], "i");

if (!$userData) {
    echo "<div class='alert alert-error'>User data not found.</div>";
    return;
}

/* SAFE FIELD HANDLING */
$userName     = $userData['name']        ?? $userData['userid'] ?? 'User';
$userRole     = $userData['role']        ?? 'User';
$userEmail    = $userData['email']       ?? 'N/A';
$userDept     = $userData['department']  ?? 'General';
$userDesig    = $userData['designation'] ?? 'Employee';
$userUsername = $userData['userid']      ?? 'N/A';

$userInitials = strtoupper(substr($userName, 0, 2));
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div class="card profile-card">
        <div class="card-body p-0">

            <!-- Profile Header -->
            <div style="background: linear-gradient(135deg, var(--primary-50) 0%, var(--white) 100%);
                        padding: 3rem 2rem;
                        text-align: center;
                        border-bottom: 1px solid var(--gray-200);">

                <div class="avatar-xl mb-4"
                     style="margin: 0 auto;
                            width: 100px;
                            height: 100px;
                            font-size: 2.5rem;
                            background: white;
                            color: var(--primary-600);
                            border: 4px solid var(--primary-100);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:50%;">

                    <?php echo htmlspecialchars($userInitials); ?>
                </div>

                <h2 class="text-2xl font-bold mb-1" style="color: var(--gray-800);">
                    <?php echo htmlspecialchars($userName); ?>
                </h2>

                <div class="badge badge-primary"
                     style="font-size: 0.8rem;
                            padding: 4px 12px;
                            opacity: 0.9;">
                    <?php echo ucfirst(htmlspecialchars($userRole)); ?>
                </div>
            </div>

            <!-- Profile Details -->
            <div class="p-6">

                <div style="display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                            gap: 1.5rem;
                            margin-bottom: 2rem;">

                    <div>
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1">
                            User ID / Username
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($userUsername); ?>
                        </div>
                    </div>

                    <div>
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1">
                            Email Address
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($userEmail); ?>
                        </div>
                    </div>

                    <div>
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1">
                            Department
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($userDept); ?>
                        </div>
                    </div>

                    <div>
                        <label class="d-block text-xs uppercase font-bold text-muted mb-1">
                            Designation
                        </label>
                        <div class="text-base font-medium">
                            <?php echo htmlspecialchars($userDesig); ?>
                        </div>
                    </div>

                </div>

                <!-- Action Buttons -->
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
                       class="btn"
                       style="border: 1px solid #ef4444;
                              color: #ef4444;
                              background: transparent;">
                        Sign Out
                    </a>

                </div>

            </div>
        </div>
    </div>
</div>
