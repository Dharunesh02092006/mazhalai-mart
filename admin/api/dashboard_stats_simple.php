<?php
/**
 * Simplified Dashboard Statistics API
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Simple session check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Get basic counts
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } else {
        $totalUsers = 0;
    }
    
    // Get revenue
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    
    // Get admin info
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, role FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_products' => (int)$totalProducts,
            'total_orders' => (int)$totalOrders,
            'total_users' => (int)$totalUsers,
            'total_revenue' => (float)$totalRevenue,
            'products_change' => '+0%',
            'orders_change' => '+0%',
            'users_change' => '+0%',
            'revenue_change' => '+0%'
        ],
        'admin' => $admin
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>