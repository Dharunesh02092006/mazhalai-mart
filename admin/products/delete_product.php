<?php
/**
 * Delete Product for Mazhalai Mart Admin Panel
 */

require_once '../admin_check.php';

// Get product ID
$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: view_products.php?error=invalid_id');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get product details first
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: view_products.php?error=product_not_found');
        exit;
    }
    
    // Check if product is used in any orders
    $orderCheckStmt = $pdo->prepare("
        SELECT COUNT(*) FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.product_name = ?
    ");
    $orderCheckStmt->execute([$product['name']]);
    $orderCount = $orderCheckStmt->fetchColumn();
    
    if ($orderCount > 0) {
        // Don't delete, just deactivate
        $updateStmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $updateStmt->execute([$productId]);
        
        logAdminActivity('deactivate', 'product', $productId, "Deactivated product (used in $orderCount orders): " . $product['name']);
        
        header('Location: view_products.php?message=product_deactivated');
        exit;
    }
    
    // Delete product image if exists
    if ($product['image_path'] && file_exists('../' . $product['image_path'])) {
        unlink('../' . $product['image_path']);
    }
    
    // Delete product
    $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $deleteStmt->execute([$productId]);
    
    // Log activity
    logAdminActivity('delete', 'product', $productId, "Deleted product: " . $product['name']);
    
    header('Location: view_products.php?message=product_deleted');
    exit;
    
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    header('Location: view_products.php?error=delete_failed');
    exit;
}
?>