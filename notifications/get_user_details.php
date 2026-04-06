<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// 1. Get user_code from GET
$userCode = $_GET['code'] ?? null;

// 2. Validate it (must not be empty)
if (empty($userCode)) {
    echo "Invalid User ID";
    exit;
}

// 3. Fetch user data linked with VIMIS_EMPLOYEE for master employee info
$sql = "SELECT u.*, v.DESGFULLNAME, v.DIVNFULLNAME, v.EMPLOYEENAME 
        FROM users u 
        LEFT JOIN VIMIS_EMPLOYEE v ON u.userid = v.EMPLOYEECODE 
        WHERE u.userid = ?";
$user = fetchOne($sql, [$userCode], "s");

// 4. If NOT found:
if (!$user) {
    echo "Invalid User ID";
    exit;
}

// 5. Fetch reporting officer info
$offQuery = "SELECT v2.EMPLOYEENAME AS officer_name
             FROM TBAD_EMPVSREPEMPPLOYEE t
             JOIN VIMIS_EMPLOYEE v2 ON t.REPEMPLOYEECODE = v2.EMPLOYEECODE
             WHERE t.EMPLOYEECODE = ?";
$officer = fetchOne($offQuery, [$userCode], "s");
$officerName = $officer['officer_name'] ?? 'Not Assigned';

// 6. If found: Display details
$displayName = !empty($user['EMPLOYEENAME']) ? $user['EMPLOYEENAME'] : ($user['name'] ?? '');
$displayDesig = !empty($user['DESGFULLNAME']) ? $user['DESGFULLNAME'] : ($user['designation'] ?? 'N/A');
$displayDept = !empty($user['DIVNFULLNAME']) ? $user['DIVNFULLNAME'] : ($user['department'] ?? 'N/A');
?>
<div class="user-details-view" style="font-family: inherit;">
    <h3>User Details</h3>
    
    <div>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($displayName); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
        <p><strong>Designation:</strong> <?php echo htmlspecialchars($displayDesig); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($displayDept); ?></p>
        <p><strong>Reporting Officer:</strong> <?php echo htmlspecialchars($officerName); ?></p>
    </div>

    <!-- 6. Add form with approve/reject buttons -->
    <form method="POST" action="../admin/approve_user.php">
        <input type="hidden" name="user_code" value="<?php echo htmlspecialchars($user['userid']); ?>">
        
        <button type="submit" name="action" value="approve">
            <i class="fa-solid fa-check mr-2"></i> Approve
        </button>
        
        <button type="submit" name="action" value="reject">
             Reject
        </button>
    </form>
</div>
