<?php
/**
 * Fixed User Statistics API for Mazhalai Mart Admin Panel
 */

session_start();

// Set content type
header('Content-Type: application/json');

// Enable error reporting but don't display errors in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    // Database connection
    $host = 'localhost';
    $dbname = 'mazhalai_mart';
    $username = 'root';
    $password = 'dharun2403@';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user statistics
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Handle case where status column might not exist
    try {
        $activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(status, 'active') = 'active'")->fetchColumn();
        $blockedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
    } catch (Exception $e) {
        // If status column doesn't exist, assume all users are active
        $activeUsers = $totalUsers;
        $blockedUsers = 0;
    }
    
    // Get new users this month
    $newUsersThisMonth = $pdo->query("
        SELECT COUNT(*) FROM users 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ")->fetchColumn();
    
    // Get active customers (users who have placed orders)
    $activeCustomers = $pdo->query("
        SELECT COUNT(DISTINCT user_id) FROM orders 
        WHERE user_id IS NOT NULL AND user_id > 0
    ")->fetchColumn();
    
    // If no user_id column in orders, try to count by email matching
    if ($activeCustomers == 0) {
        try {
            $activeCustomers = $pdo->query("
                SELECT COUNT(DISTINCT u.id) FROM users u 
                INNER JOIN orders o ON u.email = o.customer_email
            ")->fetchColumn();
        } catch (Exception $e) {
            $activeCustomers = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => (int)$totalUsers,
            'active' => (int)$activeUsers,
            'blocked' => (int)$blockedUsers,
            'new_this_month' => (int)$newUsersThisMonth,
            'active_customers' => (int)$activeCustomers
        ]
    ]);
    
} catch (Exception $e) {
    error_log("User stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>