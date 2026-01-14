<?php
/**
 * Manage Menu Items
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Menu Items';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $itemName = sanitize($_POST['item_name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        
        if (empty($itemName) || empty($category) || $price <= 0) {
            $error = 'Item name, category, and price are required.';
        } else {
            $result = insertAndGetId(
                "INSERT INTO menu_items (item_name, category, price, description) VALUES (?, ?, ?, ?)",
                [$itemName, $category, $price, $description],
                "ssds"
            );
            if ($result) {
                $success = 'Item added successfully!';
            } else {
                $error = 'Failed to add item.';
            }
        }
    } elseif ($action === 'toggle') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $available = (int)($_POST['is_available'] ?? 0);
        executeAndGetAffected("UPDATE menu_items SET is_available = ? WHERE id = ?", [$available, $itemId], "ii");
        $success = 'Item availability updated.';
    } elseif ($action === 'delete') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        executeAndGetAffected("DELETE FROM menu_items WHERE id = ?", [$itemId], "i");
        $success = 'Item deleted.';
    } elseif ($action === 'update_price') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $newPrice = (float)($_POST['new_price'] ?? 0);
        if ($newPrice > 0) {
            executeAndGetAffected("UPDATE menu_items SET price = ? WHERE id = ?", [$newPrice, $itemId], "di");
            $success = 'Price updated.';
        }
    }
}

// Get items grouped by category
$items = fetchAll("SELECT * FROM menu_items ORDER BY category, item_name");

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
    <button onclick="document.getElementById('add-item-modal').style.display='block'" class="btn btn-primary">
        Add Menu Item
    </button>
</div>

<?php foreach (CATEGORY_LABELS as $catKey => $catLabel): ?>
<div class="card mb-6">
    <div class="card-header">
        <h3><?php echo $catLabel; ?></h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $categoryItems = array_filter($items, fn($i) => $i['category'] === $catKey);
                    if (empty($categoryItems)): 
                    ?>
                    <tr><td colspan="5" class="text-center text-muted">No items in this category</td></tr>
                    <?php else: ?>
                    <?php foreach ($categoryItems as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                        <td class="text-muted"><?php echo htmlspecialchars($item['description'] ?? '-'); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_price">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="new_price" value="<?php echo $item['price']; ?>" 
                                       style="width:80px;padding:4px 8px;" step="0.01" min="0">
                                <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="is_available" value="<?php echo $item['is_available'] ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $item['is_available'] ? 'btn-success' : 'btn-secondary'; ?>">
                                    <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this item?">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Item Modal -->
<div id="add-item-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;padding:20px;">
    <div style="max-width:500px;margin:50px auto;background:white;border-radius:var(--radius-xl);overflow:hidden;">
        <div class="card-header flex-between">
            <h3 style="margin:0;">Add Menu Item</h3>
            <button onclick="document.getElementById('add-item-modal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" name="item_name" required>
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" required>
                        <?php foreach (CATEGORY_LABELS as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Price (₹) *</label>
                    <input type="number" name="price" required min="1" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2"></textarea>
                </div>
                
                <div class="flex-between">
                    <button type="button" onclick="document.getElementById('add-item-modal').style.display='none'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
