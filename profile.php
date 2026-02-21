<?php
/**
 * NRSC Catering & Meeting Management System
 * Unified Enterprise Profile System - PHP 8.1+ Hardened Version
 * Production Grade Workflow | Refined Enterprise UI (Compact & Professional)
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
    echo '<div class="container py-4"><div class="alert alert-danger p-4 shadow-sm border-0 rounded-3">
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

$pageTitle = 'Profile Management';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 fade-in">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            
            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm mb-3 py-2 px-3 d-flex align-items-center small">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?= htmlspecialchars((string)$success) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.5rem;"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-3 py-2 px-3 small">
                    <div class="fw-bold mb-1"><i class="fas fa-times-circle me-2"></i>Validation Errors:</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars((string)$err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card profile-card border-0 shadow-sm rounded-3 overflow-hidden">
                <main class="row g-0">
                    <!-- Left: Identity Panel -->
                    <aside class="col-lg-4 bg-light p-4 text-center border-bottom border-lg-0 border-lg-end">
                        <div class="avatar-container position-relative mb-3">
                            <?php if ($u['img']): ?>
                                <img src="<?= htmlspecialchars($u['img']) ?>" alt="Avatar" class="profile-avatar shadow-sm">
                            <?php else: ?>
                                <div class="profile-avatar-placeholder shadow-sm">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="position-absolute bottom-0 end-0 translate-middle-x">
                                <label for="profile_image" class="btn btn-primary btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;" title="Update Photo">
                                    <i class="fas fa-camera" style="font-size: 0.75rem;"></i>
                                </label>
                                <input type="file" id="profile_image" name="profile_image" class="d-none" onchange="this.form.submit()">
                            </form>
                        </div>

                        <h2 class="mb-1 text-dark" style="font-size: 1.15rem; font-weight: 700;"><?= htmlspecialchars($u['name']) ?></h2>
                        <div class="text-secondary small mb-3" style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($u['desig']) ?></div>
                        
                        <div class="badge <?= $badgeClass ?> border border-opacity-10 px-2 py-1 mb-4 rounded-pill" style="font-size: 11px;">
                            <i class="fas fa-shield-halved me-1"></i> <?= strtoupper($u['role']) ?> ACCESS
                        </div>

                        <div class="d-grid gap-2 mb-4 px-3">
                            <button type="button" id="editTrigger" class="btn btn-primary btn-enterprise" onclick="toggleEdit(true)">
                                <i class="fas fa-user-pen me-2"></i>Edit Records
                            </button>
                            <a href="auth/change_pass.php" class="btn btn-secondary btn-enterprise-ghost">
                                <i class="fas fa-key me-2"></i>Security Key
                            </a>
                        </div>

                        <div class="metadata-box text-start p-3 mx-2 border-top">
                            <div class="metadata-label mb-2">System Informatics</div>
                            <div class="metadata-item">
                                <span class="metadata-key">Node ID:</span>
                                <span class="metadata-val"><?= htmlspecialchars($u['userid']) ?></span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-key">Last Sync:</span>
                                <span class="metadata-val"><?= date('d M Y, H:i', strtotime($u['updated'])) ?></span>
                            </div>
                        </div>
                    </aside>

                    <!-- Right: Informatics Panel -->
                    <section class="col-lg-8 p-4">
                        <form id="mainProfileForm" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                                <h2 class="mb-0" style="font-size: 1rem; font-weight: 700; color: #374151;">Account Informatics</h2>
                                <div class="ms-auto text-muted" style="font-size: 0.75rem;">
                                    <i class="fas fa-lock me-1"></i> Enterprise Secured
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="info-label">Full Name</label>
                                    <input type="text" name="name" class="form-control profile-field" value="<?= htmlspecialchars($u['name']) ?>" readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Employee ID</label>
                                    <input type="text" class="form-control profile-field-static" value="<?= htmlspecialchars($u['userid']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Email Address</label>
                                    <input type="email" name="email" class="form-control profile-field" value="<?= htmlspecialchars($u['email']) ?>" readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Phone Contact</label>
                                    <input type="text" name="phone" class="form-control profile-field" value="<?= htmlspecialchars($u['phone']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Department / Unit</label>
                                    <input type="text" name="department" class="form-control profile-field" value="<?= htmlspecialchars($u['dept']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Designation</label>
                                    <input type="text" name="designation" class="form-control profile-field" value="<?= htmlspecialchars($u['desig']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Security Node Status</label>
                                    <div class="p-1 fw-bold text-<?= $u['status'] === 'active' ? 'success' : 'danger' ?> shadow-none border-0" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle-check me-1"></i> <?= strtoupper($u['status']) ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="info-label">Access Level</label>
                                    <div class="p-1 fw-bold text-dark" style="font-size: 0.9rem;"><?= strtoupper($u['role']) ?></div>
                                </div>
                            </div>

                            <div id="saveCluster" class="mt-4 pt-3 border-top d-none">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4 btn-enterprise">
                                        <i class="fas fa-save me-2"></i>SAVE CHANGES
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary px-3 btn-enterprise" onclick="toggleEdit(false)">
                                        DISCARD
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>
                </main>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --nrc-primary: #1a56db;
        --nrc-primary-dark: #12429f;
        --nrc-border: #e2e8f0;
        --nrc-bg-light: #f8fafc;
        --nrc-text-main: #1e293b;
        --nrc-text-muted: #64748b;
        --radius: 12px;
    }

    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f1f5f9; }

    .fade-in { animation: fadeInAnim 0.3s ease-out; }
    @keyframes fadeInAnim { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .profile-card {
        border-radius: var(--radius);
        border: 1px solid var(--nrc-border);
        background: #fff;
        box-shadow: 0 2px 6px -1px rgba(0,0,0,0.05) !important;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        object-fit: cover;
        border: 2px solid #fff;
        transition: all 0.2s ease;
    }

    .avatar-container:hover .profile-avatar {
        transform: scale(1.05);
    }
    
    .profile-avatar-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--nrc-primary), var(--nrc-primary-dark));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        border: 2px solid #fff;
        transition: all 0.2s ease;
    }

    .info-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--nrc-text-muted);
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        display: block;
    }

    .profile-field[readonly] {
        background: transparent !important;
        border: 1px solid transparent !important;
        font-weight: 500;
        font-size: 14px;
        color: var(--nrc-text-main);
        padding: 4px 0;
        height: auto;
        min-height: unset;
        box-shadow: none !important;
    }

    .profile-field-static {
        background: #f1f5f9 !important;
        border: 1px solid transparent !important;
        font-weight: 500;
        font-size: 14px;
        padding: 6px 12px;
        border-radius: 8px;
        color: var(--nrc-text-main);
    }

    .profile-field:not([readonly]) {
        background: #fff !important;
        border: 1px solid #d1d5db !important;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .profile-field:not([readonly]):focus {
        border-color: var(--nrc-primary);
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1) !important;
        outline: none;
    }

    /* Enterprise Buttons */
    .btn-enterprise {
        font-size: 13px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-enterprise-ghost {
        background: #f1f5f9;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 16px;
        border: 1px solid transparent;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-primary.btn-enterprise {
        background: var(--nrc-primary);
        border: none;
    }

    .btn-primary.btn-enterprise:hover {
        background: var(--nrc-primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(26, 86, 219, 0.2);
    }

    .btn-enterprise-ghost:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* Metadata Items */
    .metadata-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--nrc-text-muted);
        letter-spacing: 1px;
    }

    .metadata-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        font-size: 12px;
    }

    .metadata-key { color: #94a3b8; }
    .metadata-val { color: #475569; font-weight: 600; }

    @media (max-width: 768px) {
        .col-lg-4 {
            border-right: none;
            padding-bottom: 24px;
        }
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
