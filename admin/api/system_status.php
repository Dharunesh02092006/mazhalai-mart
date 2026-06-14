<?php
/**
 * System Status API for Mazhalai Mart Admin Panel
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
    
    // Test database connection
    $pdo->query("SELECT 1");
    $dbStatus = true;
    
    // Check upload directory
    $uploadDir = '../uploads/products/';
    $uploadDirExists = is_dir($uploadDir) && is_writable($uploadDir);
    
    echo json_encode([
        'success' => true,
        'database' => $dbStatus,
        'upload_dir' => $uploadDirExists
    ]);
    
} catch (Exception $e) {
    error_log("System status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'database' => false,
        'upload_dir' => false
    ]);
}
?>