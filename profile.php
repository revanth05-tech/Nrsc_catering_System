<?php
/**
 * NRSC Catering & Meeting Management System
 * Unified Enterprise Profile System - PHP 8.1+ Hardened Version
 * Production Grade Workflow | Refined Enterprise UI (Centered Layout)
 */

require_once __DIR__ . '/includes/auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];
$errors = [];
$success = '';

// ---------------------------------------------------------
// 1. HANDLE PROFILE IMAGE UPLOAD & REMOVAL
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $errors[] = "File size exceeds 2MB limit.";
        } else {
            $uploadDir = __DIR__ . '/uploads/profile_images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Fetch current image to remove later
            $current = fetchOne("SELECT profile_image FROM users WHERE id = ?", [$userId], "i");
            
            $extension = pathinfo((string)$file['name'], PATHINFO_EXTENSION);
            $fileName = $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $relativePath = 'uploads/profile_images/' . $fileName;
                
                if (executeQuery("UPDATE users SET profile_image = ? WHERE id = ?", [$relativePath, $userId], "si")) {
                    // Remove old physical file if exists
                    if (!empty($current['profile_image']) && file_exists(__DIR__ . '/' . $current['profile_image'])) {
                        unlink(__DIR__ . '/' . $current['profile_image']);
                    }
                    $success = "Profile photo updated successfully!";
                } else {
                    $errors[] = "Database update failed.";
                }
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        }
    }
}

// ---------------------------------------------------------
// 2. HANDLE PROFILE DATA UPDATE
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitize((string)($_POST['name'] ?? ''));
    $email = sanitize((string)($_POST['email'] ?? ''));
    $phone = sanitize((string)($_POST['phone'] ?? ''));
    $department = sanitize((string)($_POST['department'] ?? ''));
    $designation = sanitize((string)($_POST['designation'] ?? ''));

    if (empty($name)) $errors[] = "Full Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid enterprise email is required.";
    if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "Phone number must be 10-15 digits.";
    }

    if (empty($errors)) {
        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, department = ?, designation = ? WHERE id = ?";
        if (executeQuery($sql, [$name, $email, $phone, $department, $designation, $userId], "sssssi")) {
            $success = "Profile data synchronized successfully!";
        } else {
            $errors[] = "Update failed. Please check system logs.";
        }
    }
}

// ---------------------------------------------------------
// 3. DEFENSIVE DATA FETCHING (PHP 8.1+ HARDENED)
// ---------------------------------------------------------
$userDataRaw = fetchOne("SELECT * FROM users WHERE id = ?", [$userId], "i");

