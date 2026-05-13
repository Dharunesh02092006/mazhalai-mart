<?php
/**
 * Orders API for Mazhalai Mart Admin Panel
 */

session_start();

header('Content-Type: application/json');

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
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Get filters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $userId = $_GET['user_id'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $whereConditions = [];
    $params = [];
    
    // Filter by specific user if user_id is provided
    if (!empty($userId)) {
        $whereConditions[] = "o.user_id = ?";
        $params[] = $userId;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(o.order_id LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $whereConditions[] = "o.status = ?";
        $params[] = $status;
    }
    
    if (!empty($dateFrom)) {
        $whereConditions[] = "DATE(o.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $whereConditions[] = "DATE(o.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    
    // Get orders
    $query = "
        SELECT o.*, u.username, u.email as user_email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $whereClause 
        ORDER BY o.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination
    $totalPages = ceil($totalOrders / $limit);
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => (int)$totalOrders,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $limit,
            'total_items' => (int)$totalOrders
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Orders API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load orders'
    ]);
}
?>