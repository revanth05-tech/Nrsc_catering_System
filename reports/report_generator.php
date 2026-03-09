<?php
/**
 * Report Generator Logic (Universal)
 */

function getReportData($type, $userId, $userRole) {
    $data = [
        'title' => 'Catering Report',
        'requests' => [],
        'total_requests' => 0,
        'total_revenue' => 0,
    ];

    $query = "";
    $params = [];
    $types = "";

    // Extract requested role boundary
    $typeParts = explode('_', $type);
    $typePrefix = $typeParts[0];

    // Security check: Only allow users to generate reports meant for their role (Admin has full access)
    if ($userRole !== 'admin' && $typePrefix !== $userRole) {
        if (in_array($userRole, ['officer', 'canteen']) && in_array($type, ['approved_orders', 'completed_orders'])) {
            // Allow generic cross-dashboard reports
        } else {
            return false;
        }
    }

    switch ($type) {
        // ADMIN REPORTS
        case 'admin_all':
            $data['title'] = 'Comprehensive System Report';
            $query = "SELECT cr.request_number, cr.meeting_name, u.department, cr.service_date, cr.total_amount, cr.status 
                      FROM catering_requests cr LEFT JOIN users u ON cr.employee_id = u.id ORDER BY cr.created_at DESC";
            break;

        // EMPLOYEE REPORTS
        case 'employee_requests':
            $data['title'] = 'My Requests Summary Report';
            $query = "SELECT cr.request_number, cr.meeting_name, u.department, cr.service_date, cr.total_amount, cr.status 
                      FROM catering_requests cr LEFT JOIN users u ON cr.employee_id = u.id 
                      WHERE cr.employee_id = ? ORDER BY cr.created_at DESC";
            $params = [$userId];
            $types = "i";
            break;

        // OFFICER REPORTS
        case 'officer_pending':
            $data['title'] = 'Pending Approval Requests Report';
            $query = "SELECT cr.request_number, cr.meeting_name, u.department, cr.service_date, cr.total_amount, cr.status 
                      FROM catering_requests cr LEFT JOIN users u ON cr.employee_id = u.id 
                      WHERE cr.approving_officer_id = ? AND cr.status = 'pending' ORDER BY cr.created_at ASC";
            $params = [$userId];
            $types = "i";
            break;
            
        case 'officer_approved':
            $data['title'] = 'Approved Requests Report';
            $query = "SELECT cr.request_number, cr.meeting_name, u.department, cr.service_date, cr.total_amount, cr.status 
                      FROM catering_requests cr LEFT JOIN users u ON cr.employee_id = u.id 
                      WHERE cr.approving_officer_id = ? AND cr.status != 'pending' ORDER BY cr.created_at DESC";
            $params = [$userId];
            $types = "i";
            break;
            
        case 'approved_orders':
            $data['title'] = 'Approved Orders Report';
            $query = "SELECT r.request_number, u.name AS employee_name, r.meeting_name, r.meeting_date, r.service_location, r.total_amount, r.status, r.approved_at 
                      FROM catering_requests r JOIN users u ON r.employee_id = u.id 
                      WHERE r.status = 'approved' ORDER BY r.approved_at DESC";
            break;

        case 'completed_orders':
            $data['title'] = 'Completed Orders Report';
            $query = "SELECT r.request_number, u.name AS employee_name, r.meeting_name, r.meeting_date, r.service_location, r.total_amount, r.status, r.updated_at 
                      FROM catering_requests r JOIN users u ON r.employee_id = u.id 
                      WHERE r.status = 'completed' ORDER BY r.updated_at DESC";
            break;

        // CANTEEN REPORTS
        case 'canteen_active':
            $data['title'] = 'Active Kitchen Preparation Orders';
            $query = "SELECT cr.request_number, cr.meeting_name, u.department, cr.service_date, cr.total_amount, cr.status 
                      FROM catering_requests cr LEFT JOIN users u ON cr.employee_id = u.id 
                      WHERE cr.status IN ('approved', 'in_progress') ORDER BY cr.service_date ASC, cr.service_time ASC";
            break;
            
        case 'canteen_completed':
            $data['title'] = 'Completed Catering Orders';
            $query = "SELECT cr.request_number, cr.meeting_name, u.department, cr.service_date, cr.total_amount, cr.status 
                      FROM catering_requests cr LEFT JOIN users u ON cr.employee_id = u.id 
                      WHERE cr.status = 'completed' ORDER BY cr.service_date DESC, cr.service_time DESC";
            break;

        default:
            return false;
    }

    if (!empty($query)) {
        if (!empty($params)) {
            $requests = fetchAll($query, $params, $types);
        } else {
            $requests = fetchAll($query);
        }
        
        $data['requests'] = $requests;
        $data['total_requests'] = count($requests);
        
        foreach ($requests as $r) {
            if (in_array($r['status'], ['completed', 'approved', 'in_progress'])) {
                $data['total_revenue'] += (float)$r['total_amount'];
            }
        }
    }

    return $data;
}

function getSingleRequestData($requestId, $userId, $userRole) {
    $whereClause = "cr.id = ?";
    $params = [$requestId];
    $types = "i";

    // Security check logic per role
    if ($userRole === 'employee') {
        $whereClause .= " AND cr.employee_id = ?";
        $params[] = $userId;
        $types .= "i";
    } elseif ($userRole === 'officer') {
        $whereClause .= " AND cr.approving_officer_id = ?";
        $params[] = $userId;
        $types .= "i";
    } elseif ($userRole === 'canteen') {
        $whereClause .= " AND cr.status IN ('approved', 'in_progress', 'completed')";
    }

    // Admin has full access, no additional where clauses needed.

    global $conn;
    $request = fetchOne(
        "SELECT cr.*, u.name as requestor_name, u.department 
         FROM catering_requests cr 
         JOIN users u ON cr.employee_id = u.id 
         WHERE $whereClause",
        $params, $types
    );

    if (!$request) {
        return false;
    }

    $items = fetchAll(
        "SELECT ri.*, mi.item_name FROM request_items ri 
         JOIN menu_items mi ON ri.item_id = mi.id 
         WHERE ri.request_id = ?",
        [$requestId], "i"
    );

    return [
        'request' => $request,
        'items' => $items
    ];
}
