<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$pageTitle = 'NRSC Catering Request - Enterprise';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'] ?? 0;
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId], "i") ?? [];
$officer = fetchOne("SELECT * FROM users WHERE role='officer' AND status='active' LIMIT 1") ?? [];

/* ================= SAFE VARIABLES ================= */

$userName  = $user['name'] ?? '';
$userDept  = $user['department'] ?? '';
$userPhone = $user['phone'] ?? '';
$userRole  = $user['role'] ?? '';

$officerId   = $officer['id'] ?? null;
$officerName = $officer['name'] ?? '';
$officerDept = $officer['department'] ?? '';

$menuItems = fetchAll("SELECT * FROM menu_items WHERE is_available=1 ORDER BY category, item_name") ?? [];

$errors = [];

/* ================= FORM SUBMISSION ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $meetingName     = sanitize($_POST['meeting_name'] ?? '');
    $meetingDate     = sanitize($_POST['meeting_date'] ?? '');
    $meetingTime     = sanitize($_POST['meeting_time'] ?? '');
    $area            = sanitize($_POST['area'] ?? '');
    $serviceDate     = sanitize($_POST['service_date'] ?? '');
    $serviceTime     = sanitize($_POST['service_time'] ?? '');
    $serviceLocation = sanitize($_POST['service_location'] ?? '');
    $hallCode        = sanitize($_POST['hall_code'] ?? '');

    $items       = $_POST['items'] ?? [];
    $quantities  = $_POST['quantities'] ?? [];

    if (empty($meetingName)) $errors[] = "Meeting name required";
    if (empty($meetingDate)) $errors[] = "Meeting date required";
    if (empty($items)) $errors[] = "Select at least one item";

    if (empty($errors)) {

        $conn = getConnection();
        $conn->begin_transaction();

        try {

            if (!$officerId) {
                throw new Exception("No approving officer available.");
            }

            $requestNumber = generateRequestNumber();
            $totalAmount = 0;

            foreach ($items as $idx => $itemId) {
                $item = fetchOne("SELECT price FROM menu_items WHERE id=?", [$itemId], "i");
                if ($item) {
                    $qty = max(1, (int)($quantities[$idx] ?? 1));
                    $totalAmount += $item['price'] * $qty;
                }
            }

            $requestId = insertAndGetId(
                "INSERT INTO catering_requests 
                (request_number, employee_id, approving_officer_id,
                event_name, event_date, event_time, venue,
                purpose, special_instructions, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [
                    $requestNumber,
                    $userId,
                    $officerId,
                    $meetingName,
                    $meetingDate,
                    $meetingTime,
                    $serviceLocation,
                    $area,
                    $hallCode,
                    $totalAmount
                ],
                "siisssssds"
            );

            foreach ($items as $idx => $itemId) {

                $item = fetchOne("SELECT price FROM menu_items WHERE id=?", [$itemId], "i");

                if ($item) {
                    $qty = max(1, (int)($quantities[$idx] ?? 1));
                    $subtotal = $item['price'] * $qty;

                    executeQuery(
                        "INSERT INTO request_items 
                        (request_id, item_id, quantity, unit_price, subtotal)
                        VALUES (?, ?, ?, ?, ?)",
                        [$requestId, $itemId, $qty, $item['price'], $subtotal],
                        "iiidd"
                    );
                }
            }

            $conn->commit();
            redirect('dashboard.php', "Request {$requestNumber} submitted!", 'success');

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page">
<div class="card">
<div class="card-header">
<h3>NRSC Meeting & Catering Request</h3>
</div>

<div class="card-body">

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
<ul>
<?php foreach ($errors as $err): ?>
<li><?= htmlspecialchars($err) ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<form method="POST">

<!-- ================= MEETING DETAILS ================= -->

<h4>🧾 Meeting Details</h4>

<div class="form-row two-cols">

<div>
<label>Meeting Ref ID</label>
<input type="text" value="Auto Generated" disabled>

<label>Date of Request</label>
<input type="text" value="<?= date('d-m-Y') ?>" disabled>

<label>Requesting Person</label>
<input type="text" value="<?= htmlspecialchars($userName) ?>" disabled>

<label>Requesting Department</label>
<input type="text" value="<?= htmlspecialchars($userDept) ?>" disabled>

<label>Approving By</label>
<input type="text" value="<?= htmlspecialchars($officerName) ?>" disabled>

<label>Approving Designation</label>
<input type="text" value="Approving Officer" disabled>

<label>LIC</label>
<input type="text" name="lic">
</div>

<div>
<label>Area</label>
<input type="text" name="area">

<label>Date of Meeting</label>
<input type="date" name="meeting_date">

<label>Requesting Designation</label>
<input type="text" value="<?= htmlspecialchars($userRole) ?>" disabled>

<label>Phone Number</label>
<input type="text" value="<?= htmlspecialchars($userPhone) ?>" disabled>

<label>Approving Department</label>
<input type="text" value="<?= htmlspecialchars($officerDept) ?>" disabled>

<label>Meeting Time</label>
<input type="time" name="meeting_time">

<label>Meeting Name</label>
<input type="text" name="meeting_name">
</div>

</div>

<hr>

<!-- ================= SERVICE INFO ================= -->

<h4>🍽 Service Information</h4>

<div class="form-row">
<input type="date" name="service_date">
<input type="time" name="service_time">
<input type="text" name="service_location" placeholder="Service Location">
<input type="text" name="hall_code" placeholder="Hall Code">
</div>

<hr>

<!-- ================= ITEM SELECTION ================= -->

<h4>Item Selection</h4>

<div>
<select id="menu-selector">
<option value="">Select Item</option>
<?php foreach ($menuItems as $item): ?>
<option value="<?= $item['id'] ?>"
data-name="<?= htmlspecialchars($item['item_name'] ?? '') ?>"
data-price="<?= $item['price'] ?>">
<?= htmlspecialchars($item['item_name'] ?? '') ?> - ₹<?= $item['price'] ?>
</option>
<?php endforeach; ?>
</select>

    <input type="number" id="qty" value="1" min="1" class="form-control" style="width: 80px; display: inline-block;">
    <button type="button" onclick="addItem()" class="btn btn-success">Add</button>
</div>

<table class="table" id="items-table">
<thead>
<tr>
<th>Service Date</th>
<th>Service Time</th>
<th>Location</th>
<th>Hall Code</th>
<th>Description</th>
<th>Qty</th>
<th>Action</th>
</tr>
</thead>
<tbody></tbody>
</table>

<hr>

<!-- ================= ACTION BUTTONS ================= -->

<div class="action-bar">
    <button type="reset" class="btn btn-neutral">New</button>
    <button type="submit" class="btn btn-primary">Register</button>
    <button type="button" class="btn btn-neutral" onclick="window.print()">Print</button>
    <button type="button" class="btn btn-secondary">Save</button>
    <button type="button" class="btn btn-secondary">Update</button>
    <button type="button" class="btn btn-danger">Delete</button>
    <button type="button" class="btn btn-neutral">Search</button>
    <button type="reset" class="btn btn-neutral">Clear</button>
</div>

</form>
</div>
</div>
</div>

<script>
function addItem() {

const select = document.getElementById('menu-selector');
const option = select.options[select.selectedIndex];
if (!option.value) return;

const qty = document.getElementById('qty').value;

const table = document.querySelector('#items-table tbody');
const row = table.insertRow();

row.innerHTML = `
<td><input type="hidden" name="items[]" value="${option.value}">
${document.querySelector('[name=service_date]').value}</td>
<td>${document.querySelector('[name=service_time]').value}</td>
<td>${document.querySelector('[name=service_location]').value}</td>
<td>${document.querySelector('[name=hall_code]').value}</td>
<td>${option.dataset.name}</td>
<td><input type="hidden" name="quantities[]" value="${qty}">${qty}</td>
<td><button type="button" onclick="this.closest('tr').remove()">Delete</button></td>
`;

select.selectedIndex = 0;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>