<?php
/**
 * Database Configuration for Mazhalai Mart
 * Uses environment variables from .env file
 */

// Load environment configuration
require_once __DIR__ . '/env.php';

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $host = env('DB_HOST', '');
        $dbname = env('DB_NAME', '');
        $username = env('DB_USER', '');
        $password = env('DB_PASS', '');
        
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    return $pdo;
}

// Test connection function
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query('SELECT 1');
        
        // Database connection successful
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
