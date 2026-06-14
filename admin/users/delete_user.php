<?php
/**
 * Delete User API for Mazhalai Mart Admin Panel
 * Permanently removes a user from the database
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Database connection
    $host = 'dummy_host';
    $dbname = 'dummy_database';
    $username = 'dummy_user';
    $password = 'dummy_password';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Start transaction to ensure data integrity
    $pdo->beginTransaction();
    
    try {
        // First, check if user has any orders
        $orderStmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
        $orderStmt->execute([$userId]);
        $orderCount = $orderStmt->fetch(PDO::FETCH_ASSOC)['order_count'];
        
        if ($orderCount > 0) {
            // If user has orders, we might want to handle this differently
            // For now, we'll delete the user but keep the orders with null user_id
            $updateOrdersStmt = $pdo->prepare("
                UPDATE orders 
                SET user_id = NULL, customer_name = CONCAT(customer_name, ' (User Deleted)') 
                WHERE user_id = ?
            ");
            $updateOrdersStmt->execute([$userId]);
        }
        
        // Delete from cart items first (if cart table exists)
        try {
            $deleteCartStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $deleteCartStmt->execute([$userId]);
        } catch (Exception $e) {
            // Cart table might not exist, ignore
        }
        
        // Delete user sessions (if user_sessions table exists)
        try {
            $deleteSessionsStmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $deleteSessionsStmt->execute([$userId]);
        } catch (Exception $e) {
            // User sessions table might not exist, ignore
        }
        
        // Finally, delete the user
        $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteUserStmt->execute([$userId]);
        
        // Log activity (optional - ignore if table doesn't exist)
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['admin_id'],
                'delete',
                'user',
                $userId,
                "Deleted user: " . $user['username'] . " (" . $user['email'] . ")",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
            error_log("Admin activity log error: " . $e->getMessage());
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully',
            'deleted_user' => [
                'username' => $user['username'],
                'email' => $user['email'],
                'orders_affected' => $orderCount
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>