<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login and admin role
require_login();
require_role('admin');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get all users with pagination
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $offset = ($page - 1) * $limit;
                
                $where_clause = "WHERE is_active = 1";
                $params = [];
                
                if (!empty($search)) {
                    $where_clause .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
                    $search_param = "%$search%";
                    $params = [$search_param, $search_param, $search_param];
                }
                
                // Get total count
                $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute($params);
                $total = $count_stmt->fetch()['total'];
                
                // Get users
                $query = "SELECT id, username, full_name, email, role, created_at 
                         FROM users $where_clause 
                         ORDER BY created_at DESC 
                         LIMIT ? OFFSET ?";
                $stmt = $db->prepare($query);
                $stmt->execute(array_merge($params, [$limit, $offset]));
                $users = $stmt->fetchAll();
                
                json_response([
                    'success' => true,
                    'data' => $users,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single user
                $id = (int)$_GET['id'];
                $query = "SELECT id, username, full_name, email, role, created_at FROM users WHERE id = ? AND is_active = 1";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                
                if ($user = $stmt->fetch()) {
                    json_response(['success' => true, 'data' => $user]);
                } else {
                    json_response(['success' => false, 'message' => 'User not found'], 404);
                }
            } else {
                json_response(['success' => false, 'message' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            // Add new user
            $input = json_decode(file_get_contents('php://input'), true);
            
            $username = sanitize_input($input['username'] ?? '');
            $password = $input['password'] ?? '';
            $full_name = sanitize_input($input['fullName'] ?? '');
            $email = sanitize_input($input['email'] ?? '');
            $role = sanitize_input($input['role'] ?? 'viewer');
            
            // Validation
            if (empty($username) || empty($password) || empty($full_name)) {
                json_response(['success' => false, 'message' => 'Username, password, and full name are required'], 400);
            }
            
            if (strlen($password) < 6) {
                json_response(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
            }
            
            if (!empty($email) && !validate_email($email)) {
                json_response(['success' => false, 'message' => 'Invalid email format'], 400);
            }
            
            if (!in_array($role, ['admin', 'staff', 'viewer'])) {
                json_response(['success' => false, 'message' => 'Invalid role'], 400);
            }
            
            // Check if username already exists
            $check_query = "SELECT id FROM users WHERE username = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$username]);
            
            if ($check_stmt->fetch()) {
                json_response(['success' => false, 'message' => 'Username already exists'], 400);
            }
            
            // Hash password
            $hashed_password = hash_password($password);
            
            // Insert user
            $query = "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $hashed_password, $full_name, $email, $role])) {
                $user_id = $db->lastInsertId();
                
                // Log activity
                log_activity('user_created', "New user created: $username", $_SESSION['user_id']);
                
                json_response([
                    'success' => true, 
                    'message' => 'User created successfully',
                    'data' => ['id' => $user_id]
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to create user'], 500);
            }
            break;
            
        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid user ID'], 400);
            }
            
            $username = sanitize_input($input['username'] ?? '');
            $full_name = sanitize_input($input['fullName'] ?? '');
            $email = sanitize_input($input['email'] ?? '');
            $role = sanitize_input($input['role'] ?? 'viewer');
            
            // Validation
            if (empty($username) || empty($full_name)) {
                json_response(['success' => false, 'message' => 'Username and full name are required'], 400);
            }
            
            if (!empty($email) && !validate_email($email)) {
                json_response(['success' => false, 'message' => 'Invalid email format'], 400);
            }
            
            if (!in_array($role, ['admin', 'staff', 'viewer'])) {
                json_response(['success' => false, 'message' => 'Invalid role'], 400);
            }
            
            // Check if username already exists for other users
            $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$username, $id]);
            
            if ($check_stmt->fetch()) {
                json_response(['success' => false, 'message' => 'Username already exists'], 400);
            }
            
            // Update user
            $query = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ? WHERE id = ? AND is_active = 1";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $full_name, $email, $role, $id])) {
                // Log activity
                log_activity('user_updated', "User updated: $username", $_SESSION['user_id']);
                
                json_response(['success' => true, 'message' => 'User updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to update user'], 500);
            }
            break;
            
        case 'DELETE':
            // Soft delete user
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid user ID'], 400);
            }
            
            // Don't allow deleting own account
            if ($id == $_SESSION['user_id']) {
                json_response(['success' => false, 'message' => 'Cannot delete your own account'], 400);
            }
            
            // Get username for logging
            $user_query = "SELECT username FROM users WHERE id = ?";
            $user_stmt = $db->prepare($user_query);
            $user_stmt->execute([$id]);
            $user = $user_stmt->fetch();
            
            if (!$user) {
                json_response(['success' => false, 'message' => 'User not found'], 404);
            }
            
            // Soft delete
            $query = "UPDATE users SET is_active = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$id])) {
                // Log activity
                log_activity('user_deleted', "User deleted: " . $user['username'], $_SESSION['user_id']);
                
                json_response(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to delete user'], 500);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Users API error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Internal server error'], 500);
}
?>