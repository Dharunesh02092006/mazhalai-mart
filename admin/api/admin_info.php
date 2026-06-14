<?php
/**
 * Admin Info API for Mazhalai Mart Admin Panel
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
require_once __DIR__ . '/../../config/admin.php';
    $pdo = getDBConnection();
    
    // Get admin info from session or database
    $adminId = $_SESSION['admin_id'];
    
    // Try to get from admins table first
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo json_encode([
                'success' => true,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'full_name' => $admin['full_name'] ?? $admin['username'],
                    'email' => $admin['email'] ?? '',
                    'role' => $admin['role'] ?? 'Administrator'
                ]
            ]);
            exit;
        }
    } catch (Exception $e) {
        // Admins table might not exist, continue with fallback
    }
    
    // Fallback: Use session data or default values
    $adminInfo = [
        'id' => $adminId,
        'username' => $_SESSION['admin_username'] ?? env('ADMIN_USERNAME', 'admin'),
        'full_name' => $_SESSION['admin_name'] ?? env('ADMIN_FULL_NAME', 'Administrator'),
        'email' => $_SESSION['admin_email'] ?? env('ADMIN_EMAIL', 'admin@mazhalaimart.com'),
        'role' => $_SESSION['admin_role'] ?? 'Administrator'
    ];
    
    echo json_encode([
        'success' => true,
        'admin' => $adminInfo
    ]);
    
} catch (Exception $e) {
    error_log("Admin info error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load admin information'
    ]);
}
?>