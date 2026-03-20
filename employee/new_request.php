<?php
/**
 * NRSC Catering & Meeting Management System
 * New Request - Enterprise Edition
 * PHP 8.1+ Compatible
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$pageTitle = 'New Catering Request | NRSC';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// ---------------------------------------------------------
// 1. INITIAL DATA FETCHING (PRE-FILLS)
// ---------------------------------------------------------
$userId = getCurrentUserId();

// Fetch user data linked with VIMIS_EMPLOYEE for master employee info
$sql = "SELECT u.*, v.EMPLOYEECODE, v.EMPLOYEENAME, v.DESGFULLNAME, v.DIVNFULLNAME, v.SERVICESTATCODE 
        FROM users u 
        LEFT JOIN VIMIS_EMPLOYEE v ON u.userid = v.EMPLOYEECODE 
        WHERE u.id = ?";
$user = fetchOne($sql, [$userId], "i") ?? [];

// Default suggestions: Prioritize VIMIS master data, fallback to users table
$defName  = !empty($user['EMPLOYEENAME']) ? $user['EMPLOYEENAME'] : ($user['name'] ?? '');
$defDept  = !empty($user['DIVNFULLNAME']) ? $user['DIVNFULLNAME'] : ($user['department'] ?? '');
$defDesig = !empty($user['DESGFULLNAME']) ? $user['DESGFULLNAME'] : ($user['designation'] ?? '');
$defPhone = $user['phone'] ?? '';

// Approving officer (Auto-assigned based on reporting hierarchy)
$empCodeForHierarchy = $user['EMPLOYEECODE'] ?? $user['userid'] ?? '';
$offQuery = "SELECT u.id, v.EMPLOYEENAME, v.DIVNFULLNAME 
             FROM users u 
             JOIN VIMIS_EMPLOYEE v ON u.userid = v.EMPLOYEECODE 
             WHERE u.userid = (SELECT REPEMPLOYEECODE FROM TBAD_EMPVSREPEMPPLOYEE WHERE EMPLOYEECODE = ?) 
             AND u.status = 'active'";
$officer = fetchOne($offQuery, [$empCodeForHierarchy], "s") ?? [];

$defOfficerId   = $officer['id'] ?? null;
$defOfficerName = !empty($officer['EMPLOYEENAME']) ? $officer['EMPLOYEENAME'] : '';
$defOfficerDept = !empty($officer['DIVNFULLNAME']) ? $officer['DIVNFULLNAME'] : '';

// Available menu items
$menuItems = fetchAll("SELECT * FROM menu_items ORDER BY category, item_name") ?? [];

$errors = [];
$success = '';

// ---------------------------------------------------------
// 2. FORM SUBMISSION LOGIC
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'submit';

    // Backend enforcement for service status
    if ($action === 'submit' && ($user['SERVICESTATCODE'] ?? '') !== 'SERV') {
        $errors[] = "Operation Error: Only active employees can raise requests.";
        $action = 'save'; // Force save instead of submit if somehow bypassed
    }

    // Sanitize and capture inputs
    // Meeting Details
    $meetingName     = sanitize($_POST['meeting_name'] ?? '');
    $meetingDate     = sanitize($_POST['meeting_date'] ?? '');
    $meetingTime     = sanitize($_POST['meeting_time'] ?? '');
    $area            = sanitize($_POST['area'] ?? '');
    $lic             = sanitize($_POST['lic'] ?? '');

    // Requestor Details (Now Editable Suggestions)
    $reqPerson       = $defName; // Always use master name, remove user editing capability
    $reqDept         = sanitize($_POST['requesting_department'] ?? $defDept);
    $reqDesig        = sanitize($_POST['requesting_designation'] ?? $defDesig);
    $reqPhone        = sanitize($_POST['phone_number'] ?? $defPhone);

    // Approval Details (Auto-assigned)
    $apprBy          = $defOfficerName;
    $apprDept        = $defOfficerDept;
    $targetOfficerId = $defOfficerId;

    // Service Defaults
    $serviceDate     = sanitize($_POST['service_date'] ?? '');
    $serviceTime     = sanitize($_POST['service_time'] ?? '');
    $serviceLocation = sanitize($_POST['service_location'] ?? '');
    $hallCode        = sanitize($_POST['hall_code'] ?? '');

    // Items
    $items           = $_POST['items'] ?? [];
    $quantities      = $_POST['quantities'] ?? [];
    
    // Additional fields
    $guestCount      = (int)($_POST['guest_count'] ?? 0);
    $specialInstr    = sanitize($_POST['special_instructions'] ?? '');

    // Validation
    if (empty($meetingName)) $errors[] = "Meeting name is required.";
    if (empty($meetingDate)) $errors[] = "Meeting date is required.";
    if (empty($area))        $errors[] = "Meeting area/location is required.";
    if (empty($reqPerson))   $errors[] = "Requesting person name is required.";
    if (empty($targetOfficerId)) $errors[] = "No approving officer found in hierarchy. Please contact admin.";
    if (empty($items))       $errors[] = "Please select at least one catering item.";

    if (empty($errors)) {
        $conn = getConnection();
        $conn->begin_transaction();

        try {
            $requestNumber = generateRequestNumber();
            $totalAmount = 0;

            // Calculate total first
            foreach ($items as $idx => $itemId) {
                $menuItem = fetchOne("SELECT price FROM menu_items WHERE id=?", [(int)$itemId], "i");
                if ($menuItem) {
                    $qty = max(1, (int)($quantities[$idx] ?? 1));
                    $totalAmount += $menuItem['price'] * $qty;
                }
            }

            $status = ($action === 'save') ? 'new' : 'pending';

            // Insert Main Request
            $sql = "INSERT INTO catering_requests 
                    (request_number, employee_id, requesting_person, requesting_department, 
                     requesting_designation, phone_number, meeting_name, meeting_date, 
                     meeting_time, area, lic, guest_count, special_instructions, service_date, service_time, 
                     service_location, hall_code, approving_officer_id, approving_by, 
                     approving_department, total_amount, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $requestNumber, $userId, $reqPerson, $reqDept,
                $reqDesig, $reqPhone, $meetingName, $meetingDate,
                $meetingTime, $area, $lic, $guestCount, $specialInstr, $serviceDate, $serviceTime,
                $serviceLocation, $hallCode, $targetOfficerId, $apprBy,
                $apprDept, $totalAmount, $status
            ];
            
            $requestId = insertAndGetId($sql, $params, "sisssssssssisssssissds");

            if (!$requestId) {
                throw new Exception("Failed to create request header.");
            }

            // Insert Items
            foreach ($items as $idx => $itemId) {
                $menuItem = fetchOne("SELECT price FROM menu_items WHERE id=?", [(int)$itemId], "i");
                if ($menuItem) {
                    $qty = max(1, (int)($quantities[$idx] ?? 1));
                    $unitPrice = $menuItem['price'];
                    $subtotal = $unitPrice * $qty;

                    executeQuery(
                        "INSERT INTO request_items (request_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)",
                        [$requestId, (int)$itemId, $qty, $unitPrice],
                        "iiid"
                    );
                }
            }

            // Notify Officer
            if ($status === 'pending' && $targetOfficerId) {
                insertAndGetId(
                    "INSERT INTO notifications (user_id, role, message, link) VALUES (?, 'officer', ?, ?)",
                    [$targetOfficerId, "New catering request #$requestNumber submitted by $reqPerson.", "/catering_system/officer/dashboard.php"],
                    "iss"
                );
            }

            $conn->commit();
            if ($status === 'new') {
                redirect('saved_requests.php', "Request #$requestNumber saved successfully!", 'success');
            } else {
                redirect('my_reqs.php', "Request #$requestNumber submitted successfully!", 'success');
            }

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "System Error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card border-0">
                <div class="card-header text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>NRSC Meeting & Catering Request Form</h5>
                </div>
                
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Submission Error:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="cateringRequestForm" class="needs-validation">
                        <div class="main-grid">
                            <!-- TOP ROW: MEETING & SERVICE DETAILS (2 COLUMNS) -->
                            <div class="grid-row-2">
                                <!-- SECTION 1: MEETING & REQUESTER -->
                                <div class="grid-box">
                                    <div class="section-title mt-0 mb-4">
                                        <h6 class="text-primary text-uppercase fw-bold mb-1">
                                            <i class="fas fa-handshake me-2"></i>1. Meeting & Requester Details
                                        </h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Meeting Name <span class="text-danger">*</span></label>
                                            <input type="text" name="meeting_name" class="form-control" placeholder="Enter meeting purpose/name" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Area / Building <span class="text-danger">*</span></label>
                                            <input type="text" name="area" class="form-control" placeholder="e.g. Ground Station Area" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Meeting Date <span class="text-danger">*</span></label>
                                            <input type="date" name="meeting_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Meeting Time</label>
                                            <input type="time" name="meeting_time" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">LIC (Leader In-Charge)</label>
                                            <input type="text" name="lic" class="form-control" placeholder="Name of LIC">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Guest Count</label>
                                            <input type="number" name="guest_count" class="form-control" value="0" min="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Employee Code</label>
                                            <input type="text" name="employee_code" class="form-control bg-light" value="<?= htmlspecialchars($user['EMPLOYEECODE'] ?? $user['userid'] ?? '') ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                Employee Name <span class="text-danger">*</span>
                                                <?php if (($user['SERVICESTATCODE'] ?? '') === 'SERV'): ?>
                                                    <span class="badge bg-success ms-1" style="font-size: 0.65rem;">Active</span>
                                                <?php elseif (($user['SERVICESTATCODE'] ?? '') === 'PROB'): ?>
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Probation</span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="text" name="requesting_person" class="form-control bg-light" value="<?= htmlspecialchars($defName ?? '') ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Designation</label>
                                            <input type="text" name="requesting_designation" class="form-control bg-light" value="<?= htmlspecialchars($defDesig ?? '') ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Requesting Department</label>
                                            <input type="text" name="requesting_department" class="form-control bg-light" value="<?= htmlspecialchars($defDept ?? '') ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Reporting Officer</label>
                                            <input type="text" name="approving_by" class="form-control bg-light" value="<?= htmlspecialchars($defOfficerName ?? '') ?>" readonly>
                                            <input type="hidden" name="officer_id" value="<?= htmlspecialchars($defOfficerId ?? '') ?>">
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Auto-assigned by hierarchy</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Reporting Department</label>
                                            <input type="text" name="approving_department" class="form-control bg-light" value="<?= htmlspecialchars($defOfficerDept ?? '') ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Contact Phone Number</label>
                                            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($defPhone ?? '') ?>" placeholder="Enter current contact extension">
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 2: SERVICE DETAILS -->
                                <div class="grid-box">
                                    <div class="section-title mt-0 mb-4">
                                        <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                            <i class="fas fa-utensils me-2"></i>2. Primary Service Details
                                        </h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Service Date</label>
                                            <input type="date" name="service_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Service Time</label>
                                            <input type="time" name="service_time" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Venue/Location</label>
                                            <input type="text" name="service_location" class="form-control" placeholder="e.g. Conf Room 101">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Hall Code</label>
                                            <input type="text" name="hall_code" class="form-control" placeholder="Optional">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-danger">Special Instructions (e.g. less oil, spicy, boiled, VIP)</label>
                                            <textarea name="special_instructions" class="form-control" rows="4" placeholder="Enter preparation notes or special requirements..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BOTTOM ROW: ITEMS, SUMMARY, FOOD STATUS (3 COLUMNS) -->
                            <div class="grid-row-3">
                                <!-- SECTION 3: ITEM SELECTION -->
                                <div class="grid-box">
                                    <div class="section-title mt-0 mb-4">
                                        <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                            <i class="fas fa-list-check me-2"></i>3. Catering Item Selection
                                        </h6>
                                    </div>
                                    <div class="bg-light p-3 rounded mb-0">
                                        <div class="row align-items-end g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Select Menu Item</label>
                                                <select id="menu-selector" class="form-select">
                                                    <option value="">-- Choose Item --</option>
                                                    <?php 
                                                    $currentCat = '';
                                                    foreach ($menuItems as $item): 
                                                        if ($currentCat !== $item['category']): 
                                                            if ($currentCat !== '') echo '</optgroup>';
                                                            $currentCat = $item['category'];
                                                            echo '<optgroup label="' . ucfirst($currentCat) . '">';
                                                        endif;
                                                        $isDisabled = ($item['is_available'] == 0);
                                                    ?>
                                                        <option value="<?= $item['id'] ?>" 
                                                                data-name="<?= htmlspecialchars($item['item_name']) ?>" 
                                                                data-price="<?= $item['price'] ?>"
                                                                <?= $isDisabled ? 'disabled style="color: #6c757d;"' : '' ?>>
                                                            <?php if ($isDisabled): ?>
                                                                <?= htmlspecialchars($item['item_name']) ?> (Unavailable)
                                                            <?php else: ?>
                                                                <?= htmlspecialchars($item['item_name']) ?> (₹<?= $item['price'] ?>)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; if ($currentCat !== '') echo '</optgroup>'; ?>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Qty</label>
                                                <input type="number" id="qty" value="1" min="1" class="form-control">
                                            </div>
                                            <div class="col-6">
                                                <button type="button" onclick="addItem()" class="btn btn-success w-100">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 4: ORDER SUMMARY & ACTIONS -->
                                <div class="grid-box">
                                    <div class="section-title mt-0 mb-4">
                                        <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                            <i class="fas fa-cart-shopping me-2"></i>4. Order Summary
                                        </h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table custom-table" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Subtotal</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="empty-row">
                                                    <td colspan="5" class="text-center text-muted">No items added yet.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="order-actions">
                                        <div class="total-box">
                                            Total: <span id="grand-total">₹0.00</span>
                                        </div>

                                        <div class="btn-group">
                                            <button type="submit" name="action" value="save" class="btn btn-primary">
                                                Save
                                            </button>
                                            <?php $isServ = (($user['SERVICESTATCODE'] ?? '') === 'SERV'); ?>
                                            <button type="submit" name="action" value="submit" class="btn btn-success" <?= !$isServ ? 'disabled' : '' ?>>
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                    <?php if (!$isServ): ?>
                                        <div class="text-danger small fw-bold mt-2 text-end">
                                            <i class="fas fa-triangle-exclamation me-1"></i>
                                            Only active employees can raise requests
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- SECTION 5: FOOD AVAILABILITY -->
                                <div class="grid-box food-box">
                                    <div class="section-title mt-0 mb-4">
                                        <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                            <i class="fas fa-utensils me-2"></i>5. Food Availability
                                        </h6>
                                    </div>
                                    <div class="food-scroll">
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($menuItems as $item): ?>
                                                <li class="food-item">
                                                    <span class="food-item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                                    <?php if ($item['is_available']): ?>
                                                        <span class="badge bg-success rounded-pill bg-opacity-75">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger rounded-pill bg-opacity-75">Inactive</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ========================= */
