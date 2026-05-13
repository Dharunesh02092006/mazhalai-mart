<?php
/**
 * Order Statistics API for Mazhalai Mart Admin Panel
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

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Get order statistics by status
    $statsQuery = "
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
        FROM orders 
        GROUP BY status
    ";
    $statsStmt = $pdo->query($statsQuery);
    $orderStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easier access
    $stats = [];
    foreach ($orderStats as $stat) {
        $stats[$stat['status']] = [
            'count' => (int)$stat['count'],
            'total_amount' => (float)$stat['total_amount']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Order stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load order statistics'
    ]);
}
?>