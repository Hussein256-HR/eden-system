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
            if ($action === 'stats') {
                // Get comprehensive dashboard statistics
                $stats = get_dashboard_stats();
                
                // Add additional statistics
                
                // Department-specific counts
                $department_stats = [];
                
                // Children count
                $query = "SELECT COUNT(*) as count FROM children";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $department_stats['children'] = $stmt->fetch()['count'];
                
                // Choir count
                $query = "SELECT COUNT(*) as count FROM choir";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $department_stats['choir'] = $stmt->fetch()['count'];
                
                // Ushering count
                $query = "SELECT COUNT(*) as count FROM ushering";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $department_stats['ushering'] = $stmt->fetch()['count'];
                
                // Security count
                $query = "SELECT COUNT(*) as count FROM security";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $department_stats['security'] = $stmt->fetch()['count'];
                
                // Generic departments counts
                $generic_departments = [
                    'pastoral', 'administration', 'church-elders', 'worship-team', 'orange-strategy',
                    'intercession', 'media', 'sound', 'building', 'maintenance', 'washrooms',
                    'school-of-ministry', 'baptism-dedication-new people', 'finance', 'school-of-music',
                    'prayer-leaders', 'conference', 'leader-of-men/women', 'sports', 'transport',
                    'missions-and-evangelism', 'youth-ministry', 'divine-camping', 'singles',
                    'pre-maritial counselling', 'altars', 'pastors-welfare'
                ];
                
                foreach ($generic_departments as $dept) {
                    $query = "SELECT COUNT(*) as count FROM department_members WHERE department = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$dept]);
                    $department_stats[$dept] = $stmt->fetch()['count'];
                }
                
                $stats['departments'] = $department_stats;
                
                json_response(['success' => true, 'data' => $stats]);
                
            } elseif ($action === 'activities') {
                // Get recent activities
                $limit = (int)($_GET['limit'] ?? 10);
                $activities = get_recent_activities($limit);
                
                json_response(['success' => true, 'data' => $activities]);
                
            } elseif ($action === 'charts') {
                // Get data for dashboard charts
                $charts = [];
                
                // Members by generation chart
                $query = "SELECT generation, COUNT(*) as count 
                         FROM generations 
                         WHERE generation IS NOT NULL AND generation != '' 
                         GROUP BY generation 
                         ORDER BY count DESC 
                         LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $charts['members_by_generation'] = $stmt->fetchAll();
                
                // Members by gender
                $query = "SELECT gender, COUNT(*) as count FROM generations GROUP BY gender";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $charts['members_by_gender'] = $stmt->fetchAll();
                
                // Monthly registrations (last 12 months)
                $query = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as count
                         FROM generations 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $charts['monthly_registrations'] = $stmt->fetchAll();
                
                // Service reports by month (last 6 months)
                $query = "SELECT 
                            DATE_FORMAT(date, '%Y-%m') as month,
                            COUNT(*) as count,
                            AVG(attendance) as avg_attendance
                         FROM service_reports 
                         WHERE date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(date, '%Y-%m')
                         ORDER BY month";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $charts['service_reports_monthly'] = $stmt->fetchAll();
                
                // Department sizes
                $department_sizes = [];
                
                // Add specific departments
                $specific_depts = ['children', 'choir', 'ushering', 'security'];
                foreach ($specific_depts as $dept) {
                    $query = "SELECT COUNT(*) as count FROM $dept";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $department_sizes[] = [
                        'department' => ucfirst($dept),
                        'count' => $stmt->fetch()['count']
                    ];
                }
                
                // Add top 10 generic departments
                $query = "SELECT department, COUNT(*) as count 
                         FROM department_members 
                         GROUP BY department 
                         ORDER BY count DESC 
                         LIMIT 10";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $generic_dept_sizes = $stmt->fetchAll();
                
                foreach ($generic_dept_sizes as $dept) {
                    $department_sizes[] = [
                        'department' => ucwords(str_replace('-', ' ', $dept['department'])),
                        'count' => $dept['count']
                    ];
                }
                
                $charts['department_sizes'] = $department_sizes;
                
                json_response(['success' => true, 'data' => $charts]);
                
            } elseif ($action === 'summary') {
                // Get summary data for dashboard cards
                $summary = [];
                
                // Today's activities count
                $query = "SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $summary['today_activities'] = $stmt->fetch()['count'];
                
                // This week's new members
                $query = "SELECT COUNT(*) as count FROM generations WHERE WEEK(created_at) = WEEK(NOW()) AND YEAR(created_at) = YEAR(NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $summary['week_new_members'] = $stmt->fetch()['count'];
                
                // This month's service reports
                $query = "SELECT COUNT(*) as count FROM service_reports WHERE MONTH(date) = MONTH(NOW()) AND YEAR(date) = YEAR(NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $summary['month_service_reports'] = $stmt->fetch()['count'];
                
                // Active users (logged in within last 30 days)
                $query = "SELECT COUNT(DISTINCT user_id) as count FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $summary['active_users'] = $stmt->fetch()['count'];
                
                // Average service attendance (last 10 services)
                $query = "SELECT AVG(attendance) as avg_attendance FROM (SELECT attendance FROM service_reports WHERE attendance > 0 ORDER BY date DESC LIMIT 10) as recent_services";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $summary['avg_attendance'] = round($stmt->fetch()['avg_attendance'] ?? 0);
                
                json_response(['success' => true, 'data' => $summary]);
                
            } else {
                json_response(['success' => false, 'message' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            if ($action === 'log_activity') {
                // Log custom activity
                $input = json_decode(file_get_contents('php://input'), true);
                
                $activity_type = sanitize_input($input['type'] ?? '');
                $description = sanitize_input($input['description'] ?? '');
                
                if (empty($activity_type) || empty($description)) {
                    json_response(['success' => false, 'message' => 'Activity type and description are required'], 400);
                }
                
                log_activity($activity_type, $description);
                
                json_response(['success' => true, 'message' => 'Activity logged successfully']);
                
            } else {
                json_response(['success' => false, 'message' => 'Invalid action'], 400);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Internal server error'], 500);
}
?>