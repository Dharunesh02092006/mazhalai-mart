<?php
// Orders API for Mazhalai Mart - Updated for User-Specific Data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../auth/session_config.php';

class OrderAPI {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function createOrder($orderData) {
        try {
            // Check if user is logged in
            startSecureSession();
            $userId = getCurrentUserId();
            
            $this->conn->beginTransaction();
            
            // Prepare product names and quantities for the main order record
            $productNames = [];
            $quantities = [];
            
            foreach ($orderData['items'] as $item) {
                $productNames[] = $item['name'];
                $quantities[] = $item['quantity'];
            }
            
            // Convert arrays to comma-separated strings
            $productNamesStr = implode(', ', $productNames);
            $quantitiesStr = implode(', ', $quantities);
            
            // Insert order with user_id
            $orderQuery = "INSERT INTO orders (order_id, user_id, customer_name, customer_email, customer_phone, 
                          shipping_address, product_names, quantities, payment_method, subtotal, 
                          delivery_charges, total_amount, status) 
                          VALUES (:order_id, :user_id, :customer_name, :customer_email, :customer_phone, 
                          :shipping_address, :product_names, :quantities, :payment_method, :subtotal, 
                          :delivery_charges, :total_amount, 'confirmed')";
            
            $stmt = $this->conn->prepare($orderQuery);
            $stmt->bindParam(':order_id', $orderData['order_id']);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':customer_name', $orderData['customer_name']);
            $stmt->bindParam(':customer_email', $orderData['customer_email']);
            $stmt->bindParam(':customer_phone', $orderData['customer_phone']);
            $stmt->bindParam(':shipping_address', $orderData['shipping_address']);
            $stmt->bindParam(':product_names', $productNamesStr);
            $stmt->bindParam(':quantities', $quantitiesStr);
            $stmt->bindParam(':payment_method', $orderData['payment_method']);
            $stmt->bindParam(':subtotal', $orderData['subtotal']);
            $stmt->bindParam(':delivery_charges', $orderData['delivery_charges']);
            $stmt->bindParam(':total_amount', $orderData['total_amount']);
            
            $stmt->execute();
            $orderId = $this->conn->lastInsertId();
            
