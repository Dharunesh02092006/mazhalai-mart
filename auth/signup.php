<?php
/**
 * User Signup API for Mazhalai Mart
 * Handles user registration with validation and security
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
    $required_fields = ['username', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        throw new Exception('Username must be between 3 and 50 characters');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Connect to database
    $pdo = getDBConnection();
    
    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already exists');
    }
    
    // Check for duplicate username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('Username already exists');
    }
    
    // Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    // Start session for the new user
    startSecureSession();
    setUserSession([
        'id' => $userId,
        'username' => $username,
        'email' => $email
    ]);
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email
        ],
        'redirect' => '/mazhalai-mart-copy/index.html'
    ]);
    
} catch (PDOException $e) {
    error_log("Signup database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>