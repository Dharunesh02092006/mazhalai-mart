<?php
/**
 * User Status API for Mazhalai Mart
 * Returns current user authentication status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session_config.php';

// Define AUTH_OPTIONAL to prevent redirect in auth_check
define('AUTH_OPTIONAL', true);
require_once __DIR__ . '/auth_check.php';

try {
    if (isLoggedIn()) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => getCurrentUserId(),
                'username' => getCurrentUsername(),
                'email' => $_SESSION['email'] ?? null
            ]
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'user' => null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'authenticated' => false,
        'error' => 'Server error'
    ]);
}
?>