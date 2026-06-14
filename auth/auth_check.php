<?php
/**
 * Authentication Check Middleware for Mazhalai Mart
 * Verifies user authentication and handles remember me functionality
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session_config.php';

// Start secure session
startSecureSession();

// Check if user is already logged in via session
if (isLoggedIn()) {
    // User is authenticated via session
    return;
}

// Check for remember me token if session doesn't exist
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        $pdo = getDBConnection();
        
        // Verify token and get user data (check if user is not blocked)
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.status, rt.expires_at 
            FROM users u 
            JOIN remember_tokens rt ON u.id = rt.user_id 
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['status'] === 'active') {
            // Valid token found - recreate session
            setUserSession($user);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Optionally extend token expiry (rolling expiration)
            $newExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $updateStmt = $pdo->prepare("UPDATE remember_tokens SET expires_at = ? WHERE token = ?");
            $updateStmt->execute([$newExpiry, $token]);
            
            // Update cookie expiry
            setRememberMeCookie($token, strtotime('+30 days'));
            
            return; // User is now authenticated
        } else {
            // Invalid or expired token - clear cookie
            clearRememberMeCookie();
        }
    } catch (Exception $e) {
        error_log("Auth check error: " . $e->getMessage());
        clearRememberMeCookie();
    }
}

// If we reach here, user is not authenticated
// For API requests, return JSON response
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required',
        'redirect' => '/mazhalai-mart/login.html'
    ]);
    exit;
}

// For regular page requests, redirect to login
if (!defined('AUTH_OPTIONAL')) {
    header('Location: /mazhalai-mart/login.html');
    exit;
}
?>