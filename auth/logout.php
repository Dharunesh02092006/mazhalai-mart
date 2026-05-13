<?php
/**
 * User Logout for Mazhalai Mart
 * Handles secure logout with token cleanup
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session_config.php';

// Start session
startSecureSession();

try {
    $pdo = getDBConnection();
    
    // If user is logged in, clean up remember tokens
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        
        // Remove all remember tokens for this user
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        // Remove token from database
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
        
        // Clear cookie
        clearRememberMeCookie();
    }
    
    // Clear session data
    clearUserSession();
    
    // Destroy session completely
    session_destroy();
    
    // For AJAX requests, return JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/mazhalai-mart/login.html'
        ]);
        exit;
    }
    
    // For regular requests, redirect to login page
    header('Location: /mazhalai-mart/login.html?message=logged_out');
    exit;
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    
    // Still try to clear session and redirect
    session_destroy();
    clearRememberMeCookie();
    
    header('Location: /mazhalai-mart/login.html?error=logout_error');
    exit;
}
?>