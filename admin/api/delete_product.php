<?php
/**
 * Delete Product API for Mazhalai Mart Admin Panel
 */

session_start();

header('Content-Type: application/json');

// Simple session check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($productId <= 0) {
        throw new Exception('Invalid product ID');
    }
    
    // Get product details first
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Check if product is used in any orders (check both order_items table and orders table with product_names)
    $orderCount = 0;
    
    // Check order_items table if it exists
    try {
        $orderCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $orderCheckStmt->execute([$productId]);
        $orderCount += $orderCheckStmt->fetchColumn();
    } catch (Exception $e) {
        // order_items table might not exist, ignore
    }
    
    // Check orders table with product_names column
    try {
        $orderCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE product_names LIKE ?");
        $orderCheckStmt->execute(['%' . $product['name'] . '%']);
        $orderCount += $orderCheckStmt->fetchColumn();
    } catch (Exception $e) {
        // Ignore if column doesn't exist
    }
    
    if ($orderCount > 0) {
        // Don't delete, just deactivate
        $updateStmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $updateStmt->execute([$productId]);
        
        // Log activity
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['admin_id'],
                'deactivate',
                'product',
                $productId,
                "Deactivated product (used in $orderCount orders): " . $product['name'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
            error_log("Admin activity log error: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deactivated (used in existing orders)',
            'action' => 'deactivated'
        ]);
        exit;
    }
    
    // Delete product image if exists
    if ($product['image_path'] && file_exists('../../' . $product['image_path'])) {
        unlink('../../' . $product['image_path']);
    }
    
    // Delete product
    $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $deleteStmt->execute([$productId]);
    
    // Log activity
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $_SESSION['admin_id'],
            'delete',
            'product',
            $productId,
            "Deleted product: " . $product['name'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Ignore logging errors
        error_log("Admin activity log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully',
        'action' => 'deleted'
    ]);
    
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>