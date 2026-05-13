<?php
/**
 * Products API for Mazhalai Mart Admin Panel
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
    
    // Check if requesting a single product by ID
    $productId = $_GET['id'] ?? null;
    
    if ($productId) {
        // Get single product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo json_encode([
                'success' => true,
                'products' => [$product],
                'total' => 1
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }
        exit;
    }
    
    // Get filters for multiple products
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    $stock = $_GET['stock'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $whereConditions = [];
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
    
    if (!empty($status)) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
    }
    
    if (!empty($stock)) {
        switch ($stock) {
            case 'low':
                $whereConditions[] = "stock_quantity < 10";
                break;
            case 'medium':
                $whereConditions[] = "stock_quantity BETWEEN 10 AND 50";
                break;
            case 'high':
                $whereConditions[] = "stock_quantity > 50";
                break;
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM products $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetchColumn();
    
    // Get products
    $query = "
        SELECT * FROM products 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination
    $totalPages = ceil($totalProducts / $limit);
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => (int)$totalProducts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $limit,
            'total_items' => (int)$totalProducts
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Products API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load products'
    ]);
}
?>