<?php
/**
 * User-Specific Cart API for Mazhalai Mart
 * Handles cart operations for authenticated users
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
            handleGetCart($pdo, $userId);
            break;
            
        case 'POST':
            handleAddToCart($pdo, $userId);
            break;
            
        case 'PUT':
            handleUpdateCart($pdo, $userId);
            break;
            
        case 'DELETE':
            handleRemoveFromCart($pdo, $userId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Cart API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function handleGetCart($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT id, product_id, product_name, product_price, product_image, quantity, created_at
        FROM user_cart 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $subtotal = 0;
    $totalItems = 0;
    
    foreach ($cartItems as &$item) {
        $item['total_price'] = $item['product_price'] * $item['quantity'];
        $subtotal += $item['total_price'];
        $totalItems += $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'cart_items' => $cartItems,
        'summary' => [
            'total_items' => $totalItems,
            'subtotal' => $subtotal,
            'delivery_charges' => 60,
            'total' => $subtotal + 60
        ]
    ]);
}

function handleAddToCart($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $productId = $input['product_id'];
    $productName = $input['product_name'] ?? '';
    $productPrice = $input['product_price'] ?? 0;
    $productImage = $input['product_image'] ?? '';
    
    // Ensure product_image is properly formatted
    if (!$productImage || $productImage === 'undefined') {
        // Try to fetch image from products table
        $prodStmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ? LIMIT 1");
        $prodStmt->execute([$productId]);
        $product = $prodStmt->fetch(PDO::FETCH_ASSOC);
        $productImage = $product['image_path'] ?? 'images/placeholder.jpg';
    }
    
    // Normalize image path
    if (empty($productImage) || $productImage === 'undefined' || $productImage === 'null') {
        $productImage = 'images/placeholder.jpg';
    } else if (strpos($productImage, 'uploads/') === 0) {
        // Admin uploads path - prepend admin/
        $productImage = 'admin/' . $productImage;
    } else if (strpos($productImage, 'images/') !== 0 && strpos($productImage, 'admin/') !== 0 && strpos($productImage, 'http') !== 0) {
        // Add images/ prefix if not already there
        $productImage = 'images/' . $productImage;
    }
    $quantity = $input['quantity'] ?? 1;
    
    // Validate required fields
    if (empty($productName) || $productPrice <= 0) {
        throw new Exception('Invalid product data');
    }
    
    // Check if item already exists in cart
    $checkStmt = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$userId, $productId]);
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        $updateStmt = $pdo->prepare("UPDATE user_cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newQuantity, $existingItem['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'action' => 'updated',
            'new_quantity' => $newQuantity
        ]);
    } else {
        // Add new item
        $insertStmt = $pdo->prepare("
            INSERT INTO user_cart (user_id, product_id, product_name, product_price, product_image, quantity) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$userId, $productId, $productName, $productPrice, $productImage, $quantity]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'action' => 'added',
            'cart_item_id' => $pdo->lastInsertId()
        ]);
    }
}

function handleUpdateCart($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_id']) || !isset($input['quantity'])) {
        throw new Exception('Product ID and quantity are required');
    }
    
    $productId = $input['product_id'];
    $quantity = max(1, intval($input['quantity'])); // Minimum quantity is 1
    
    $stmt = $pdo->prepare("
        UPDATE user_cart 
        SET quantity = ?, updated_at = NOW() 
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->execute([$quantity, $userId, $productId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'new_quantity' => $quantity
        ]);
    } else {
        throw new Exception('Cart item not found');
    }
}

function handleRemoveFromCart($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $productId = $input['product_id'];
    
    $stmt = $pdo->prepare("DELETE FROM user_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Product removed from cart successfully'
        ]);
    } else {
        throw new Exception('Cart item not found');
    }
}

// Helper function to get cart count
function getCartCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM user_cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}
?>