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
                // Get all generation members with pagination
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $search = $_GET['search'] ?? '';
                $generation_filter = $_GET['generation'] ?? '';
                $offset = ($page - 1) * $limit;
                
                $where_clause = "WHERE 1=1";
                $params = [];
                
                if (!empty($search)) {
                    $where_clause .= " AND (surname LIKE ? OR other_names LIKE ? OR mobile LIKE ? OR email LIKE ?)";
                    $search_param = "%$search%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                if (!empty($generation_filter)) {
                    $where_clause .= " AND generation = ?";
                    $params[] = $generation_filter;
                }
                
                // Get total count
                $count_query = "SELECT COUNT(*) as total FROM generations $where_clause";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute($params);
                $total = $count_stmt->fetch()['total'];
                
                // Get generation members
                $query = "SELECT id, surname, other_names, gender, mobile, generation, joined_date, created_at 
                         FROM generations $where_clause 
                         ORDER BY created_at DESC 
                         LIMIT ? OFFSET ?";
                $stmt = $db->prepare($query);
                $stmt->execute(array_merge($params, [$limit, $offset]));
                $members = $stmt->fetchAll();
                
                json_response([
                    'success' => true,
                    'data' => $members,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single generation member
                $id = (int)$_GET['id'];
                $query = "SELECT * FROM generations WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                
                if ($member = $stmt->fetch()) {
                    json_response(['success' => true, 'data' => $member]);
                } else {
                    json_response(['success' => false, 'message' => 'Member not found'], 404);
                }
                
            } elseif ($action === 'generations') {
                // Get list of all generations with member counts
                $query = "SELECT generation, COUNT(*) as member_count 
                         FROM generations 
                         WHERE generation IS NOT NULL AND generation != '' 
                         GROUP BY generation 
                         ORDER BY generation";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $generations = $stmt->fetchAll();
                
                json_response(['success' => true, 'data' => $generations]);
                
            } elseif ($action === 'stats') {
                // Get generation statistics
                $stats = [];
                
                // Total members
                $query = "SELECT COUNT(*) as total FROM generations";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['total_members'] = $stmt->fetch()['total'];
                
                // Members by generation
                $query = "SELECT generation, COUNT(*) as count 
                         FROM generations 
                         WHERE generation IS NOT NULL AND generation != '' 
                         GROUP BY generation 
                         ORDER BY count DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['by_generation'] = $stmt->fetchAll();
                
                // Members by gender
                $query = "SELECT gender, COUNT(*) as count 
                         FROM generations 
                         GROUP BY gender";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['by_gender'] = $stmt->fetchAll();
                
                // Recent registrations (last 30 days)
                $query = "SELECT COUNT(*) as count 
                         FROM generations 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['recent_registrations'] = $stmt->fetch()['count'];
                
                json_response(['success' => true, 'data' => $stats]);
                
            } else {
                json_response(['success' => false, 'message' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            // Add new generation member
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
                'profession', 'occupation', 'employment_year', 'tel_office', 'tel_neighbor', 
                'mobile', 'email', 'national_id', 'tribe', 'district', 'village', 'parish',
                'employment_address', 'church_join_year', 'marital_status', 'spouse_name', 
                'children', 'former_church', 'saved_date', 'joined_date', 'generation', 
                'ministry_area', 'assisted_period', 'updation_date'
            ];
            
            foreach ($fields as $field) {
                $sanitized_data[$field] = sanitize_input($data[$field] ?? '');
                if ($sanitized_data[$field] === '') {
                    $sanitized_data[$field] = null;
                }
            }
            
            // Convert numeric fields
            $sanitized_data['age'] = !empty($sanitized_data['age']) ? (int)$sanitized_data['age'] : null;
            $sanitized_data['employment_year'] = !empty($sanitized_data['employment_year']) ? (int)$sanitized_data['employment_year'] : null;
            $sanitized_data['church_join_year'] = !empty($sanitized_data['church_join_year']) ? (int)$sanitized_data['church_join_year'] : null;
            
            // Add photo filename
            $sanitized_data['photo'] = $photo_filename;
            
            // Build insert query
            $columns = array_keys($sanitized_data);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            $query = "INSERT INTO generations (" . implode(',', $columns) . ") VALUES ($placeholders)";
            
            $stmt = $db->prepare($query);
            
            if ($stmt->execute(array_values($sanitized_data))) {
                $member_id = $db->lastInsertId();
                
                // Log activity
                log_activity('generation_member_registered', "New generation member registered: " . $sanitized_data['surname'] . " " . $sanitized_data['other_names']);
                
                json_response([
                    'success' => true, 
                    'message' => 'Generation member registered successfully',
                    'data' => ['id' => $member_id]
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to register member'], 500);
            }
            break;
            
        case 'PUT':
            // Update generation member
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid member ID'], 400);
            }
            
            // Build update query dynamically
            $allowed_fields = [
                'surname', 'other_names', 'gender', 'dob', 'age', 'po_box', 'education', 
                'profession', 'occupation', 'employment_year', 'tel_office', 'tel_neighbor', 
                'mobile', 'email', 'national_id', 'tribe', 'district', 'village', 'parish',
                'employment_address', 'church_join_year', 'marital_status', 'spouse_name', 
                'children', 'former_church', 'saved_date', 'joined_date', 'generation', 
                'ministry_area', 'assisted_period', 'updation_date'
            ];
            
            $update_fields = [];
            $update_values = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $update_fields[] = "$field = ?";
                    $value = sanitize_input($input[$field]);
                    
                    // Handle numeric fields
                    if (in_array($field, ['age', 'employment_year', 'church_join_year'])) {
                        $value = !empty($value) ? (int)$value : null;
                    }
                    
                    $update_values[] = $value;
                }
            }
            
            if (empty($update_fields)) {
                json_response(['success' => false, 'message' => 'No fields to update'], 400);
            }
            
            $update_values[] = $id;
            $query = "UPDATE generations SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($update_values)) {
                // Log activity
                log_activity('generation_member_updated', "Generation member updated: ID $id");
                
                json_response(['success' => true, 'message' => 'Member updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to update member'], 500);
            }
            break;
            
        case 'DELETE':
            // Delete generation member
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid member ID'], 400);
            }
            
            // Get member name for logging
            $member_query = "SELECT surname, other_names FROM generations WHERE id = ?";
            $member_stmt = $db->prepare($member_query);
            $member_stmt->execute([$id]);
            $member = $member_stmt->fetch();
            
            if (!$member) {
                json_response(['success' => false, 'message' => 'Member not found'], 404);
            }
            
            // Delete member
            $query = "DELETE FROM generations WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$id])) {
                // Log activity
                $name = $member['surname'] . " " . $member['other_names'];
                log_activity('generation_member_deleted', "Generation member deleted: $name");
                
                json_response(['success' => true, 'message' => 'Member deleted successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Failed to delete member'], 500);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Generations API error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Internal server error'], 500);
}
?>