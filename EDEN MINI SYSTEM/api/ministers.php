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
                // Get all ministers with pagination
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $offset = ($page - 1) * $limit;
                
                $where_clause = "WHERE 1=1";
                $params = [];
                
                if (!empty($search)) {
                    $where_clause .= " AND (surname LIKE ? OR other_names LIKE ? OR mobile LIKE ? OR generation LIKE ?)";
                    $search_param = "%$search%";
                    $params = [$search_param, $search_param, $search_param, $search_param];
                }
                
                // Get total count
                $count_query = "SELECT COUNT(*) as total FROM ministers $where_clause";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute($params);
                $total = $count_stmt->fetch()['total'];
                
                // Get ministers
                $query = "SELECT id, surname, other_names, gender, mobile, generation, joined_date, created_at 
                         FROM ministers $where_clause 
                         ORDER BY created_at DESC 
                         LIMIT ? OFFSET ?";
                $stmt = $db->prepare($query);
                $stmt->execute(array_merge($params, [$limit, $offset]));
                $ministers = $stmt->fetchAll();
                
                json_response([
                    'success' => true,
                    'data' => $ministers,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single minister
                $id = (int)$_GET['id'];
                $query = "SELECT * FROM ministers WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                
                if ($minister = $stmt->fetch()) {
                    json_response(['success' => true, 'data' => $minister]);
                } else {
                    json_response(['success' => false, 'message' => 'Minister not found'], 404);
                }
            } else {
                json_response(['success' => false, 'message' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            // Add new minister
            $data = $_POST;
            
            // Handle file upload if present
            $photo_filename = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_file($_FILES['photo'], ['jpg', 'jpeg', 'png']);
                if ($upload_result['success']) {
                    $photo_filename = $upload_result['filename'];
                } else {
                    json_response(['success' => false, 'message' => $upload_result['message']], 400);
                }
            }
            
            // Required fields validation
            $required_fields = ['surname', 'gender', 'dob', 'mobile'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
                }
            }
            
            // Sanitize inputs
            $sanitized_data = [];
            $fields = [
                'surname', 'other_names', 'gender', 'dob', 'age', 'po_box', 'education', 
                'profession', 'occupation', 'tel_office', 'tel_neighbor', 'mobile', 'email',
                'national_id', 'tribe', 'district', 'village', 'employment_address', 
                'year_joined', 'marital_status', 'spouse_name', 'children', 'former_church',
                'saved_date', 'joined_date', 'tithe', 'generation', 'calling', 'service_period'
            ];
            
            foreach ($fields as $field) {
                $sanitized_data[$field] = sanitize_input($data[$field] ?? '');
                if ($sanitized_data[$field] === '') {
                    $sanitized_data[$field] = null;
                }
            }
            
            // Convert age and year_joined to integers
            $sanitized_data['age'] = !empty($sanitized_data['age']) ? (int)$sanitized_data['age'] : null;
            $sanitized_data['year_joined'] = !empty($sanitized_data['year_joined']) ? (int)$sanitized_data['year_joined'] : null;
            
            // Add photo filename
            $sanitized_data['photo'] = $photo_filename;
            
            // Build insert query
            $columns = array_keys($sanitized_data);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            $query = "INSERT INTO ministers (" . implode(',', $columns) . ") VALUES ($placeholders)";
            
            $stmt = $db->prepare($query);
            
            if ($stmt->execute(array_values($sanitized_data))) {
                $minister_id = $db->lastInsertId();
                
                // Log activity
                log_activity('minister_registered', "New minister registered: " . $sanitized_data['surname'] . " " . $sanitized_data['other_names']);
                
                json_response([
                    'success' => true, 
                    'message' => 'Minister registered successfully',
                    'data' => ['id' => $minister_id]
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to register minister'], 500);
            }
            break;
            
        case 'PUT':
            // Update minister
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid minister ID'], 400);
            }
            
            // Build update query dynamically
            $allowed_fields = [
                'surname', 'other_names', 'gender', 'dob', 'age', 'po_box', 'education', 
                'profession', 'occupation', 'tel_office', 'tel_neighbor', 'mobile', 'email',
                'national_id', 'tribe', 'district', 'village', 'employment_address', 
                'year_joined', 'marital_status', 'spouse_name', 'children', 'former_church',
                'saved_date', 'joined_date', 'tithe', 'generation', 'calling', 'service_period'
            ];
            
            $update_fields = [];
            $update_values = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $update_fields[] = "$field = ?";
                    $update_values[] = sanitize_input($input[$field]);
                }
            }
            
            if (empty($update_fields)) {
                json_response(['success' => false, 'message' => 'No fields to update'], 400);
            }
            
            $update_values[] = $id;
            $query = "UPDATE ministers SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($update_values)) {
                // Log activity
                log_activity('minister_updated', "Minister updated: ID $id");
                
                json_response(['success' => true, 'message' => 'Minister updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to update minister'], 500);
            }
            break;
            
        case 'DELETE':
            // Delete minister
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid minister ID'], 400);
            }
            
            // Get minister name for logging
            $minister_query = "SELECT surname, other_names FROM ministers WHERE id = ?";
            $minister_stmt = $db->prepare($minister_query);
            $minister_stmt->execute([$id]);
            $minister = $minister_stmt->fetch();
            
            if (!$minister) {
                json_response(['success' => false, 'message' => 'Minister not found'], 404);
            }
            
            // Delete minister
            $query = "DELETE FROM ministers WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$id])) {
                // Log activity
                $name = $minister['surname'] . " " . $minister['other_names'];
                log_activity('minister_deleted', "Minister deleted: $name");
                
                json_response(['success' => true, 'message' => 'Minister deleted successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to delete minister'], 500);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Ministers API error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Internal server error'], 500);
}
?>