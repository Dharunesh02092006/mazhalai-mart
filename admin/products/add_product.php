<?php
/**
 * Add Product Page for Mazhalai Mart Admin Panel
 */

require_once '../admin_check.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) {
        $error = 'Product name is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($stock_quantity < 0) {
        $error = 'Stock quantity cannot be negative';
    } elseif (empty($category)) {
        $error = 'Category is required';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['image']);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['path'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            if (empty($error)) {
                // Insert product
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, price, stock_quantity, category, image_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $price, $stock_quantity, $category, $imagePath, $status]);
                
                $productId = $pdo->lastInsertId();
                
                // Log activity
                logAdminActivity('create', 'product', $productId, "Created product: $name");
                
                $success = 'Product added successfully!';
                
                // Clear form data
                $name = $description = $category = '';
                $price = $stock_quantity = 0;
                $status = 'active';
            }
        } catch (Exception $e) {
            error_log("Add product error: " . $e->getMessage());
            $error = 'Failed to add product. Please try again.';
        }
    }
}

// Handle image upload
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
        return ['success' => true, 'path' => 'admin/uploads/products/' . $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
}

$currentAdmin = getCurrentAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Mazhalai Mart Admin</title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../admin-styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h1>Mazhalai Mart</h1>
                <p>Admin Panel</p>
            </div>
            <nav class="admin-nav">
                <a href="../dashboard.php" class="admin-nav-item">
                    <i>📊</i> Dashboard
                </a>
                <a href="view_products.php" class="admin-nav-item active">
                    <i>📦</i> Products
                </a>
                <a href="../orders/view_orders.php" class="admin-nav-item">
                    <i>🛒</i> Orders
                </a>
                <a href="../users/view_users.php" class="admin-nav-item">
                    <i>👥</i> Users
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <h1>Add New Product</h1>
                <div class="admin-user-info">
                    <div class="admin-user-details">
                        <p class="admin-user-name"><?php echo htmlspecialchars($currentAdmin['full_name']); ?></p>
                        <p class="admin-user-role"><?php echo htmlspecialchars($currentAdmin['role']); ?></p>
                    </div>
                    <a href="../logout.php" class="admin-logout-btn">Logout</a>
                </div>
            </header>
            
            <main class="admin-content">
                <div class="page-header">
                    <h2 class="page-title">Add New Product</h2>
                    <a href="view_products.php" class="admin-btn">← Back to Products</a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>"
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Enter product description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₹) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required 
                               value="<?php echo htmlspecialchars($price ?? ''); ?>"
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                               value="<?php echo htmlspecialchars($stock_quantity ?? ''); ?>"
                               placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Skin Care" <?php echo ($category ?? '') === 'Skin Care' ? 'selected' : ''; ?>>Skin Care</option>
                            <option value="Bath & Body" <?php echo ($category ?? '') === 'Bath & Body' ? 'selected' : ''; ?>>Bath & Body</option>
                            <option value="Diapers" <?php echo ($category ?? '') === 'Diapers' ? 'selected' : ''; ?>>Diapers</option>
                            <option value="Nutrition" <?php echo ($category ?? '') === 'Nutrition' ? 'selected' : ''; ?>>Nutrition</option>
                            <option value="Feeding" <?php echo ($category ?? '') === 'Feeding' ? 'selected' : ''; ?>>Feeding</option>
                            <option value="Toys" <?php echo ($category ?? '') === 'Toys' ? 'selected' : ''; ?>>Toys</option>
                            <option value="Clothing" <?php echo ($category ?? '') === 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <div class="file-upload-area" onclick="document.getElementById('image').click()">
                            <div class="upload-icon">📷</div>
                            <p class="upload-text">Click to upload product image</p>
                            <input type="file" id="image" name="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        </div>
                        <img id="image-preview" class="image-preview" style="display: none;">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo ($status ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="admin-btn">Add Product</button>
                        <a href="view_products.php" class="admin-btn admin-btn-danger">Cancel</a>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Update upload area text
                    const uploadText = document.querySelector('.upload-text');
                    uploadText.textContent = 'Image selected: ' + input.files[0].name;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Drag and drop functionality
        const uploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('image');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                previewImage(fileInput);
            }
        });
    </script>
</body>
</html>