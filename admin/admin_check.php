<?php
/**
 * Admin Authentication Check for Admin Panel Pages
 */

session_start();

require_once __DIR__ . '/../config/database.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Get current admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Get current admin info
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, status FROM admins WHERE id = ? AND status = 'active'");
        $stmt->execute([getCurrentAdminId()]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Admin fetch error: " . $e->getMessage());
        return null;
    }
}

// For regular pages, redirect to login if not authenticated
if (!isAdminLoggedIn()) {
    header('Location: login.html');
    exit;
}
?>