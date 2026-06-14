<?php
/**
 * Edit Product API for Mazhalai Mart Admin Panel
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Get form data
    $productId = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        exit;
    }
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
        exit;
    }
    
    if ($stock_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Stock quantity cannot be negative']);
        exit;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit;
    }
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $imagePath = $product['image_path']; // Keep existing image by default
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/products/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['image']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WebP are allowed.']);
            exit;
        }
        
        // Validate file size (5MB max)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
            exit;
        }
        
        // Generate unique filename
        $filename = 'product_' . $productId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'admin/uploads/products/' . $filename;
            
            // Delete old image if it exists and is different
            if ($product['image_path'] && $product['image_path'] !== $imagePath && file_exists('../../' . $product['image_path'])) {
                unlink('../../' . $product['image_path']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }
    
    // Update product
    $stmt = $pdo->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, stock_quantity = ?, category = ?, status = ?, image_path = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $name,
        $description,
        $price,
        $stock_quantity,
        $category,
        $status,
        $imagePath,
        $productId
    ]);
    
    // Log activity
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $_SESSION['admin_id'],
            'update',
            'product',
            $productId,
            "Updated product: $name",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Ignore logging errors
        error_log("Admin activity log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    error_log("Edit product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update product: ' . $e->getMessage()
    ]);
}
?>