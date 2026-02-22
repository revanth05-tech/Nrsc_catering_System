<?php
// sections/employee/form.php - New Request Fragment

// Get menu items
$menuItems = fetchAll("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, item_name");
$categories = CATEGORY_LABELS; // Assumed defined in config

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = sanitize($_POST['meeting_name'] ?? '');
    $eventDate = sanitize($_POST['meeting_date'] ?? '');
    $eventTime = sanitize($_POST['meeting_time'] ?? '');
    $area = sanitize($_POST['area'] ?? '');
    $guestCount = (int)($_POST['guest_count'] ?? 0);
    $purpose = sanitize($_POST['purpose'] ?? '');
    $instructions = sanitize($_POST['special_instructions'] ?? '');
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    $errors = [];
    
    if (empty($eventName)) $errors[] = 'Event name is required';
    if (empty($eventDate)) $errors[] = 'Event date is required';
    if (empty($eventTime)) $errors[] = 'Event time is required';
    if (empty($area)) $errors[] = 'Venue is required';
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
                "INSERT INTO catering_requests (request_number, employee_id, meeting_name, meeting_date, meeting_time, area, guest_count, purpose, special_instructions, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$requestNumber, $userId, $eventName, $eventDate, $eventTime, $area, $guestCount, $purpose, $instructions, $totalAmount],
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
            
            // Redirect to History Section
            // We use header directly or the helper function if it supports full URLs
            // Assuming redirect() function takes a URL
            redirect('index.php?section=history', 'Request ' . $requestNumber . ' created successfully!', 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to create request: ' . $e->getMessage();
        }
    }
}
?>

<div class="card" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="mb-0">Details & Menu Selection</h3>
    </div>
    
    <div class="card-body">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error mb-6">
                <div class="font-bold mb-2">Please correct the following errors:</div>
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Event Details Section -->
            <div class="mb-8">
                <h4 class="text-primary mb-4 text-sm font-bold uppercase tracking-wide opacity-80 border-b border-gray-700 pb-2">
                    Event Information
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="meeting_name">Event Name *</label>
                        <input type="text" id="meeting_name" name="meeting_name" required 
                               value="<?php echo htmlspecialchars($_POST['meeting_name'] ?? ''); ?>" 
                               placeholder="e.g. Project Review Meeting">
                    </div>
                    
                    <div class="form-group">
                        <label for="area">Venue *</label>
                        <input type="text" id="area" name="area" required 
                               value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>" 
                               placeholder="Building / Room No.">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="meeting_date">Date *</label>
                        <input type="date" id="meeting_date" name="meeting_date" required 
                               value="<?php echo $_POST['meeting_date'] ?? ''; ?>" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="meeting_time">Time *</label>
                        <input type="time" id="meeting_time" name="meeting_time" required 
                               value="<?php echo $_POST['meeting_time'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="guest_count">Guest Count *</label>
                        <input type="number" id="guest_count" name="guest_count" required 
                               value="<?php echo $_POST['guest_count'] ?? '10'; ?>" min="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose / Agenda</label>
                    <textarea id="purpose" name="purpose" rows="2" 
                              placeholder="Brief description..."><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Menu Section -->
            <div class="mb-8">
                <h4 class="text-primary mb-4 text-sm font-bold uppercase tracking-wide opacity-80 border-b border-gray-700 pb-2">
                    Catering Menu
                </h4>

                <div class="bg-gray-900 rounded-lg p-6 border border-gray-700">
                    <div class="form-group mb-6">
                        <label class="mb-2 block">Choose Items to Add</label>
                        <div class="d-flex gap-2">
                            <select id="menu-selector" class="flex-grow">
                                <option value="">-- Select Item --</option>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <optgroup label="<?php echo $catLabel; ?>">
                                        <?php foreach ($menuItems as $item): ?>
                                            <?php if ($item['category'] === $catKey): ?>
                                                <option value="<?php echo $item['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                        data-price="<?php echo $item['price']; ?>">
                                                    <?php echo htmlspecialchars($item['item_name']); ?> - ₹<?php echo $item['price']; ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-primary" onclick="addSelectedItem()">Add</button>
                        </div>
                    </div>

                    <!-- Selected Items Container -->
                    <div class="selected-items-container bg-gray-800 rounded border border-gray-700 p-4 mb-4">
                        <div class="selected-items" id="selected-items" style="min-height: 50px;">
                            <!-- Items appear here -->
                        </div>
                        
                        <?php if (empty($_POST['items'])): ?>
                            <div id="empty-cart-msg" class="text-center text-muted text-sm py-4">No items selected yet.</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-t border-gray-700 pt-4">
                        <div class="text-muted">Total Estimated Cost</div>
                        <div class="text-2xl font-bold text-white order-total-value">₹0.00</div>
                        <input type="hidden" name="total_amount" value="0">
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-8">
                <div class="form-group">
                    <label for="special_instructions">Special Instructions (Dietary, etc.)</label>
                    <textarea id="special_instructions" name="special_instructions" rows="2" 
                              placeholder="Any specific requirements..."><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-3 pt-4 border-t border-gray-700">
                <a href="?section=home" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">Submit Processing Request</button>
            </div>
        </form>
    </div>
</div>

<script>
// Local override for addSelectedItem if needed, but main.js should handle it.
// Enhanced version to hide empty message
const originalAdd = window.addSelectedItem || function(){};
window.addSelectedItem = function() {
    const msg = document.getElementById('empty-cart-msg');
    if(msg) msg.style.display = 'none';
    
    // Call original logic from main.js
    // We need to re-implement specific logic because main.js might not know about empty-cart-msg
    const select = document.getElementById('menu-selector');
    const option = select.options[select.selectedIndex];
    if (!option.value) return;
    
    addItemToOrder(option.value, option.dataset.name, parseFloat(option.dataset.price));
    select.selectedIndex = 0;
};
</script>
