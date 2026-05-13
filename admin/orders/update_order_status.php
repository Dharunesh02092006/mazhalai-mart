<?php
/**
 * Update Order Status for Mazhalai Mart Admin Panel
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
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');

// Validate inputs
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Get current order details
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    $oldStatus = $order['status'];
    
    // Update order status
    $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$newStatus, $orderId]);
    
    // Log activity (optional - only if admin_activity_log table exists)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $_SESSION['admin_id'],
            'update_status',
            'order',
            $orderId,
            "Updated order {$order['order_id']} status from '$oldStatus' to '$newStatus'",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Ignore logging errors
        error_log("Admin activity log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order status updated successfully',
        'old_status' => $oldStatus,
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    error_log("Update order status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $e->getMessage()]);
}
?>