<?php
/**
 * Public Products API for Mazhalai Mart
 * Returns active products for user-facing pages
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    
    // Get filters
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 12; // 12 per page for user view
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions - always filter for active status
    $whereConditions = ["status = 'active'"];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(name LIKE ? OR description LIKE ? OR category LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $whereConditions[] = "category = ?";
        $params[] = $category;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products WHERE " . implode(" AND ", $whereConditions);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
    $totalProducts = $totalRow['total'] ?? 0;
    $totalPages = ceil($totalProducts / $limit);
    
    // Get paginated products
    $query = "SELECT id, name, description, price, stock_quantity, category, image_path, created_at 
              FROM products 
              WHERE " . implode(" AND ", $whereConditions) . " 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $executeParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($executeParams);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_products' => $totalProducts,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching products',
        'error' => $e->getMessage()
    ]);
}
?>