            // Insert order items into separate table
            $itemQuery = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total) 
                         VALUES (:order_id, :product_id, :product_name, :quantity, :price, :total)";
            
            $itemStmt = $this->conn->prepare($itemQuery);
            
            foreach ($orderData['items'] as $item) {
                $itemStmt->bindParam(':order_id', $orderId);
                $itemStmt->bindParam(':product_id', $item['id']);
                $itemStmt->bindParam(':product_name', $item['name']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':price', $item['price']);
                $itemTotal = $item['price'] * $item['quantity'];
                $itemStmt->bindParam(':total', $itemTotal);
                $itemStmt->execute();
            }
            
            // Clear user cart if logged in
            if ($userId) {
                $clearCartQuery = "DELETE FROM user_cart WHERE user_id = :user_id";
                $clearCartStmt = $this->conn->prepare($clearCartQuery);
                $clearCartStmt->bindParam(':user_id', $userId);
                $clearCartStmt->execute();
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderData['order_id']
            ];
            
        } catch(Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage()
            ];
        }
    }
    
    public function getAllOrders() {
        try {
            // Check if user is logged in for user-specific orders
            startSecureSession();
            $userId = getCurrentUserId();
            
            if ($userId) {
                // Return only user's orders
                return $this->getUserOrders($userId);
            }
            
            // Return all orders (for admin or non-logged in users)
            $query = "SELECT o.id, o.order_id, o.user_id, o.customer_name, o.customer_email, o.customer_phone, 
                     o.shipping_address, o.product_names, o.quantities, o.payment_method, o.subtotal, 
                     o.delivery_charges, o.total_amount, o.status, o.created_at, o.updated_at
                     FROM orders o
                     ORDER BY o.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get order items for each order from the separate table
            foreach ($orders as &$order) {
                $itemsQuery = "SELECT oi.product_id, oi.product_name, oi.quantity, oi.price, oi.total
                              FROM order_items oi 
                              WHERE oi.order_id = :order_id";
                $itemsStmt = $this->conn->prepare($itemsQuery);
                $itemsStmt->bindParam(':order_id', $order['id']);
                $itemsStmt->execute();
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add image paths to items
                foreach ($items as &$item) {
                    $item['image'] = $this->getImagePathByName($item['product_name']);
                    $item['id'] = $item['product_id'];
                    $item['name'] = $item['product_name'];
                }
                
                $order['items'] = $items;
                $order['order_date'] = $order['created_at'];
            }
            
            return [
                'success' => true,
                'orders' => $orders,
                'count' => count($orders)
            ];
            
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching orders: ' . $e->getMessage()
            ];
        }
    }
    
    public function getUserOrders($userId) {
        try {
            $query = "SELECT o.id, o.order_id, o.customer_name, o.customer_email, o.customer_phone, 
                     o.shipping_address, o.product_names, o.quantities, o.payment_method, o.subtotal, 
                     o.delivery_charges, o.total_amount, o.status, o.created_at, o.updated_at
                     FROM orders o
                     WHERE o.user_id = :user_id 
                     ORDER BY o.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get order items for each order from the separate table
            foreach ($orders as &$order) {
                $itemsQuery = "SELECT oi.product_id, oi.product_name, oi.quantity, oi.price, oi.total
                              FROM order_items oi 
                              WHERE oi.order_id = :order_id";
                $itemsStmt = $this->conn->prepare($itemsQuery);
                $itemsStmt->bindParam(':order_id', $order['id']);
                $itemsStmt->execute();
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add image paths to items
                foreach ($items as &$item) {
                    $item['image'] = $this->getImagePathByName($item['product_name']);
                    $item['id'] = $item['product_id'];
                    $item['name'] = $item['product_name'];
                }
                
                $order['items'] = $items;
                $order['order_date'] = $order['created_at'];
            }
            
            return [
                'success' => true,
                'orders' => $orders,
                'count' => count($orders)
            ];
            
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching user orders: ' . $e->getMessage()
            ];
        }
    }
    
    private function getImagePathByName($productName) {
        $imageMap = [
            'Baby Lotion' => 'images/lotion.webp',
            'Baby Shampoo' => 'images/shampoo.webp',
            'Baby Diapers' => 'images/diapers.webp',
            'Nutrition Supplement' => 'images/nutrition.webp',
            'Baby Nutrition' => 'images/nutrition.webp',
            'Bathing Products' => 'images/bathing products.jpg',
            'Feeding Essentials' => 'images/feeding.webp'
        ];
        
        return isset($imageMap[$productName]) ? $imageMap[$productName] : 'images/placeholder.jpg';
    }
    
    public function getOrdersByEmail($email) {
        try {
            $query = "SELECT o.id, o.order_id, o.customer_name, o.customer_email, o.customer_phone, 
                     o.shipping_address, o.product_names, o.quantities, o.payment_method, o.subtotal, 
                     o.delivery_charges, o.total_amount, o.status, o.created_at, o.updated_at
                     FROM orders o
                     WHERE o.customer_email = :email 
                     ORDER BY o.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get order items for each order from the separate table
            foreach ($orders as &$order) {
                $itemsQuery = "SELECT oi.product_id, oi.product_name, oi.quantity, oi.price, oi.total
                              FROM order_items oi 
                              WHERE oi.order_id = :order_id";
                $itemsStmt = $this->conn->prepare($itemsQuery);
                $itemsStmt->bindParam(':order_id', $order['id']);
                $itemsStmt->execute();
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add image paths to items
                foreach ($items as &$item) {
                    $item['image'] = $this->getImagePathByName($item['product_name']);
                    $item['id'] = $item['product_id'];
                    $item['name'] = $item['product_name'];
                }
                
                $order['items'] = $items;
                $order['order_date'] = $order['created_at'];
            }
            
            return [
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ];
            
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching orders: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateOrderStatus($orderId, $status) {
        try {
            $query = "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE order_id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':order_id', $orderId);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Order status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update order status'
                ];
            }
            
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating order: ' . $e->getMessage()
            ];
        }
    }
}

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];
$api = new OrderAPI();

switch($method) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action']) && $input['action'] === 'create_order') {
            $result = $api->createOrder($input['data']);
        } elseif (isset($input['action']) && $input['action'] === 'cancel_order') {
            $result = $api->updateOrderStatus($input['order_id'], 'cancelled');
        } else {
            $result = ['success' => false, 'message' => 'Invalid action'];
        }
        break;
    
    case 'GET':
        if (isset($_GET['email'])) {
            $result = $api->getOrdersByEmail($_GET['email']);
        } else {
            // Get all orders if no email specified
            $result = $api->getAllOrders();
        }
        break;
    
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['order_id']) && isset($input['status'])) {
            $result = $api->updateOrderStatus($input['order_id'], $input['status']);
        } else {
            $result = ['success' => false, 'message' => 'Order ID and status required'];
        }
        break;
    
    default:
        $result = ['success' => false, 'message' => 'Method not allowed'];
        break;
}

echo json_encode($result);
?>