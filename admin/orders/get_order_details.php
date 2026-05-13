<?php
/**
 * Get Order Details for Mazhalai Mart Admin Panel
 */

session_start();

// Simple session check
if (!isset($_SESSION['admin_id'])) {
    echo '<p style="color: red;">Authentication required. Please login.</p>';
    exit;
}

$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    echo '<p style="color: red;">Invalid order ID.</p>';
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Get order details
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.username, u.email as user_email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo '<p style="color: red;">Order not found.</p>';
        exit;
    }
    
    // Get order items - parse from product_names and quantities columns
    $productNames = explode(', ', $order['product_names']);
    $quantities = explode(', ', $order['quantities']);
    
    // Create items array from the parsed data
    $items = [];
    for ($i = 0; $i < count($productNames); $i++) {
        if (isset($productNames[$i]) && isset($quantities[$i])) {
            // Try to get product price from products table
            $productStmt = $pdo->prepare("SELECT price FROM products WHERE name = ? LIMIT 1");
            $productStmt->execute([$productNames[$i]]);
            $productPrice = $productStmt->fetchColumn();
            
            if (!$productPrice) {
                $productPrice = 0; // Default price if product not found
            }
            
            $quantity = intval($quantities[$i]);
            $total = $productPrice * $quantity;
            
            $items[] = [
                'product_name' => $productNames[$i],
                'quantity' => $quantity,
                'price' => $productPrice,
                'total' => $total
            ];
        }
    }
    
    // Parse shipping address - it might be stored as plain text or JSON
    $shippingAddress = null;
    if (!empty($order['shipping_address'])) {
        $decoded = json_decode($order['shipping_address'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $shippingAddress = $decoded;
        } else {
            // If it's not JSON, treat as plain text
            $shippingAddress = ['address' => $order['shipping_address']];
        }
    }
    
    ?>
    <div style="font-family: 'Quicksand', sans-serif;">
        <!-- Order Information -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 15px 0; color: #4B2E2B;">Order Information</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?><br>
                    <strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span><br>
                    <strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?><br>
                    <strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                </div>
                <div>
                    <strong>Subtotal:</strong> ₹<?php echo number_format($order['subtotal'], 2); ?><br>
                    <strong>Delivery Charges:</strong> ₹<?php echo number_format($order['delivery_charges'], 2); ?><br>
                    <strong>Total Amount:</strong> <span style="color: #27ae60; font-weight: bold;">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 15px 0; color: #4B2E2B;">Customer Information</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?>
                </div>
                <div>
                    <?php if ($order['username']): ?>
                        <strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?><br>
                        <strong>Account Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?><br>
                        <span style="color: #27ae60;">✓ Registered User</span>
                    <?php else: ?>
                        <span style="color: #999;">Guest Order</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Shipping Address -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 15px 0; color: #4B2E2B;">Shipping Address</h4>
            <div>
                <?php if ($shippingAddress): ?>
                    <?php if (is_array($shippingAddress)): ?>
                        <?php if (isset($shippingAddress['address'])): ?>
                            <?php echo htmlspecialchars($shippingAddress['address']); ?><br>
                        <?php endif; ?>
                        <?php if (isset($shippingAddress['city']) && isset($shippingAddress['state'])): ?>
                            <?php echo htmlspecialchars($shippingAddress['city']); ?>, <?php echo htmlspecialchars($shippingAddress['state']); ?>
                        <?php endif; ?>
                        <?php if (isset($shippingAddress['pincode'])): ?>
                            - <?php echo htmlspecialchars($shippingAddress['pincode']); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($shippingAddress); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo htmlspecialchars($order['shipping_address']); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: #4B2E2B;">Order Items</h4>
            <?php if (empty($items)): ?>
                <p>No items found for this order.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #4B2E2B; color: white;">
                            <th style="padding: 10px; text-align: left;">Product</th>
                            <th style="padding: 10px; text-align: center;">Quantity</th>
                            <th style="padding: 10px; text-align: right;">Price</th>
                            <th style="padding: 10px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo number_format($item['quantity']); ?></td>
                                <td style="padding: 10px; text-align: right;">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td style="padding: 10px; text-align: right;">₹<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    
} catch (Exception $e) {
    error_log("Get order details error: " . $e->getMessage());
    echo '<div style="padding: 20px; text-align: center;">';
    echo '<p style="color: red; font-weight: bold;">Failed to load order details.</p>';
    echo '<p style="color: #666; font-size: 0.9em;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="color: #666; font-size: 0.9em;">Please try refreshing the page or contact support if the problem persists.</p>';
    echo '</div>';
}
?>