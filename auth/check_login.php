<?php
/**
 * Check if user is logged in - API endpoint
 */

require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/../config/database.php';

// Start secure session
startSecureSession();

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
$isLoggedIn = isLoggedIn();

// Get user info if logged in
$userData = null;
if ($isLoggedIn) {
    $userData = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

echo json_encode([
    'loggedIn' => $isLoggedIn,
    'user' => $userData
]);
?>
