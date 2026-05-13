<?php
/**
 * User Profile API for Mazhalai Mart
 * Handles user profile data and statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session_config.php';

// Require authentication
require_once __DIR__ . '/../auth/auth_check.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGetProfile($pdo, $userId);
            break;
            
        case 'POST':
        case 'PUT':
            handleUpdateProfile($pdo, $userId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Profile API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function handleGetProfile($pdo, $userId) {
    // Get user basic info
    $userStmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.created_at,
               p.first_name, p.last_name, p.phone, p.address, 
               p.city, p.state, p.pincode, p.date_of_birth
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get cart statistics
    $cartStmt = $pdo->prepare("
        SELECT COUNT(*) as total_items, COALESCE(SUM(quantity), 0) as total_quantity
        FROM user_cart 
        WHERE user_id = ?
    ");
    $cartStmt->execute([$userId]);
    $cartStats = $cartStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order statistics
    $orderStmt = $pdo->prepare("
        SELECT COUNT(*) as total_orders, 
               COALESCE(SUM(total_amount), 0) as total_spent,
               MAX(created_at) as last_order_date
        FROM orders 
        WHERE user_id = ?
    ");
    $orderStmt->execute([$userId]);
    $orderStats = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent orders
    $recentOrdersStmt = $pdo->prepare("
        SELECT order_id, total_amount, status, created_at
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentOrdersStmt->execute([$userId]);
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'city' => $user['city'],
            'state' => $user['state'],
            'pincode' => $user['pincode'],
            'date_of_birth' => $user['date_of_birth'],
            'member_since' => $user['created_at']
        ],
        'statistics' => [
            'cart' => [
                'total_items' => (int)$cartStats['total_items'],
                'total_quantity' => (int)$cartStats['total_quantity']
            ],
            'orders' => [
                'total_orders' => (int)$orderStats['total_orders'],
                'total_spent' => (float)$orderStats['total_spent'],
                'last_order_date' => $orderStats['last_order_date']
            ]
        ],
        'recent_orders' => $recentOrders
    ]);
}

function handleUpdateProfile($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Check if profile exists
    $checkStmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $checkStmt->execute([$userId]);
    $profileExists = $checkStmt->fetch();
    
    $allowedFields = ['first_name', 'last_name', 'phone', 'address', 'city', 'state', 'pincode', 'date_of_birth'];
    $updateData = [];
    $placeholders = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[] = $input[$field];
            $placeholders[] = "$field = ?";
        }
    }
    
    if (empty($updateData)) {
        throw new Exception('No valid fields to update');
    }
    
    if ($profileExists) {
        // Update existing profile
        $updateData[] = $userId;
        $sql = "UPDATE user_profiles SET " . implode(', ', $placeholders) . ", updated_at = NOW() WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
    } else {
        // Create new profile
        $fields = array_keys(array_filter($input, function($key) use ($allowedFields) {
            return in_array($key, $allowedFields);
        }, ARRAY_FILTER_USE_KEY));
        
        $fieldsList = implode(', ', $fields);
        $valuesList = implode(', ', array_fill(0, count($fields), '?'));
        
        $sql = "INSERT INTO user_profiles (user_id, $fieldsList) VALUES (?, $valuesList)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$userId], array_values(array_intersect_key($input, array_flip($fields)))));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
}
?>