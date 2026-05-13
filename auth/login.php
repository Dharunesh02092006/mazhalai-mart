<?php
/**
 * User Login API for Mazhalai Mart
 * Handles user authentication with remember me functionality
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session_config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception('Email and password are required');
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    $rememberMe = isset($input['remember_me']) && $input['remember_me'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Connect to database
    $pdo = getDBConnection();
    
    // Get user by email (check if user is not blocked)
    $stmt = $pdo->prepare("SELECT id, username, email, password, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Invalid email or password');
    }
    
    // Check if user is blocked
    if ($user['status'] === 'blocked') {
        throw new Exception('Your account has been blocked. Please contact support.');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password');
    }
    
    // Start session
    startSecureSession();
    setUserSession($user);
    
    // Handle remember me functionality
    $rememberToken = null;
    if ($rememberMe) {
        // Generate secure token
        $rememberToken = generateSecureToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Clean up old tokens for this user (optional - keep only latest)
        $cleanupStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $cleanupStmt->execute([$user['id']]);
        
        // Store token in database
        $tokenStmt = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $tokenStmt->execute([$user['id'], $rememberToken, $expiresAt]);
        
        // Set secure cookie
        setRememberMeCookie($rememberToken, strtotime('+30 days'));
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ],
        'remember_me' => $rememberMe,
        'redirect' => '/mazhalai-mart/index.html'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Login database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>