<?php
/**
 * Products API for Mazhalai Mart
 * Fetches all active products from the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Fetch all active products
    $query = "SELECT id, name, description, price, image_path, category, stock_quantity FROM products WHERE status = 'active' ORDER BY created_at DESC";
    
    $stmt = $pdo->query($query);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rename image_path to image for consistency with frontend
    foreach ($products as &$product) {
        // Ensure image path is properly formatted
        $imagePath = $product['image_path'] ?? '';
        if (!$imagePath || $imagePath === 'null') {
            $imagePath = 'images/placeholder.jpg';
        } elseif (strpos($imagePath, 'uploads/') === 0) {
            // Admin uploads need admin/ prefix
            $imagePath = 'admin/' . $imagePath;
        } elseif (strpos($imagePath, 'admin/uploads/') === 0) {
            // Already has correct prefix
            $imagePath = $imagePath;
        } elseif (strpos($imagePath, 'images/') === 0) {
            // Already has correct prefix
            $imagePath = $imagePath;
        } elseif (strpos($imagePath, 'admin/') === 0) {
            // Already has correct prefix
            $imagePath = $imagePath;
        } else {
            // Unknown format - try adding images/ prefix for backward compatibility
            $imagePath = 'images/' . $imagePath;
        }
        $product['image'] = $imagePath;
        unset($product['image_path']);
    }
    
    // Return success response with products
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching products: ' . $e->getMessage()
    ]);
}
?>

