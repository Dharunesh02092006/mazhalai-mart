<?php
/**
 * Admin Configuration for Mazhalai Mart
 * Uses environment variables from .env file
 */

// Load environment configuration
require_once __DIR__ . '/env.php';

/**
 * Get admin credentials from environment
 */
function getAdminCredentials() {
    return [
        'username' => env('ADMIN_USERNAME', 'dummy_admin'),
        'password' => env('ADMIN_PASSWORD', 'dummy_admin_password'),
        'email' => env('ADMIN_EMAIL', 'admin@mazhalaimart.com'),
        'full_name' => env('ADMIN_FULL_NAME', 'Administrator')
    ];
}

/**
 * Verify admin credentials
 */
function verifyAdminCredentials($username, $password) {
    $credentials = getAdminCredentials();
    
    return ($username === $credentials['username'] && 
            password_verify($password, password_hash($credentials['password'], PASSWORD_DEFAULT)) ||
            $password === $credentials['password']); // Allow plain text for backward compatibility
}

/**
 * Get admin configuration settings
 */
function getAdminConfig() {
    return [
        'items_per_page' => env_int('ADMIN_ITEMS_PER_PAGE', 20),
        'session_lifetime' => env_int('SESSION_LIFETIME', 3600),
        'upload_path' => env('UPLOAD_PATH', 'admin/uploads/products/'),
        'max_file_size' => env_int('MAX_FILE_SIZE', 5242880), // 5MB
        'allowed_extensions' => explode(',', env('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,webp'))
    ];
}

/**
 * Get application settings
 */
function getAppConfig() {
    return [
        'name' => env('APP_NAME', 'Mazhalai Mart'),
        'environment' => env('APP_ENV', 'production'),
        'debug' => env_bool('APP_DEBUG', false),
        'items_per_page' => env_int('ITEMS_PER_PAGE', 10)
    ];
}
?>