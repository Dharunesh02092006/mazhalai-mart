<?php
/**
 * Product Statistics API for Mazhalai Mart Admin Panel
 */

require_once '../admin_check.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Get product statistics
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $lowStockProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();
    $totalValue = $pdo->query("SELECT COALESCE(SUM(price * stock_quantity), 0) FROM products WHERE status = 'active'")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => (int)$totalProducts,
            'active' => (int)$activeProducts,
            'low_stock' => (int)$lowStockProducts,
            'total_value' => (float)$totalValue
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Product stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load product statistics'
    ]);
}
?>