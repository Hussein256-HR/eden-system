<?php
/**
 * Utility Functions for Eden Miracle Church Management System
 */

require_once 'config/database.php';

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Check user role
 */
function has_role($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? 'viewer';
    
    $roles = ['viewer' => 1, 'staff' => 2, 'admin' => 3];
    
    return $roles[$user_role] >= $roles[$required_role];
}

/**
 * Require specific role
 */
function require_role($required_role) {
    if (!has_role($required_role)) {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log activity
 */
function log_activity($activity_type, $description, $user_id = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        $query = "INSERT INTO activity_logs (activity_type, description, user_id) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$activity_type, $description, $user_id]);
        
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Upload file
 */
function upload_file($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = MAX_FILE_SIZE) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large.'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type.'];
    }
    
    $upload_dir = UPLOAD_PATH;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

/**
 * Format date for display
 */
function format_date($date, $format = 'M d, Y') {
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function format_datetime($datetime, $format = 'M d, Y g:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($datetime));
}

/**
 * Generate pagination
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    $pagination = '';
    
    if ($total_pages <= 1) {
        return $pagination;
    }
    
    $pagination .= '<nav aria-label="Page navigation">';
    $pagination .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
    
    return $pagination;
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Get dashboard statistics
 */
function get_dashboard_stats() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stats = [];
        
        // Total members from generations table
        $query = "SELECT COUNT(*) as count FROM generations";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_members'] = $stmt->fetch()['count'];
        
        // Total staff (users with staff or admin role)
        $query = "SELECT COUNT(*) as count FROM users WHERE role IN ('staff', 'admin') AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_staff'] = $stmt->fetch()['count'];
        
        // Total ministers
        $query = "SELECT COUNT(*) as count FROM ministers";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_ministers'] = $stmt->fetch()['count'];
        
        // Total departments (count unique departments from department_members)
        $query = "SELECT COUNT(DISTINCT department) as count FROM department_members";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_departments'] = $stmt->fetch()['count'] + 4; // Add fixed departments (children, choir, ushering, security)
        
        // Total children
        $query = "SELECT COUNT(*) as count FROM children";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_children'] = $stmt->fetch()['count'];
        
        // Total generations (unique generations from generations table)
        $query = "SELECT COUNT(DISTINCT generation) as count FROM generations WHERE generation IS NOT NULL AND generation != ''";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_generations'] = $stmt->fetch()['count'];
        
        // Weekly reports (service reports from this week)
        $query = "SELECT COUNT(*) as count FROM service_reports WHERE WEEK(date) = WEEK(NOW()) AND YEAR(date) = YEAR(NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['weekly_reports'] = $stmt->fetch()['count'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return [
            'total_members' => 0,
            'total_staff' => 0,
            'total_ministers' => 0,
            'total_departments' => 0,
            'total_children' => 0,
            'total_generations' => 0,
            'weekly_reports' => 0
        ];
    }
}

/**
 * Get recent activities
 */
function get_recent_activities($limit = 10) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT al.*, u.full_name as user_name 
                  FROM activity_logs al 
                  LEFT JOIN users u ON al.user_id = u.id 
                  ORDER BY al.created_at DESC 
                  LIMIT ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting recent activities: " . $e->getMessage());
        return [];
    }
}
?>