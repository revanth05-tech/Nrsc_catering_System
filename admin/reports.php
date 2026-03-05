<?php
/**
 * Reports
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Reports';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Date range filter
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

// Summary stats
$summary = fetchOne(
    "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
        SUM(guest_count) as total_guests
     FROM catering_requests 
     WHERE DATE(created_at) BETWEEN ? AND ?",
    [$startDate, $endDate], "ss"
);

// Top items
$topItems = fetchAll(
    "SELECT mi.item_name, mi.category, SUM(ri.quantity) as total_qty, SUM(ri.subtotal) as total_value
     FROM request_items ri
     JOIN menu_items mi ON ri.item_id = mi.id
     JOIN catering_requests cr ON ri.request_id = cr.id
     WHERE DATE(cr.created_at) BETWEEN ? AND ? AND cr.status IN ('approved', 'completed', 'in_progress')
     GROUP BY ri.item_id
     ORDER BY total_qty DESC
     LIMIT 10",
    [$startDate, $endDate], "ss"
);

// Requests by department
$byDept = fetchAll(
    "SELECT u.department, COUNT(*) as count, SUM(cr.total_amount) as total
     FROM catering_requests cr
     JOIN users u ON cr.employee_id = u.id
     WHERE DATE(cr.created_at) BETWEEN ? AND ?
     GROUP BY u.department
     ORDER BY count DESC",
    [$startDate, $endDate], "ss"
);

// Monthly trend
$monthlyData = fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
            COUNT(*) as requests, 
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as revenue
     FROM catering_requests 
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month"
);

include __DIR__ . '/../includes/header.php';
?>

<form method="GET" class="flex-between mb-6" style="background:white;padding:20px;border-radius:var(--radius-lg);">
    <div class="d-flex gap-4" style="align-items:flex-end;">
        <div class="form-group mb-0">
            <label>Start Date</label>
            <input type="date" name="start" value="<?php echo $startDate; ?>">
        </div>
        <div class="form-group mb-0">
            <label>End Date</label>
            <input type="date" name="end" value="<?php echo $endDate; ?>">
        </div>
        <button type="submit" class="btn btn-primary">Apply Filter</button>
    </div>
</form>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value"><?php echo $summary['total_requests'] ?? 0; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?php echo $summary['completed'] ?? 0; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?php echo formatCurrency($summary['total_revenue'] ?? 0); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Guests Served</div>
            <div class="stat-value"><?php echo number_format($summary['total_guests'] ?? 0); ?></div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Top Menu Items</h3>
        </div>
        <div class="card-body">
            <?php if (empty($topItems)): ?>
                <p class="text-muted text-center">No data available</p>
            <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Qty Ordered</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topItems as $i => $item): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><?php echo CATEGORY_LABELS[$item['category']] ?? $item['category']; ?></td>
                            <td><?php echo $item['total_qty']; ?></td>
                            <td><?php echo formatCurrency($item['total_value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Requests by Department</h3>
        </div>
        <div class="card-body">
            <?php if (empty($byDept)): ?>
                <p class="text-muted text-center">No data available</p>
            <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Requests</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byDept as $dept): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department'] ?? 'Unknown'); ?></strong></td>
                            <td><?php echo $dept['count']; ?></td>
                            <td><?php echo formatCurrency($dept['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h3>Monthly Trend (Last 6 Months)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($monthlyData)): ?>
            <p class="text-muted text-center">No data available</p>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Requests</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyData as $month): ?>
                    <tr>
                        <td><strong><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></strong></td>
                        <td><?php echo $month['requests']; ?></td>
                        <td><?php echo formatCurrency($month['revenue']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