if (!$userDataRaw) {
    include __DIR__ . '/includes/header.php';
    echo '<div class="container py-4"><div class="alert alert-danger p-4 shadow-sm border-0 rounded-3 text-center">
            <h4 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Critical Integrity Error</h4>
            <p class="mb-0">Your user node profile could not be synchronized with the central enterprise directory.</p>
            <a href="index.php" class="btn btn-primary mt-3">Re-authenticate</a>
          </div></div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

// Type-Safe Snapshot for UI
$u = [
    'name'      => (string)($userDataRaw['name'] ?? 'User'),
    'userid'    => (string)($userDataRaw['userid'] ?? 'N/A'),
    'email'     => (string)($userDataRaw['email'] ?? ''),
    'phone'     => (string)($userDataRaw['phone'] ?? ''),
    'dept'      => (string)($userDataRaw['department'] ?? 'N/A'),
    'desig'     => (string)($userDataRaw['designation'] ?? 'Employee'),
    'img'       => (string)($userDataRaw['profile_image'] ?? ''),
    'role'      => (string)($userDataRaw['role'] ?? 'employee'),
    'status'    => (string)($userDataRaw['status'] ?? 'active'),
    'updated'   => (string)($userDataRaw['updated_at'] ?? date('Y-m-d H:i:s'))
];

// Role Badge Color Mapping
$roleColors = [
    'employee' => 'bg-info-subtle text-info-emphasis',
    'officer'  => 'bg-warning-subtle text-warning-emphasis',
    'admin'    => 'bg-primary-subtle text-primary-emphasis',
    'canteen'  => 'bg-success-subtle text-success-emphasis'
];
$badgeClass = $roleColors[$u['role']] ?? 'bg-body-secondary';

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 fade-in">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            
            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm mb-3 py-2 px-3 d-flex align-items-center small fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?= htmlspecialchars((string)$success) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.5rem;"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-3 py-2 px-3 small fade show">
                    <div class="fw-bold mb-1"><i class="fas fa-times-circle me-2"></i>Validation Errors:</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars((string)$err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card profile-card border-0 shadow-sm rounded-3 overflow-hidden">
                <!-- Profile Header - Centered Layout -->
                <header class="profile-header bg-light border-bottom">
                    <div class="profile-header-container">
                        <div class="avatar-wrapper position-relative mb-3">
                            <?php if ($u['img']): ?>
                                <img src="<?= htmlspecialchars($u['img']) ?>" alt="Avatar" class="profile-avatar shadow-sm">
                            <?php else: ?>
                                <div class="profile-avatar-placeholder shadow-sm">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="position-absolute bottom-0 start-50 translate-middle-x" style="bottom: -15px;">
                                <label for="profile_image" class="btn btn-white btn-sm px-2 py-1 shadow-sm border rounded-pill d-flex align-items-center gap-1" style="cursor: pointer; font-size: 11px; font-weight: 600;">
                                    <i class="fas fa-camera text-primary"></i> <span>Edit</span>
                                </label>
                                <input type="file" id="profile_image" name="profile_image" class="d-none" onchange="this.form.submit()">
                            </form>
                        </div>

                        <h2 class="profile-name mb-1"><?= htmlspecialchars($u['name']) ?></h2>
                        <div class="profile-role mb-2"><?= htmlspecialchars($u['desig']) ?></div>
                        
                        <div class="badge <?= $badgeClass ?> border border-opacity-10 px-2 py-1 mb-3 rounded-pill" style="font-size: 11px;">
                            <i class="fas fa-shield-halved me-1"></i> <?= strtoupper($u['role']) ?> ACCESS
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" id="editTrigger" class="btn btn-primary btn-enterprise" onclick="toggleEdit(true)">
                                <i class="fas fa-user-pen me-2"></i>Edit Profile
                            </button>
                            <a href="auth/change_pass.php" class="btn btn-enterprise-ghost text-dark">
                                <i class="fas fa-key me-2"></i>Reset Pin
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Informatics Panel -->
                <section class="p-4">
                    <form id="mainProfileForm" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <h2 class="mb-0 fs-6 fw-bold color-gray-700">Enterprise Informatics</h2>
                            <div class="ms-auto text-muted small">
                                <i class="fas fa-lock me-1"></i> SSL Protected
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="info-label">Full Name</label>
                                <input type="text" name="name" class="form-control profile-field" value="<?= htmlspecialchars($u['name']) ?>" readonly required>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Personal ID</label>
                                <input type="text" class="form-control profile-field-static" value="<?= htmlspecialchars($u['userid']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">E-Mail Identity</label>
                                <input type="email" name="email" class="form-control profile-field" value="<?= htmlspecialchars($u['email']) ?>" readonly required>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Contact Node</label>
                                <input type="text" name="phone" class="form-control profile-field" value="<?= htmlspecialchars($u['phone']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Dept / Unit Code</label>
                                <input type="text" name="department" class="form-control profile-field" value="<?= htmlspecialchars($u['dept']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Office Designation</label>
                                <input type="text" name="designation" class="form-control profile-field" value="<?= htmlspecialchars($u['desig']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Status Level</label>
                                <div class="px-1 py-0 fw-bold text-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle-check me-1"></i> <?= strtoupper($u['status']) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Credential Role</label>
                                <div class="px-1 py-0 fw-bold text-dark" style="font-size: 0.9rem;"><?= strtoupper($u['role']) ?> ACCESS</div>
                            </div>
                        </div>

                        <div id="saveCluster" class="mt-4 pt-3 border-top d-none">
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="submit" class="btn btn-success px-4 btn-enterprise">
                                    <i class="fas fa-save me-2"></i>SAVE CHANGES
                                </button>
                                <button type="button" class="btn btn-outline-secondary px-3 btn-enterprise" onclick="toggleEdit(false)">
                                    DISCARD
                                </button>
                            </div>
                        </div>
                    </form>

                    <footer class="mt-5 pt-3 border-top">
                        <div class="metadata-row d-flex justify-content-between">
                            <div class="metadata-item">
                                <span class="metadata-key">System Identifier:</span>
                                <span class="metadata-val"><?= htmlspecialchars($u['userid']) ?></span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-key">Last Directory Sync:</span>
                                <span class="metadata-val"><?= date('d M Y, h:i A', strtotime($u['updated'])) ?></span>
                            </div>
                        </div>
                    </footer>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --nrc-primary: #1a56db;
        --nrc-primary-dark: #12429f;
        --nrc-border: #eef2f6;
        --nrc-bg-light: #f8fafc;
        --nrc-text-main: #1f2937;
        --nrc-text-muted: #6b7280;
    }

    body { font-family: 'Inter', system-ui, sans-serif; background-color: #f3f4f6; }

    .fade-in { animation: fadeInAnim 0.3s ease-out; }
    @keyframes fadeInAnim { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .profile-card {
        border: 1px solid var(--nrc-border);
        background: #fff;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important;
    }

    /* Centered Header Layout */
    .profile-header {
        padding: 32px 24px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
    }

    .profile-header-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50% !important; /* Perfect circle as requested */
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }

    .avatar-wrapper:hover .profile-avatar {
        transform: scale(1.05);
    }
    
    .profile-avatar-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50% !important; /* Perfect circle as requested */
        background: linear-gradient(135deg, var(--nrc-primary), var(--nrc-primary-dark));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .profile-name { font-size: 18px; font-weight: 700; color: var(--nrc-text-main); }
    .profile-role { font-size: 14px; font-weight: 500; color: var(--nrc-text-muted); }

    .info-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--nrc-text-muted);
        letter-spacing: 0.5px;
        margin-bottom: 2px;
        display: block;
    }

    .profile-field[readonly] {
        background: transparent !important;
        border: 1px solid transparent !important;
        font-weight: 600;
        font-size: 14px;
        color: #111;
        padding: 4px 0;
        height: auto;
        box-shadow: none !important;
    }

    .profile-field-static {
        background: #f3f4f6 !important;
        border: 1px solid transparent !important;
        font-weight: 500;
        font-size: 14px;
        padding: 6px 12px;
        border-radius: 8px;
    }

    .profile-field:not([readonly]) {
        background: #fff !important;
        border: 1px solid #d1d5db !important;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 14px;
        transition: border 0.2s;
    }

    .profile-field:not([readonly]):focus {
        border-color: var(--nrc-primary);
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1) !important;
    }

    /* Button Styling Fixed */
    .btn-enterprise {
        font-size: 13px;
        font-weight: 500;
        padding: 8px 18px;
        border-radius: 8px;
        transition: all 0.2s ease;
        text-align: center;
    }

    .btn-primary.btn-enterprise {
        background: var(--nrc-primary);
        color: #ffffff !important; /* Forced white text visibility */
        border: none;
    }

    .btn-primary.btn-enterprise:hover {
        background: var(--nrc-primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 86, 219, 0.2);
    }

    .btn-enterprise-ghost {
        background: #fff;
        border: 1px solid #d1d5db;
        color: #374151 !important;
    }

    .btn-enterprise-ghost:hover {
        background: #f9fafb;
        transform: translateY(-2px);
    }

    .btn-white {
        background: #fff;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-white:hover {
        background: #f9fafb;
        color: var(--nrc-primary);
        border-color: var(--nrc-primary);
    }

    /* Metadata Footer */
    .metadata-item { font-size: 12px; }
    .metadata-key { color: #9ca3af; margin-right: 5px; }
    .metadata-val { color: #4b5563; font-weight: 600; }

    @media (max-width: 576px) {
        .metadata-row { flex-direction: column; gap: 8px; text-align: center; }
    }
</style>

<script>
function toggleEdit(on) {
    const fields = document.querySelectorAll('.profile-field');
    const trigger = document.getElementById('editTrigger');
    const cluster = document.getElementById('saveCluster');
    
    fields.forEach(f => {
        if(on) {
            f.removeAttribute('readonly');
            f.style.padding = "8px 12px";
            f.style.backgroundColor = "#fff";
        } else {
            f.setAttribute('readonly', 'true');
            f.style.padding = "4px 0";
            f.style.backgroundColor = "transparent";
        }
    });

    if(on) {
        trigger.classList.add('d-none');
        cluster.classList.remove('d-none');
    } else {
        trigger.classList.remove('d-none');
        cluster.classList.add('d-none');
        document.getElementById('mainProfileForm').reset();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
