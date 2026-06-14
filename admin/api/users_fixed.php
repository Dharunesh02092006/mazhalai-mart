<?php
// Clear any potential caching issues
if (function_exists('opcache_reset')) { 
    opcache_reset(); 
}
if (function_exists('apcu_clear_cache')) { 
    apcu_clear_cache(); 
}

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=dummy_host;dbname=dummy_database;charset=utf8", 'dummy_user', 'dummy_password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, let's verify the table structure
    $tableCheckStmt = $pdo->prepare("SHOW COLUMNS FROM users");
    $tableCheckStmt->execute();
    $columns = $tableCheckStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check if users table exists and has basic required columns
    $requiredColumns = ['id', 'username', 'email', 'created_at'];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            throw new Exception("Missing required column: $col in users table");
        }
    }
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Build dynamic SELECT clause based on available columns
    $selectFields = ['u.id', 'u.username', 'u.email', 'u.created_at'];
    
    if (in_array('full_name', $columns)) {
        $selectFields[] = 'COALESCE(u.full_name, u.username) as full_name';
    } else {
        $selectFields[] = 'u.username as full_name';
    }
    
    if (in_array('phone', $columns)) {
        $selectFields[] = 'COALESCE(u.phone, \'\') as phone';
    } else {
        $selectFields[] = '\'\' as phone';
    }
    
    if (in_array('status', $columns)) {
        $selectFields[] = 'u.status';
    } else {
        $selectFields[] = '\'active\' as status';
    }
    
    if (in_array('updated_at', $columns)) {
        $selectFields[] = 'u.updated_at';
    } else {
        $selectFields[] = 'u.created_at as updated_at';
    }
    
    if (in_array('blocked_at', $columns)) {
        $selectFields[] = 'COALESCE(u.blocked_at, \'\') as blocked_at';
    } else {
        $selectFields[] = '\'\' as blocked_at';
    }
    
    $selectClause = implode(', ', $selectFields);
    
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        if (in_array('full_name', $columns)) {
            $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ? OR COALESCE(u.full_name, u.username) LIKE ?)";
        } else {
            $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
        }
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        if (in_array('full_name', $columns)) {
            $params[] = "%{$search}%";
        }
    }
    
    if (!empty($status) && in_array('status', $columns) && $status === 'active') {
        $whereConditions[] = "u.status = ?";
        $params[] = $status;
    }
    
    if (!empty($dateFrom)) {
        $whereConditions[] = "DATE(u.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $whereConditions[] = "DATE(u.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $countQuery = "SELECT COUNT(*) as cnt FROM users u $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $query = "
        SELECT $selectClause,
               COALESCE(ord.total_orders, 0) as total_orders,
               COALESCE(ord.total_spent, 0) as total_spent
        FROM users u
        LEFT JOIN (SELECT user_id, COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders GROUP BY user_id) ord 
        ON u.id = ord.user_id
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = ceil($totalUsers / $limit);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => (int)$totalUsers,
        'pagination' => ['current_page' => $page, 'total_pages' => $totalPages, 'per_page' => $limit, 'total_items' => (int)$totalUsers]
    ]);
} catch (Exception $e) {
    error_log("Users API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Log additional debug information
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;
            error_log("Users table exists: " . ($tableExists ? 'Yes' : 'No'));
            
            if ($tableExists) {
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                error_log("Users table columns: " . implode(', ', $columns));
            }
        } catch (Exception $debugError) {
            error_log("Debug error: " . $debugError->getMessage());
        }
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>