<?php
/**
 * Admin Logout for Mazhalai Mart Admin Panel
 */

session_start();

require_once __DIR__ . '/../config/database.php';

// Log logout activity if admin is logged in
if (isset($_SESSION['admin_id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, description, ip_address, user_agent) 
            VALUES (?, 'logout', 'Admin logged out', ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Admin logout log error: " . $e->getMessage());
    }
}

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page with success message
header('Location: login.html?message=logged_out');
exit;
?>