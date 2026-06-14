<?php
/**
 * Web-accessible Database Schema Verification and Fix Script
 * Access this through your web browser to check database structure
 */

// Simple authentication - only allow local access
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('Access denied. This script can only be run locally.');
}

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "Starting database verification...\n";
    
    $pdo = new PDO("mysql:host=dummy_host;dbname=dummy_database;charset=utf8", 'dummy_user', 'dummy_password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "Users table not found. Creating from complete schema...\n";
        
        // Read and execute complete schema
        $schemaFile = __DIR__ . '/complete_admin_schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            $pdo->exec($schema);
            echo "Complete schema executed successfully!\n";
        } else {
            echo "Schema file not found!\n";
            exit(1);
        }
    } else {
        echo "Users table exists. Checking structure...\n";
        
        // Get current table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Current users table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
        }
        
        // Check for required columns
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['id', 'username', 'email', 'status', 'created_at'];
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (!empty($missingColumns)) {
            echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
            echo "The table structure needs to be updated. Please run the complete schema.\n";
        } else {
            echo "All required columns are present.\n";
        }
        
        // Check if last_login column exists (this shouldn't exist)
        if (in_array('last_login', $columnNames)) {
            echo "WARNING: Found 'last_login' column that shouldn't exist!\n";
            echo "Consider dropping this column: ALTER TABLE users DROP COLUMN last_login;\n";
        }
    }
    
    // Test a simple query
    echo "Testing users query...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users table has {$result['count']} records.\n";
    
    // Test the actual query from users_fixed.php
    echo "Testing complex users query...\n";
    $query = "
        SELECT u.id, u.username, u.email, 
               COALESCE(u.full_name, u.username) as full_name,
               COALESCE(u.phone, '') as phone,
               u.status, u.created_at, u.updated_at,
               COALESCE(u.blocked_at, '') as blocked_at,
               COALESCE(ord.total_orders, 0) as total_orders,
               COALESCE(ord.total_spent, 0) as total_spent
        FROM users u
        LEFT JOIN (SELECT user_id, COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders GROUP BY user_id) ord 
        ON u.id = ord.user_id
        ORDER BY u.created_at DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query executed successfully! Found " . count($users) . " users.\n";
    
    if (!empty($users)) {
        echo "Sample user data:\n";
        foreach ($users as $user) {
            echo "- {$user['username']} ({$user['email']}) - Status: {$user['status']}\n";
        }
    }
    
    echo "Database verification completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>