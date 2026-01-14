<?php
/**
 * Create New Catering Request
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$pageTitle = 'New Catering Request';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Get menu items
$menuItems = fetchAll("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, item_name");
$categories = CATEGORY_LABELS;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = sanitize($_POST['event_name'] ?? '');
    $eventDate = sanitize($_POST['event_date'] ?? '');
    $eventTime = sanitize($_POST['event_time'] ?? '');
    $venue = sanitize($_POST['venue'] ?? '');
    $guestCount = (int)($_POST['guest_count'] ?? 0);
    $purpose = sanitize($_POST['purpose'] ?? '');
    $instructions = sanitize($_POST['special_instructions'] ?? '');
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    $errors = [];
    
    if (empty($eventName)) $errors[] = 'Event name is required';
    if (empty($eventDate)) $errors[] = 'Event date is required';
    if (empty($eventTime)) $errors[] = 'Event time is required';
    if (empty($venue)) $errors[] = 'Venue is required';
    if ($guestCount < 1) $errors[] = 'Guest count must be at least 1';
    if (empty($items)) $errors[] = 'Please select at least one menu item';
    
    if (empty($errors)) {
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            $requestNumber = generateRequestNumber();
            $userId = $_SESSION['user_id'];
            
            // Calculate total
            $totalAmount = 0;
            foreach ($items as $idx => $itemId) {
                $item = fetchOne("SELECT price FROM menu_items WHERE id = ?", [$itemId], "i");
                if ($item) {
                    $qty = (int)($quantities[$idx] ?? 1);
                    $totalAmount += $item['price'] * $qty;
                }
            }
            
            // Insert request
            $requestId = insertAndGetId(
                "INSERT INTO catering_requests (request_number, employee_id, event_name, event_date, event_time, venue, guest_count, purpose, special_instructions, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$requestNumber, $userId, $eventName, $eventDate, $eventTime, $venue, $guestCount, $purpose, $instructions, $totalAmount],
                "sissssissd"
            );
            
            // Insert items
            foreach ($items as $idx => $itemId) {
                $item = fetchOne("SELECT price FROM menu_items WHERE id = ?", [$itemId], "i");
                if ($item) {
                    $qty = (int)($quantities[$idx] ?? 1);
                    $subtotal = $item['price'] * $qty;
                    executeQuery(
                        "INSERT INTO request_items (request_id, item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)",
                        [$requestId, $itemId, $qty, $item['price'], $subtotal],
                        "iiidd"
                    );
                }
            }
            
            $conn->commit();
            redirect('my_reqs.php', 'Request ' . $requestNumber . ' created successfully!', 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to create request. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3>Create New Catering Request</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin:0;padding-left:20px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-section">
                    <h4 class="form-section-title">Event Details</h4>
                    
                    <div class="form-group">
                        <label for="event_name">Event Name *</label>
                        <input type="text" id="event_name" name="event_name" required 
                               value="<?php echo $_POST['event_name'] ?? ''; ?>" placeholder="e.g., Team Meeting, Workshop">
                    </div>
                    
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label for="event_date">Event Date *</label>
                            <input type="date" id="event_date" name="event_date" required 
                                   value="<?php echo $_POST['event_date'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="event_time">Event Time *</label>
                            <input type="time" id="event_time" name="event_time" required 
                                   value="<?php echo $_POST['event_time'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label for="venue">Venue *</label>
                            <input type="text" id="venue" name="venue" required 
                                   value="<?php echo $_POST['venue'] ?? ''; ?>" placeholder="e.g., Conference Room A">
                        </div>
                        <div class="form-group">
                            <label for="guest_count">Number of Guests *</label>
                            <input type="number" id="guest_count" name="guest_count" required 
                                   value="<?php echo $_POST['guest_count'] ?? '10'; ?>" min="1" max="500">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="purpose">Purpose</label>
                        <textarea id="purpose" name="purpose" rows="2" placeholder="Brief description of the event"><?php echo $_POST['purpose'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4 class="form-section-title">Menu Selection</h4>
                    
                    <div class="item-selector">
                        <div class="form-group">
                            <label>Add Items to Order</label>
                            <select id="menu-selector" onchange="addSelectedItem()">
                                <option value="">-- Select Item --</option>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <optgroup label="<?php echo $catLabel; ?>">
                                        <?php foreach ($menuItems as $item): ?>
                                            <?php if ($item['category'] === $catKey): ?>
                                                <option value="<?php echo $item['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                        data-price="<?php echo $item['price']; ?>">
                                                    <?php echo htmlspecialchars($item['item_name']); ?> - <?php echo formatCurrency($item['price']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="selected-items" id="selected-items">
                            <!-- Items will be added here dynamically -->
                        </div>
                        
                        <div class="order-total">
                            <span class="order-total-label">Total Amount:</span>
                            <span class="order-total-value">₹0.00</span>
                            <input type="hidden" name="total_amount" value="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4 class="form-section-title">Additional Instructions</h4>
                    <div class="form-group">
                        <label for="special_instructions">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" rows="3" 
                                  placeholder="Any dietary requirements, allergies, or special arrangements..."><?php echo $_POST['special_instructions'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <div class="flex-between">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addSelectedItem() {
    const select = document.getElementById('menu-selector');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) return;
    
    const id = option.value;
    const name = option.dataset.name;
    const price = parseFloat(option.dataset.price);
    
    addItemToOrder(id, name, price);
    select.selectedIndex = 0;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
