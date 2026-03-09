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
        return false;
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
