<?php
/**
 * Admin Login API for Mazhalai Mart Admin Panel
 * Handles JSON login requests from HTML login page
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['username']) || empty($input['password'])) {
        throw new Exception('Username and password are required');
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    $rememberMe = isset($input['remember_me']) && $input['remember_me'];
    
    // Connect to database
    $pdo = getDBConnection();
    
    // Get admin by username or email
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND status = 'active'");
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $loginSuccess = false;
    
    if ($admin && password_verify($password, $admin['password'])) {
        $loginSuccess = true;
    } else {
        // Fallback to environment credentials if database admin not found
        $envCredentials = getAdminCredentials();
        if ($username === $envCredentials['username'] && $password === $envCredentials['password']) {
            // Create a virtual admin object from environment
            $admin = [
                'id' => 1,
                'username' => $envCredentials['username'],
                'email' => $envCredentials['email'],
                'full_name' => $envCredentials['full_name'],
                'role' => 'super_admin'
            ];
            $loginSuccess = true;
        }
    }
    
    if (!$loginSuccess) {
        throw new Exception('Invalid username or password');
    }
    
    // Set session
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_full_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_login_time'] = time();
    
    // Log activity
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, description, ip_address, user_agent) 
            VALUES (?, 'login', 'Admin logged in via API', ?, ?)
        ");
        $logStmt->execute([
            $admin['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Admin login log error: " . $e->getMessage());
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'full_name' => $admin['full_name'],
            'role' => $admin['role']
        ],
        'redirect' => 'dashboard.html'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Admin login database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>