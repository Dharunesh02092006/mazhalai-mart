<?php
/**
 * Fixed Block/Unblock User API for Mazhalai Mart Admin Panel
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
    $host = 'localhost';
    $dbname = 'mazhalai_mart';
    $username = 'root';
    $password = 'dharun2403@';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $userId = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($userId <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    if (!in_array($action, ['block', 'unblock'])) {
        throw new Exception('Invalid action');
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Update user status
    if ($action === 'block') {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET status = 'blocked', blocked_by = ?, blocked_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['admin_id'], $userId]);
        $message = 'User blocked successfully';
        $logAction = 'block';
    } else {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET status = 'active', blocked_by = NULL, blocked_at = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $message = 'User unblocked successfully';
        $logAction = 'unblock';
    }
    
    // Log activity (optional - ignore if table doesn't exist)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $_SESSION['admin_id'],
            $logAction,
            'user',
            $userId,
            ucfirst($logAction) . "ed user: " . $user['username'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Ignore logging errors
        error_log("Admin activity log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log("Block user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>