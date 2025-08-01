<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
require_login();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get all service reports with pagination
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $date_filter = $_GET['date'] ?? '';
                $offset = ($page - 1) * $limit;
                
                $where_clause = "WHERE 1=1";
                $params = [];
                
                if (!empty($search)) {
                    $where_clause .= " AND (preacher_name LIKE ? OR coordinator_name LIKE ? OR service_type LIKE ?)";
                    $search_param = "%$search%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param]);
                }
                
                if (!empty($date_filter)) {
                    $where_clause .= " AND DATE(date) = ?";
                    $params[] = $date_filter;
                }
                
                // Get total count
                $count_query = "SELECT COUNT(*) as total FROM service_reports $where_clause";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute($params);
                $total = $count_stmt->fetch()['total'];
                
                // Get service reports
                $query = "SELECT id, date, service_type, preacher_name, coordinator_name, sessions, 
                                sermon_start, sermon_end, prayer_start, prayer_end, attendance, created_at 
                         FROM service_reports $where_clause 
                         ORDER BY date DESC, created_at DESC 
                         LIMIT ? OFFSET ?";
                $stmt = $db->prepare($query);
                $stmt->execute(array_merge($params, [$limit, $offset]));
                $reports = $stmt->fetchAll();
                
                // Decode JSON sessions for each report
                foreach ($reports as &$report) {
                    $report['sessions'] = json_decode($report['sessions'] ?? '[]', true);
                }
                
                json_response([
                    'success' => true,
                    'data' => $reports,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single service report
                $id = (int)$_GET['id'];
                $query = "SELECT * FROM service_reports WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                
                if ($report = $stmt->fetch()) {
                    // Decode JSON fields
                    $report['sessions'] = json_decode($report['sessions'] ?? '[]', true);
                    $report['pastors'] = explode("\n", $report['pastors'] ?? '');
                    
                    json_response(['success' => true, 'data' => $report]);
                } else {
                    json_response(['success' => false, 'message' => 'Report not found'], 404);
                }
                
            } elseif ($action === 'stats') {
                // Get service reports statistics
                $stats = [];
                
                // Total reports
                $query = "SELECT COUNT(*) as total FROM service_reports";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['total_reports'] = $stmt->fetch()['total'];
                
                // Reports this month
                $query = "SELECT COUNT(*) as count FROM service_reports WHERE MONTH(date) = MONTH(NOW()) AND YEAR(date) = YEAR(NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['this_month'] = $stmt->fetch()['count'];
                
                // Reports this week
                $query = "SELECT COUNT(*) as count FROM service_reports WHERE WEEK(date) = WEEK(NOW()) AND YEAR(date) = YEAR(NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['this_week'] = $stmt->fetch()['count'];
                
                // Average attendance
                $query = "SELECT AVG(attendance) as avg_attendance FROM service_reports WHERE attendance > 0";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['avg_attendance'] = round($stmt->fetch()['avg_attendance'] ?? 0);
                
                // Top preachers
                $query = "SELECT preacher_name, COUNT(*) as count FROM service_reports GROUP BY preacher_name ORDER BY count DESC LIMIT 5";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['top_preachers'] = $stmt->fetchAll();
                
                json_response(['success' => true, 'data' => $stats]);
                
            } else {
                json_response(['success' => false, 'message' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            // Add new service report
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Required fields validation
            $required_fields = ['date', 'service_type', 'preacher_name', 'coordinator_name'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
                }
            }
            
            // Validate sessions
            if (empty($input['sessions']) || !is_array($input['sessions'])) {
                json_response(['success' => false, 'message' => 'At least one session must be selected'], 400);
            }
            
            // Sanitize and prepare data
            $data = [
                'date' => sanitize_input($input['date']),
                'service_type' => sanitize_input($input['service_type']),
                'preacher_name' => sanitize_input($input['preacher_name']),
                'coordinator_name' => sanitize_input($input['coordinator_name']),
                'sessions' => json_encode($input['sessions']),
                'sermon_start' => !empty($input['sermon_start']) ? $input['sermon_start'] : null,
                'sermon_end' => !empty($input['sermon_end']) ? $input['sermon_end'] : null,
                'prayer_start' => !empty($input['prayer_start']) ? $input['prayer_start'] : null,
                'prayer_end' => !empty($input['prayer_end']) ? $input['prayer_end'] : null,
                'pastors' => sanitize_input($input['pastors'] ?? ''),
                'sermon_clarity' => (int)($input['sermon_clarity'] ?? 0),
                'time_management' => (int)($input['time_management'] ?? 0),
                'confidence' => (int)($input['confidence'] ?? 0),
                'engagement' => (int)($input['engagement'] ?? 0),
                'conduct' => (int)($input['conduct'] ?? 0),
                'relevance' => (int)($input['relevance'] ?? 0),
                'scripture_use' => (int)($input['scripture_use'] ?? 0),
                'testimonies' => (int)($input['testimonies'] ?? 0),
                'attendance' => (int)($input['attendance'] ?? 0),
                'notes' => sanitize_input($input['notes'] ?? '')
            ];
            
            // Build insert query
            $columns = array_keys($data);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            $query = "INSERT INTO service_reports (" . implode(',', $columns) . ") VALUES ($placeholders)";
            
            $stmt = $db->prepare($query);
            
            if ($stmt->execute(array_values($data))) {
                $report_id = $db->lastInsertId();
                
                // Log activity
                log_activity('service_report_created', "Service report created for " . $data['date'] . " by " . $data['coordinator_name']);
                
                json_response([
                    'success' => true, 
                    'message' => 'Service report created successfully',
                    'data' => ['id' => $report_id]
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to create service report'], 500);
            }
            break;
            
        case 'PUT':
            // Update service report
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid report ID'], 400);
            }
            
            // Build update query dynamically
            $allowed_fields = [
                'date', 'service_type', 'preacher_name', 'coordinator_name', 'sessions',
                'sermon_start', 'sermon_end', 'prayer_start', 'prayer_end', 'pastors',
                'sermon_clarity', 'time_management', 'confidence', 'engagement', 'conduct',
                'relevance', 'scripture_use', 'testimonies', 'attendance', 'notes'
            ];
            
            $update_fields = [];
            $update_values = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $update_fields[] = "$field = ?";
                    
                    if ($field === 'sessions') {
                        $update_values[] = json_encode($input[$field]);
                    } elseif (in_array($field, ['sermon_clarity', 'time_management', 'confidence', 'engagement', 'conduct', 'relevance', 'scripture_use', 'testimonies', 'attendance'])) {
                        $update_values[] = (int)$input[$field];
                    } else {
                        $update_values[] = sanitize_input($input[$field]);
                    }
                }
            }
            
            if (empty($update_fields)) {
                json_response(['success' => false, 'message' => 'No fields to update'], 400);
            }
            
            $update_values[] = $id;
            $query = "UPDATE service_reports SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($update_values)) {
                // Log activity
                log_activity('service_report_updated', "Service report updated: ID $id");
                
                json_response(['success' => true, 'message' => 'Service report updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to update service report'], 500);
            }
            break;
            
        case 'DELETE':
            // Delete service report
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid report ID'], 400);
            }
            
            // Get report info for logging
            $report_query = "SELECT date, preacher_name FROM service_reports WHERE id = ?";
            $report_stmt = $db->prepare($report_query);
            $report_stmt->execute([$id]);
            $report = $report_stmt->fetch();
            
            if (!$report) {
                json_response(['success' => false, 'message' => 'Report not found'], 404);
            }
            
            // Delete report
            $query = "DELETE FROM service_reports WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$id])) {
                // Log activity
                log_activity('service_report_deleted', "Service report deleted: " . $report['date'] . " - " . $report['preacher_name']);
                
                json_response(['success' => true, 'message' => 'Service report deleted successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to delete service report'], 500);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Service Reports API error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Internal server error'], 500);
}
?>