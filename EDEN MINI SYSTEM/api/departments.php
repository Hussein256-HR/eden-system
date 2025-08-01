<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
require_login();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$department = $_GET['department'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Define department tables mapping
    $department_tables = [
        'children' => 'children',
        'choir' => 'choir',
        'ushering' => 'ushering',
        'security' => 'security'
    ];
    
    // Generic departments use department_members table
    $generic_departments = [
        'pastoral', 'administration', 'church-elders', 'worship-team', 'orange-strategy',
        'intercession', 'media', 'sound', 'building', 'maintenance', 'washrooms',
        'school-of-ministry', 'baptism-dedication-new people', 'finance', 'school-of-music',
        'prayer-leaders', 'conference', 'leader-of-men/women', 'sports', 'transport',
        'missions-and-evangelism', 'youth-ministry', 'divine-camping', 'singles',
        'pre-maritial counselling', 'altars', 'pastors-welfare'
    ];
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $offset = ($page - 1) * $limit;
                
                if (isset($department_tables[$department])) {
                    // Handle specific department tables
                    $table = $department_tables[$department];
                    
                    switch ($department) {
                        case 'children':
                            $where_clause = "WHERE 1=1";
                            $params = [];
                            
                            if (!empty($search)) {
                                $where_clause .= " AND (child_name LIKE ? OR parent_name LIKE ?)";
                                $search_param = "%$search%";
                                $params = [$search_param, $search_param];
                            }
                            
                            // Get total count
                            $count_query = "SELECT COUNT(*) as total FROM $table $where_clause";
                            $count_stmt = $db->prepare($count_query);
                            $count_stmt->execute($params);
                            $total = $count_stmt->fetch()['total'];
                            
                            // Get data
                            $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute(array_merge($params, [$limit, $offset]));
                            $data = $stmt->fetchAll();
                            break;
                            
                        case 'choir':
                            $where_clause = "WHERE 1=1";
                            $params = [];
                            
                            if (!empty($search)) {
                                $where_clause .= " AND (member_name LIKE ? OR voice_type LIKE ?)";
                                $search_param = "%$search%";
                                $params = [$search_param, $search_param];
                            }
                            
                            $count_query = "SELECT COUNT(*) as total FROM $table $where_clause";
                            $count_stmt = $db->prepare($count_query);
                            $count_stmt->execute($params);
                            $total = $count_stmt->fetch()['total'];
                            
                            $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute(array_merge($params, [$limit, $offset]));
                            $data = $stmt->fetchAll();
                            break;
                            
                        case 'ushering':
                            $where_clause = "WHERE 1=1";
                            $params = [];
                            
                            if (!empty($search)) {
                                $where_clause .= " AND (usher_name LIKE ? OR team LIKE ?)";
                                $search_param = "%$search%";
                                $params = [$search_param, $search_param];
                            }
                            
                            $count_query = "SELECT COUNT(*) as total FROM $table $where_clause";
                            $count_stmt = $db->prepare($count_query);
                            $count_stmt->execute($params);
                            $total = $count_stmt->fetch()['total'];
                            
                            $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute(array_merge($params, [$limit, $offset]));
                            $data = $stmt->fetchAll();
                            break;
                            
                        case 'security':
                            $where_clause = "WHERE 1=1";
                            $params = [];
                            
                            if (!empty($search)) {
                                $where_clause .= " AND (security_name LIKE ? OR shift LIKE ?)";
                                $search_param = "%$search%";
                                $params = [$search_param, $search_param];
                            }
                            
                            $count_query = "SELECT COUNT(*) as total FROM $table $where_clause";
                            $count_stmt = $db->prepare($count_query);
                            $count_stmt->execute($params);
                            $total = $count_stmt->fetch()['total'];
                            
                            $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute(array_merge($params, [$limit, $offset]));
                            $data = $stmt->fetchAll();
                            break;
                    }
                    
                } elseif (in_array($department, $generic_departments)) {
                    // Handle generic departments
                    $where_clause = "WHERE department = ?";
                    $params = [$department];
                    
                    if (!empty($search)) {
                        $where_clause .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                        $search_param = "%$search%";
                        $params = array_merge($params, [$search_param, $search_param, $search_param]);
                    }
                    
                    $count_query = "SELECT COUNT(*) as total FROM department_members $where_clause";
                    $count_stmt = $db->prepare($count_query);
                    $count_stmt->execute($params);
                    $total = $count_stmt->fetch()['total'];
                    
                    $query = "SELECT * FROM department_members $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute(array_merge($params, [$limit, $offset]));
                    $data = $stmt->fetchAll();
                    
                } else {
                    json_response(['success' => false, 'message' => 'Invalid department'], 400);
                }
                
                json_response([
                    'success' => true,
                    'data' => $data,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]);
                
            } elseif ($action === 'stats') {
                // Get department statistics
                $stats = [];
                
                // Specific departments
                foreach ($department_tables as $dept => $table) {
                    $query = "SELECT COUNT(*) as count FROM $table";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $stats[$dept] = $stmt->fetch()['count'];
                }
                
                // Generic departments
                foreach ($generic_departments as $dept) {
                    $query = "SELECT COUNT(*) as count FROM department_members WHERE department = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$dept]);
                    $stats[$dept] = $stmt->fetch()['count'];
                }
                
                json_response(['success' => true, 'data' => $stats]);
            }
            break;
            
        case 'POST':
            // Add new department member
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($department_tables[$department])) {
                // Handle specific department tables
                switch ($department) {
                    case 'children':
                        $required = ['childName', 'age', 'gender', 'parentName'];
                        foreach ($required as $field) {
                            if (empty($data[$field])) {
                                json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
                            }
                        }
                        
                        $query = "INSERT INTO children (child_name, age, gender, parent_name, parent_phone) VALUES (?, ?, ?, ?, ?)";
                        $params = [
                            sanitize_input($data['childName']),
                            (int)$data['age'],
                            sanitize_input($data['gender']),
                            sanitize_input($data['parentName']),
                            sanitize_input($data['parentPhone'] ?? '')
                        ];
                        break;
                        
                    case 'choir':
                        $required = ['memberName', 'voiceType'];
                        foreach ($required as $field) {
                            if (empty($data[$field])) {
                                json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
                            }
                        }
                        
                        $query = "INSERT INTO choir (member_name, voice_type, phone, join_date) VALUES (?, ?, ?, ?)";
                        $params = [
                            sanitize_input($data['memberName']),
                            sanitize_input($data['voiceType']),
                            sanitize_input($data['phone'] ?? ''),
                            !empty($data['joinDate']) ? $data['joinDate'] : null
                        ];
                        break;
                        
                    case 'ushering':
                        $required = ['usherName'];
                        foreach ($required as $field) {
                            if (empty($data[$field])) {
                                json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
                            }
                        }
                        
                        $query = "INSERT INTO ushering (usher_name, team, phone, join_date) VALUES (?, ?, ?, ?)";
                        $params = [
                            sanitize_input($data['usherName']),
                            sanitize_input($data['usherTeam'] ?? ''),
                            sanitize_input($data['phone'] ?? ''),
                            !empty($data['joinDate']) ? $data['joinDate'] : null
                        ];
                        break;
                        
                    case 'security':
                        $required = ['securityName'];
                        foreach ($required as $field) {
                            if (empty($data[$field])) {
                                json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
                            }
                        }
                        
                        $query = "INSERT INTO security (security_name, shift, phone, join_date) VALUES (?, ?, ?, ?)";
                        $params = [
                            sanitize_input($data['securityName']),
                            sanitize_input($data['shift'] ?? ''),
                            sanitize_input($data['phone'] ?? ''),
                            !empty($data['joinDate']) ? $data['joinDate'] : null
                        ];
                        break;
                }
                
            } elseif (in_array($department, $generic_departments)) {
                // Handle generic departments
                $required = ['name'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
                    }
                }
                
                $query = "INSERT INTO department_members (department, name, email, phone, join_date, next_of_kin, marital_status, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $department,
                    sanitize_input($data['name']),
                    sanitize_input($data['email'] ?? ''),
                    sanitize_input($data['phone'] ?? ''),
                    !empty($data['joinDate']) ? $data['joinDate'] : null,
                    sanitize_input($data['nextOfKin'] ?? ''),
                    sanitize_input($data['maritalStatus'] ?? ''),
                    sanitize_input($data['address'] ?? '')
                ];
                
            } else {
                json_response(['success' => false, 'message' => 'Invalid department'], 400);
            }
            
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($params)) {
                $member_id = $db->lastInsertId();
                
                // Log activity
                log_activity('department_member_added', "New member added to $department department");
                
                json_response([
                    'success' => true, 
                    'message' => 'Member added successfully',
                    'data' => ['id' => $member_id]
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to add member'], 500);
            }
            break;
            
        case 'DELETE':
            // Delete department member
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid member ID'], 400);
            }
            
            if (isset($department_tables[$department])) {
                $table = $department_tables[$department];
                $query = "DELETE FROM $table WHERE id = ?";
            } elseif (in_array($department, $generic_departments)) {
                $query = "DELETE FROM department_members WHERE id = ? AND department = ?";
                $params = [$id, $department];
            } else {
                json_response(['success' => false, 'message' => 'Invalid department'], 400);
            }
            
            $stmt = $db->prepare($query);
            $execute_params = isset($params) ? $params : [$id];
            
            if ($stmt->execute($execute_params)) {
                // Log activity
                log_activity('department_member_deleted', "Member deleted from $department department");
                
                json_response(['success' => true, 'message' => 'Member deleted successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to delete member'], 500);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Departments API error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Internal server error'], 500);
}
?>