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
$user   = fetchOne("SELECT * FROM users WHERE id = ?", [$userId], "i") ?? [];

// Default suggestions from users table
$defName  = $user['name'] ?? '';
$defDept  = $user['department'] ?? '';
$defDesig = $user['designation'] ?? '';
$defPhone = $user['phone'] ?? '';

// Active approving officer (auto-assignment)
$officer = fetchOne("SELECT * FROM users WHERE role='officer' AND status='active' LIMIT 1") ?? [];
$defOfficerId   = $officer['id'] ?? null;
$defOfficerName = $officer['name'] ?? '';
$defOfficerDept = $officer['department'] ?? '';

// Available menu items
$menuItems = fetchAll("SELECT * FROM menu_items WHERE is_available=1 ORDER BY category, item_name") ?? [];

$errors = [];
$success = '';

// ---------------------------------------------------------
// 2. FORM SUBMISSION LOGIC
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize and capture inputs
    // Meeting Details
    $meetingName     = sanitize($_POST['meeting_name'] ?? '');
    $meetingDate     = sanitize($_POST['meeting_date'] ?? '');
    $meetingTime     = sanitize($_POST['meeting_time'] ?? '');
    $area            = sanitize($_POST['area'] ?? '');
    $lic             = sanitize($_POST['lic'] ?? '');

    // Requestor Details (Now Editable Suggestions)
    $reqPerson       = sanitize($_POST['requesting_person'] ?? $defName);
    $reqDept         = sanitize($_POST['requesting_department'] ?? $defDept);
    $reqDesig        = sanitize($_POST['requesting_designation'] ?? $defDesig);
    $reqPhone        = sanitize($_POST['phone_number'] ?? $defPhone);

    // Approval Details (Now Editable Suggestions)
    $apprBy          = sanitize($_POST['approving_by'] ?? $defOfficerName);
    $apprDept        = sanitize($_POST['approving_department'] ?? $defOfficerDept);
    $targetOfficerId = !empty($_POST['officer_id']) ? (int)$_POST['officer_id'] : $defOfficerId;

    // Service Defaults
    $serviceDate     = sanitize($_POST['service_date'] ?? '');
    $serviceTime     = sanitize($_POST['service_time'] ?? '');
    $serviceLocation = sanitize($_POST['service_location'] ?? '');
    $hallCode        = sanitize($_POST['hall_code'] ?? '');

    // Items
    $items           = $_POST['items'] ?? [];
    $quantities      = $_POST['quantities'] ?? [];

    // Validation
    if (empty($meetingName)) $errors[] = "Meeting name is required.";
    if (empty($meetingDate)) $errors[] = "Meeting date is required.";
    if (empty($area))        $errors[] = "Meeting area/location is required.";
    if (empty($reqPerson))   $errors[] = "Requesting person name is required.";
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

            // Insert Main Request
            $sql = "INSERT INTO catering_requests 
                    (request_number, employee_id, requesting_person, requesting_department, 
                     requesting_designation, phone_number, meeting_name, meeting_date, 
                     meeting_time, area, lic, service_date, service_time, 
                     service_location, hall_code, approving_officer_id, approving_by, 
                     approving_department, total_amount, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $params = [
                $requestNumber, $userId, $reqPerson, $reqDept,
                $reqDesig, $reqPhone, $meetingName, $meetingDate,
                $meetingTime, $area, $lic, $serviceDate, $serviceTime,
                $serviceLocation, $hallCode, $targetOfficerId, $apprBy,
                $apprDept, $totalAmount
            ];
            
            $requestId = insertAndGetId($sql, $params, "sisssssssssssssissd");

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

            $conn->commit();
            redirect('my_requests.php', "Request #$requestNumber submitted successfully!", 'success');

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
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
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
                        
                        <!-- 1. MEETING DETAILS SECTION -->
                        <div class="section-title mb-4">
                            <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                <i class="fas fa-handshake me-2"></i>1. Meeting & Requester Details
                            </h6>
                        </div>

                        <div class="row g-3">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Meeting Name <span class="text-danger">*</span></label>
                                    <input type="text" name="meeting_name" class="form-control" placeholder="Enter meeting purpose/name" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Meeting Date <span class="text-danger">*</span></label>
                                        <input type="date" name="meeting_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Meeting Time</label>
                                        <input type="time" name="meeting_time" class="form-control">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Requesting Person <span class="text-danger">*</span></label>
                                    <input type="text" name="requesting_person" class="form-control" value="<?= htmlspecialchars($defName ?? '') ?>" placeholder="Search name...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Requesting Department</label>
                                    <input type="text" name="requesting_department" class="form-control" value="<?= htmlspecialchars($defDept ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Approving Officer Name</label>
                                    <input type="text" name="approving_by" class="form-control bg-light-blue" value="<?= htmlspecialchars($defOfficerName ?? '') ?>">
                                    <input type="hidden" name="officer_id" value="<?= htmlspecialchars($defOfficerId ?? '') ?>">
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Area / Building <span class="text-danger">*</span></label>
                                    <input type="text" name="area" class="form-control" placeholder="e.g. Ground Station Area" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">LIC (Leader In-Charge)</label>
                                    <input type="text" name="lic" class="form-control" placeholder="Name of LIC">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Designation</label>
                                    <input type="text" name="requesting_designation" class="form-control" value="<?= htmlspecialchars($defDesig ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($defPhone ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Approving Department</label>
                                    <input type="text" name="approving_department" class="form-control bg-light-blue" value="<?= htmlspecialchars($defOfficerDept ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- 2. SERVICE INFORMATION SECTION -->
                        <div class="section-title mt-4 mb-4">
                            <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                <i class="fas fa-utensils me-2"></i>2. Primary Service Details
                            </h6>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Service Date</label>
                                <input type="date" name="service_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Service Time</label>
                                <input type="time" name="service_time" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Specific Venue/Location</label>
                                <input type="text" name="service_location" class="form-control" placeholder="e.g. Conf Room 101">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Hall Code</label>
                                <input type="text" name="hall_code" class="form-control" placeholder="Optional">
                            </div>
                        </div>

                        <!-- 3. ITEM SELECTION SECTION -->
                        <div class="section-title mt-5 mb-4">
                            <h6 class="text-primary text-uppercase fw-bold border-bottom pb-2">
                                <i class="fas fa-list-check me-2"></i>3. Catering Item Selection
                            </h6>
                        </div>

                        <div class="bg-light p-3 rounded mb-4">
                            <div class="row align-items-end g-3">
                                <div class="col-md-6">
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
                                        ?>
                                            <option value="<?= $item['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($item['item_name']) ?>" 
                                                    data-price="<?= $item['price'] ?>">
                                                <?= htmlspecialchars($item['item_name']) ?> (₹<?= $item['price'] ?>)
                                            </option>
                                        <?php endforeach; if ($currentCat !== '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Quantity</label>
                                    <input type="number" id="qty" value="1" min="1" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" onclick="addItem()" class="btn btn-success w-100">
                                        <i class="fas fa-plus me-1"></i> Add to List
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="items-table">
                                <thead class="table-info">
                                    <tr>
                                        <th width="80">#</th>
                                        <th>Description</th>
                                        <th width="120">Unit Price</th>
                                        <th width="100">Qty</th>
                                        <th width="120">Subtotal</th>
                                        <th width="80">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="empty-row">
                                        <td colspan="6" class="text-center text-muted py-4">No items added yet. Search or select above to add.</td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Total Amount:</th>
                                        <th id="grand-total" class="text-primary fs-5">₹0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- ACTION BAR -->
                        <div class="d-flex flex-wrap gap-2 justify-content-center mt-5 p-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                            </button>
                            <button type="reset" class="btn btn-outline-secondary px-4" onclick="return confirm('Clear all data?')">
                                <i class="fas fa-trash-can me-2"></i>Clear Form
                            </button>
                            <button type="button" class="btn btn-secondary px-4" onclick="window.location.href='dashboard.php'">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-blue { background-color: #f0f7ff !important; }
    .section-title h6 { letter-spacing: 0.5px; }
    .form-control:focus { box-shadow: 0 0 0 0.25 cold-grey; border-color: var(--primary-400); }
    .table-info { --bs-table-bg: #e7f1ff; }
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
        <td>₹${price.toFixed(2)}</td>
        <td>
            <input type="hidden" name="quantities[]" value="${qty}">
            ${qty}
        </td>
        <td class="fw-bold">₹${subtotal.toFixed(2)}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this, ${subtotal})">
                <i class="fas fa-times"></i>
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
                <td colspan="6" class="text-center text-muted py-4">No items added yet. Search or select above to add.</td>
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