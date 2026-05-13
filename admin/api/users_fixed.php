<?php
/**
 * Fixed Users API for Mazhalai Mart Admin Panel
 */

session_start();

// Set content type
header('Content-Type: application/json');

// Enable error reporting but don't display errors in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Simple session check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'mazhalai_mart';
    $username = 'root';
    $password = 'dharun2403@';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get filters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ? OR COALESCE(u.full_name, u.username) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $whereConditions[] = "COALESCE(u.status, 'active') = ?";
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
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM users u $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    
    // Get users with order statistics
    $query = "
        SELECT u.id, u.username, u.email, 
               COALESCE(u.full_name, u.username) as full_name,
               COALESCE(u.phone, '') as phone,
               COALESCE(u.status, 'active') as status,
               u.created_at, u.updated_at,
               COALESCE(u.last_login, '') as last_login,
               COALESCE(u.blocked_at, '') as blocked_at,
               COUNT(o.id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id
        $whereClause 
        GROUP BY u.id, u.username, u.email, u.full_name, u.phone, u.status, u.created_at, u.updated_at, u.last_login, u.blocked_at
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination
    $totalPages = ceil($totalUsers / $limit);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => (int)$totalUsers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $limit,
            'total_items' => (int)$totalUsers
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Users API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>