<?php
/**
 * User-Specific Orders API for Mazhalai Mart
 * Handles order operations for authenticated users
 */

header('Content-Type: application/json');
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

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGetOrders($pdo, $userId);
            break;
            
        case 'POST':
            handleCreateOrder($pdo, $userId);
            break;
            
        case 'PUT':
            handleUpdateOrder($pdo, $userId);
            break;
            
        case 'DELETE':
            handleCancelOrder($pdo, $userId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Orders API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function handleGetOrders($pdo, $userId) {
    // Get orders for the current user
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_name, oi.quantity, oi.price as item_price
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group items by order
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
            $orders[$orderId]['items'][] = [
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['item_price']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'orders' => array_values($orders),
        'total_orders' => count($orders)
    ]);
}

function handleCreateOrder($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get user cart items
        $cartStmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ?");
        $cartStmt->execute([$userId]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cartItems)) {
            throw new Exception('Cart is empty');
        }
        
        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['product_price'] * $item['quantity'];
        }
        
        $deliveryCharges = 60;
        $totalAmount = $subtotal + $deliveryCharges;
        
        // Generate order ID
        $orderId = generateOrderId();
        
        // Get user info
        $userStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Create order
        $orderData = [
            'order_id' => $orderId,
            'user_id' => $userId,
            'customer_name' => $input['customer_name'] ?? $user['username'],
            'customer_email' => $input['customer_email'] ?? $user['email'],
            'customer_phone' => $input['customer_phone'] ?? '',
            'shipping_address' => json_encode($input['shipping_address'] ?? []),
            'payment_method' => $input['payment_method'] ?? 'cod',
            'subtotal' => $subtotal,
            'delivery_charges' => $deliveryCharges,
            'total_amount' => $totalAmount,
            'status' => 'pending'
        ];
        
        $insertOrderStmt = $pdo->prepare("
            INSERT INTO orders (order_id, user_id, customer_name, customer_email, customer_phone, 
                              shipping_address, payment_method, subtotal, delivery_charges, total_amount, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertOrderStmt->execute([
            $orderData['order_id'],
            $orderData['user_id'],
            $orderData['customer_name'],
            $orderData['customer_email'],
            $orderData['customer_phone'],
            $orderData['shipping_address'],
            $orderData['payment_method'],
            $orderData['subtotal'],
            $orderData['delivery_charges'],
            $orderData['total_amount'],
            $orderData['status']
        ]);
        
        $dbOrderId = $pdo->lastInsertId();
        
        // Add order items
        $insertItemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_name, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cartItems as $item) {
            $insertItemStmt->execute([
                $dbOrderId,
                $item['product_name'],
                $item['quantity'],
                $item['product_price']
            ]);
        }
        
        // Clear user cart
        $clearCartStmt = $pdo->prepare("DELETE FROM user_cart WHERE user_id = ?");
        $clearCartStmt->execute([$userId]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $orderId,
            'total_amount' => $totalAmount
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleUpdateOrder($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['order_id']) || !isset($input['status'])) {
        throw new Exception('Order ID and status are required');
    }
    
    $orderId = $input['order_id'];
    $status = $input['status'];
    
    // Verify order belongs to user
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?, updated_at = NOW() 
        WHERE order_id = ? AND user_id = ?
    ");
    $stmt->execute([$status, $orderId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Order updated successfully'
        ]);
    } else {
        throw new Exception('Order not found or access denied');
    }
}

function handleCancelOrder($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['order_id'])) {
        throw new Exception('Order ID is required');
    }
    
    $orderId = $input['order_id'];
    
    // Verify order belongs to user and can be cancelled
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE order_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$orderId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Order cancelled successfully'
        ]);
    } else {
        throw new Exception('Order not found, access denied, or cannot be cancelled');
    }
}

function generateOrderId() {
    $date = new DateTime();
    $dateStr = $date->format('Ymd');
    $randomNum = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    return 'BCR-' . $dateStr . '-' . $randomNum;
}
?>