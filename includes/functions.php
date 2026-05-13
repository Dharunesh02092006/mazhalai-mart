<?php
// Utility functions for Mazhalai Mart

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (Indian format)
function validatePhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

// Generate order ID
function generateOrderId() {
    return 'BCR-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Calculate delivery charges
function calculateDeliveryCharges($subtotal) {
    if ($subtotal >= 500) {
        return 0; // Free delivery for orders above ₹500
    }
    return 40;
}

// Send email notification (basic implementation)
function sendOrderConfirmation($email, $orderData) {
    $subject = "Order Confirmation - " . $orderData['order_id'];
    $message = "Dear Customer,\n\n";
    $message .= "Thank you for your order with Mazhalai Mart!\n\n";
    $message .= "Order ID: " . $orderData['order_id'] . "\n";
    $message .= "Total Amount: " . formatCurrency($orderData['total_amount']) . "\n\n";
    $message .= "We will process your order shortly.\n\n";
    $message .= "Best regards,\nMazhalai Mart Team";
    
    $headers = "From: noreply@mazhalaimart.com\r\n";
    $headers .= "Reply-To: support@mazhalaimart.com\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Log activity
function logActivity($action, $details = '') {
    $logFile = 'logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $action";
    if ($details) {
        $logEntry .= " - $details";
    }
    $logEntry .= "\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Check if user is logged in (for future user authentication)
function isLoggedIn() {
    session_start();
    return isset($_SESSION['user_id']);
}

// Get user data (for future user authentication)
function getUserData() {
    session_start();
    if (isset($_SESSION['user_id'])) {
        // Fetch user data from database
        return $_SESSION['user_data'] ?? null;
    }
    return null;
}

// Response helper for API
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    
    $response = ['success' => $success];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($message) {
        $response['message'] = $message;
    }
    
    echo json_encode($response);
    exit;
}

// File upload helper
function uploadImage($file, $uploadDir = 'images/') {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Upload failed'];
    }
}
?>