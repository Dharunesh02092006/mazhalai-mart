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
    
    // Connect to database
    $pdo = getDBConnection();
    
    // Handle real-time email availability check
    if (isset($input['check_email'])) {
        $email = trim($input['check_email']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['available' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch();
        
        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Email already registered' : 'Email available'
        ]);
        exit;
    }
    
    // Handle real-time username availability check
    if (isset($input['check_username'])) {
        $username = trim($input['check_username']);
        
        if (strlen($username) < 3) {
            echo json_encode(['available' => false, 'message' => 'Username too short']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetch();
        
        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Username already taken' : 'Username available'
        ]);
        exit;
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
        throw new Exception('This email address is already registered. Please use a different email or try logging in.');
    }
    
    // Check for duplicate username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('This username is already taken. Please choose a different username.');
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
        'redirect' => '/mazhalai-mart/index.html'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Signup database error: " . $e->getMessage());
    
    // Check if it's a duplicate entry error (MySQL error code 1062)
    if ($e->getCode() == 23000) {
        // Check if it's specifically about email or username
        if (strpos($e->getMessage(), 'email') !== false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'This email address is already registered. Please use a different email or try logging in.'
            ]);
        } elseif (strpos($e->getMessage(), 'username') !== false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'This username is already taken. Please choose a different username.'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Account with this information already exists.'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again later.'
        ]);
    }
}
?>