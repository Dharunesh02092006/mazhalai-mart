<?php
/**
 * Add Product API for Mazhalai Mart Admin Panel
 */

require_once '../admin_check.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) {
        throw new Exception('Product name is required');
    }
    
    if ($price <= 0) {
        throw new Exception('Price must be greater than 0');
    }
    
    if ($stockQuantity < 0) {
        throw new Exception('Stock quantity cannot be negative');
    }
    
    if (empty($category)) {
        throw new Exception('Category is required');
    }
    
    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['image']);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
        } else {
            throw new Exception($uploadResult['error']);
        }
    }
    
    // Insert product
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, stock_quantity, category, image_path, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $description, $price, $stockQuantity, $category, $imagePath, $status]);
    
    $productId = $pdo->lastInsertId();
    
    // Log activity
    logAdminActivity('create', 'product', $productId, "Created product: $name");
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    error_log("Add product error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleImageUpload($file) {
    $uploadDir = '../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size too large. Maximum 5MB allowed.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'uploads/products/' . $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
}
?>