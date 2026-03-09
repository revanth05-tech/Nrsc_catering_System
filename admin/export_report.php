<?php
/**
 * Export Report directly to Print / PDF
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'all';

// Build the query based on reportType
$query = "SELECT cr.request_number, cr.meeting_name, u.department, 
                 cr.service_date, cr.service_time, cr.total_amount, cr.status 
          FROM catering_requests cr
          LEFT JOIN users u ON cr.employee_id = u.id
          WHERE DATE(cr.created_at) BETWEEN ? AND ?";

$params = [$startDate, $endDate];
$types = "ss";

if ($reportType === 'approved') {
    $query .= " AND cr.status = 'approved'";
} elseif ($reportType === 'completed') {
    $query .= " AND cr.status = 'completed'";
} elseif ($reportType === 'revenue') {
    $query .= " AND cr.status IN ('approved', 'completed') AND cr.total_amount > 0";
}

$query .= " ORDER BY cr.service_date DESC, cr.service_time DESC";

$requests = fetchAll($query, $params, $types);

$totalRequests = count($requests);
$totalRevenue = 0;

foreach ($requests as $r) {
    if (in_array($r['status'], ['completed', 'approved'])) {
        $totalRevenue += (float)$r['total_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Catering Requests Report - <?php echo date('Ymd'); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #1a56db;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1a56db;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            margin: 8px 0 0 0;
            font-size: 20px;
            color: #555;
            font-weight: normal;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 15px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            font-size: 14px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f4f6f9;
            color: #1a56db;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .summary {
            width: 350px;
            float: right;
            border: 2px solid #1a56db;
            padding: 20px;
            background-color: #f9fbff;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 18px;
            color: #333;
        }
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-weight: bold;
            color: #1a56db;
        }
        .status-badge {
            text-transform: uppercase;
            font-size: 12px;
            font-weight: bold;
            color: #666;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .summary { 
                border: 1px solid #000; 
                background-color: transparent; 
                color: #000;
            }
            .header h1, th, .summary-row:last-child {
                color: #000 !important;
            }
            .header {
                border-bottom: 2px solid #000;
            }
        }
        .no-print-btn {
            background-color: #1a56db;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 30px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .no-print-btn:hover {
            background-color: #1545b3;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body onload="setTimeout(function(){ window.print(); }, 500);">
    <div class="no-print" style="text-align: center;">
        <button onclick="window.print()" class="no-print-btn">🖨️ Print / Save as PDF</button>
        <button onclick="window.close()" class="no-print-btn" style="background-color: #6b7280; box-shadow: none;">❌ Close</button>
    </div>

    <div class="header">
        <h1>NRSC Catering Management System</h1>
        <h2>National Remote Sensing Centre</h2>
    </div>

    <div class="meta-info">
        <div>
            <strong>Report Title:</strong> Catering Requests Report<br>
            <strong>Report Type:</strong> <?php echo ucfirst($reportType); ?> Requests<br>
            <strong>Date Range:</strong> <?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?>
        </div>
        <div>
            <strong>Generated Date:</strong> <?php echo date('d M Y, h:i A'); ?><br>
            <strong>Generated By:</strong> Admin
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Request Number</th>
                <th>Meeting Name</th>
                <th>Department</th>
                <th>Service Date</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px;">No requests found for the selected criteria.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($requests as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['request_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['meeting_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                                echo formatDate($row['service_date']); 
                                if (!empty($row['service_time'])) echo '<br><small>' . date('h:i A', strtotime($row['service_time'])) . '</small>';
                            ?>
                        </td>
                        <td><?php echo formatCurrency($row['total_amount']); ?></td>
                        <td class="status-badge"><?php echo htmlspecialchars($row['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="clearfix">
        <div class="summary">
            <div class="summary-row">
                <span>Total Requests:</span>
                <span><?php echo $totalRequests; ?></span>
            </div>
            <div class="summary-row">
                <span>Total Revenue:</span>
                <span><?php echo formatCurrency($totalRevenue); ?></span>
            </div>
        </div>
    </div>
</body>
</html>
