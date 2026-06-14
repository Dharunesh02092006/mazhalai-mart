<?php
/**
 * User-Specific Orders API for Mazhalai Mart
 * Handles order operations for authenticated users
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
$result = ['success' => false, 'message' => 'No response'];

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            $result = handleGetOrders($pdo, $userId);
            break;
            
        case 'POST':
            $result = handleCreateOrder($pdo, $userId);
            break;
            
        case 'PUT':
            $result = handleUpdateOrder($pdo, $userId);
            break;
            
        case 'DELETE':
            $result = handleCancelOrder($pdo, $userId);
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    error_log("User Orders API error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $result = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

function handleGetOrders($pdo, $userId) {
    try {
        // Get orders for the current user with product images from products table
        $stmt = $pdo->prepare("
            SELECT o.*, oi.product_name, oi.quantity, oi.price as item_price, oi.product_id, 
                   COALESCE(oi.product_image, p.image_path, 'images/placeholder.jpg') as image
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group items by order and fix image paths
        $orders = [];
        foreach ($results as $row) {
            $orderId = $row['id'];
            
            if (!isset($orders[$orderId])) {
                $orders[$orderId] = [
                    'id' => $row['id'],
                    'order_id' => $row['order_id'],
                    'customer_name' => $row['customer_name'],
                    'customer_email' => $row['customer_email'],
                    'customer_phone' => $row['customer_phone'],
                    'shipping_address' => $row['shipping_address'],
                    'payment_method' => $row['payment_method'],
                    'subtotal' => $row['subtotal'],
                    'delivery_charges' => $row['delivery_charges'],
                    'total_amount' => $row['total_amount'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'items' => []
                ];
            }
            
            if ($row['product_name']) {
                // Fix image path
                $imagePath = $row['image'] ?? 'images/placeholder.jpg';
                if (empty($imagePath) || $imagePath === 'undefined' || $imagePath === 'null') {
                    $imagePath = 'images/placeholder.jpg';
                } else if (strpos($imagePath, 'uploads/') === 0) {
                    $imagePath = 'admin/' . $imagePath;
                } else if (strpos($imagePath, 'images/') !== 0 && strpos($imagePath, 'admin/') !== 0 && strpos($imagePath, 'http') !== 0) {
                    $imagePath = 'images/' . $imagePath;
                }
                
                $orders[$orderId]['items'][] = [
                    'name' => $row['product_name'],
                    'product_name' => $row['product_name'],
                    'quantity' => $row['quantity'],
                    'price' => $row['item_price'],
                    'image' => $imagePath,
                    'product_id' => $row['product_id']
                ];
            }
        }
        
        return [
            'success' => true,
            'orders' => array_values($orders),
            'total_orders' => count($orders)
        ];
    } catch (Exception $e) {
        throw new Exception('Error fetching orders: ' . $e->getMessage());
    }
}

function handleCreateOrder($pdo, $userId) {
    return ['success' => false, 'message' => 'Create order endpoint not available'];
}

function handleUpdateOrder($pdo, $userId) {
    return ['success' => false, 'message' => 'Update order endpoint not available'];
}

function handleCancelOrder($pdo, $userId) {
    return ['success' => false, 'message' => 'Cancel order endpoint not available'];
}
?>
