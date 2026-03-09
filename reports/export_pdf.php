<?php
/**
 * Universal Export PDF endpoint
 */
require_once __DIR__ . '/../includes/auth.php'; // Asserts basic login
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/report_generator.php';

if (!isset($_SESSION['user_id'])) {
    die("Access Denied: Please log in.");
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$type = $_GET['type'] ?? '';
$requestId = (int)($_GET['request_id'] ?? 0);

if (empty($type) && empty($requestId)) {
    die("Error: No report type or request specified.");
}

$dateNow = date('d M Y, h:i A');
$generatedBy = ROLE_LABELS[$userRole] ?? ucfirst($userRole);

$isSingle = false;
if ($requestId > 0) {
    $isSingle = true;
    $reportData = getSingleRequestData($requestId, $userId, $userRole);
    if ($reportData === false) {
        die("Access Denied: You do not have permission to view this request.");
    }
    $req = $reportData['request'];
    $items = $reportData['items'];
    $reportTitle = 'Catering Request Details';
} else {
    // Fetch generated data based on role constraints
    $reportData = getReportData($type, $userId, $userRole);
    if ($reportData === false) {
        die("Access Denied: You do not have permission to view this report.");
    }
    $requests = $reportData['requests'];
    $totalRequests = $reportData['total_requests'];
    $totalRevenue = $reportData['total_revenue'];
    $reportTitle = $reportData['title'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($reportTitle); ?> - <?php echo date('Ymd'); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 40px;
            background-color: #f4f6fb;
        }
        .report-container {
            max-width: 1100px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
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
        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 15px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        table th {
            background: #f4f6fb;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            color: #1a56db;
            text-transform: uppercase;
            font-size: 13px;
            border: 1px solid #ccc;
        }
        table td {
            padding: 10px;
            border: 1px solid #ccc;
            border-bottom: 1px solid #eaeaea;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .summary-box {
            margin-top: 30px;
            width: 300px;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #3a6ff7;
            float: right;
            background-color: #f9fbff;
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
        .report-actions {
            text-align: center;
        }
        @media print {
            body { 
                padding: 0; 
                background-color: white;
            }
            .report-actions { 
                display: none; 
            }
            .report-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                max-width: 100%;
            }
            .summary-box { 
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
    <div class="report-actions">
        <button onclick="window.print()" class="no-print-btn">🖨️ Print / Save as PDF</button>
        <button onclick="window.close()" class="no-print-btn" style="background-color: #6b7280; box-shadow: none;">❌ Close</button>
    </div>
    
    <div class="report-container">

    <div class="header">
        <h1>NRSC Catering Management System</h1>
        <h2>National Remote Sensing Centre</h2>
    </div>

    <div class="report-meta">
        <div>
            <strong>Report Title:</strong> <?php echo htmlspecialchars($reportTitle); ?><br>
            <strong>Role Context:</strong> <?php echo $generatedBy; ?>
        </div>
        <div>
            <strong>Generated Date:</strong> <?php echo $dateNow; ?><br>
            <strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
        </div>
    </div>
    
    <?php if ($isSingle): ?>
    
    <div style="margin-bottom: 30px; border: 1px solid #ccc; padding: 20px; border-radius: 8px; background-color: #f9fbff;">
        <h3 style="margin-top: 0; color: #1a56db; border-bottom: 2px solid #ddd; padding-bottom: 10px;">Request Information</h3>
        <table style="border: none; margin-bottom: 0;">
            <tr style="background: transparent;">
                <td style="border: none; padding: 6px 12px;"><strong>Request Number:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo htmlspecialchars($req['request_number']); ?></td>
                <td style="border: none; padding: 6px 12px;"><strong>Meeting Name:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo htmlspecialchars($req['meeting_name']); ?></td>
            </tr>
            <tr style="background: transparent;">
                <td style="border: none; padding: 6px 12px;"><strong>Requesting Person:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo htmlspecialchars($req['requestor_name'] ?? $req['requesting_person']); ?></td>
                <td style="border: none; padding: 6px 12px;"><strong>Department:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo htmlspecialchars($req['department'] ?? 'N/A'); ?></td>
            </tr>
            <tr style="background: transparent;">
                <td style="border: none; padding: 6px 12px;"><strong>Service Date:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo formatDate($req['service_date']); ?></td>
                <td style="border: none; padding: 6px 12px;"><strong>Service Time:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo !empty($req['service_time']) ? date('h:i A', strtotime($req['service_time'])) : 'N/A'; ?></td>
            </tr>
            <tr style="background: transparent;">
                <td style="border: none; padding: 6px 12px;"><strong>Service Location:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo htmlspecialchars($req['service_location'] ?? $req['area']); ?></td>
                <td style="border: none; padding: 6px 12px;"><strong>Hall Code:</strong></td>
                <td style="border: none; padding: 6px 12px;"><?php echo htmlspecialchars($req['hall_code'] ?? 'N/A'); ?></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 30px;">No items found for this request.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatCurrency($item['unit_price']); ?></td>
                        <td><?php echo formatCurrency($item['subtotal'] ?? ($item['unit_price'] * $item['quantity'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="clearfix">
        <div class="summary-box">
            <div class="summary-row">
                <span>Total Items:</span>
                <span><?php $t = 0; foreach($items as $i) $t += $i['quantity']; echo $t; ?></span>
            </div>
            <div class="summary-row">
                <span>Total Amount:</span>
                <span><?php echo formatCurrency($req['total_amount']); ?></span>
            </div>
        </div>
    </div>
    
    <?php else: ?>

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
                    <td colspan="6" style="text-align: center; padding: 30px;">No requests found for this report.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($requests as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['request_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['meeting_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                        <td><?php echo formatDate($row['service_date']); ?></td>
                        <td><?php echo formatCurrency($row['total_amount']); ?></td>
                        <td class="status-badge"><?php echo htmlspecialchars($row['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="clearfix">
        <div class="summary-box">
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
    
    <?php endif; ?>
    </div>
</body>
</html>