/* 🔥 MASTER GRID */
/* ========================= */
.main-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ========================= */
/* TOP ROW (2 COLUMNS) */
/* ========================= */
.grid-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* ========================= */
/* BOTTOM ROW (3 COLUMNS) */
/* ========================= */
.grid-row-3 {
    display: grid;
    grid-template-columns: 1.2fr 1.2fr 0.8fr;
    gap: 20px;
}

/* ========================= */
/* COMMON BOX */
/* ========================= */
.grid-box {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 6px 15px rgba(0,0,0,0.05);
}

/* ========================= */
/* FOOD PANEL SMALL */
/* ========================= */
.food-box {
    max-height: 400px; /* Increased to accommodate content naturally */
    overflow: hidden;
}

/* Scroll inside */
.food-scroll {
    max-height: 220px;
    overflow-y: auto;
}

/* ========================= */
/* SECTION TITLE */
/* ========================= */
.section-title {
    border-left: 4px solid #1a56db;
    padding-left: 10px;
    margin-bottom: 15px;
}

.section-title h6 {
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #1e429f;
    margin-bottom: 0;
}

/* ========================= */
/* FOOD ITEMS */
/* ========================= */
.food-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.food-item-name {
    font-size: 0.85rem;
    color: #4b5563;
}

.food-item .badge {
    font-size: 0.7rem;
}

