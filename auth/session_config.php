<?php
/**
 * Session Configuration for Mazhalai Mart Authentication
 * Secure session settings with httponly cookies and proper security
 */

// Start session with secure configuration
function startSecureSession() {
    // Session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID for security (prevent session fixation)
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// Set remember me cookie with secure settings
function setRememberMeCookie($token, $expires) {
    $secure = isset($_SERVER['HTTPS']);
    
    setcookie(
        'remember_token',
        $token,
        [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Clear remember me cookie
function clearRememberMeCookie() {
    setcookie(
        'remember_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Generate secure random token
function generateSecureToken() {
    return bin2hex(random_bytes(32));
}

// Check if user is logged in and not blocked
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Verify user is still active (not blocked)
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || $user['status'] !== 'active') {
            // User is blocked or doesn't exist - clear session
            clearUserSession();
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("User status check error: " . $e->getMessage());
        return false;
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current username
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// Set user session data
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['login_time'] = time();
}

// Clear user session data
function clearUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    unset($_SESSION['login_time']);
}
?>