/* Form refinement */
.form-control, .form-select {
    padding: 0.5rem 0.8rem;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 0.85rem;
}

.btn {
    border-radius: 8px;
    font-size: 0.8rem;
}

/* ========================= */
/* 🔥 ORDER SUMMARY FIX */
/* ========================= */

.custom-table {
    width: 100%;
    font-size: 0.85rem;
}

.custom-table th {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #6b7280;
}

.custom-table td {
    vertical-align: middle;
}

/* Prevent overflow */
.table-responsive {
    overflow-x: auto;
}

/* TOTAL + BUTTON ALIGN */
.order-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

/* Total styling */
.total-box {
    font-weight: 600;
    font-size: 0.95rem;
}

#grand-total {
    color: #1a56db;
    font-weight: bold;
}

/* Buttons inline */
.btn-group {
    display: flex;
    gap: 10px;
}

/* Prevent button shrink */
.btn-group .btn {
    white-space: nowrap;
}
</style>

<script>
let totalAmount = 0;

function addItem() {
    const select = document.getElementById('menu-selector');
    const option = select.options[select.selectedIndex];
    if (!option.value) {
        alert("Please select a valid item.");
        return;
    }

    const qty = parseInt(document.getElementById('qty').value) || 1;
    const name = option.dataset.name;
    const price = parseFloat(option.dataset.price);
    const subtotal = price * qty;

    const tableBody = document.querySelector('#items-table tbody');
    const emptyRow = document.getElementById('empty-row');
    if (emptyRow) emptyRow.remove();

    const rowCount = tableBody.rows.length + 1;
    const row = tableBody.insertRow();
    
    row.innerHTML = `
        <td class="text-center fw-bold">${rowCount}</td>
        <td>
            ${name}
            <input type="hidden" name="items[]" value="${option.value}">
        </td>
        <td>
            <input type="hidden" name="quantities[]" value="${qty}">
            ${qty}
        </td>
        <td class="fw-bold">₹${subtotal.toFixed(2)}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="removeRow(this, ${subtotal})">
                <i class="fas fa-trash-can"></i>
            </button>
        </td>
    `;

    updateTotal(subtotal);
    select.selectedIndex = 0;
    document.getElementById('qty').value = 1;
}

function removeRow(btn, sub) {
    btn.closest('tr').remove();
    updateTotal(-sub);
    
    const tbody = document.querySelector('#items-table tbody');
    if (tbody.rows.length === 0) {
        tbody.innerHTML = `
            <tr id="empty-row">
                <td colspan="5" class="text-center text-muted">No items added yet.</td>
            </tr>
        `;
    }
}

function updateTotal(amt) {
    totalAmount += amt;
    document.getElementById('grand-total').innerText = `₹${totalAmount.toFixed(2)}`;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>