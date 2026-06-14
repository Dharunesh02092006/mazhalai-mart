// Orders page functionality for Mazhalai Mart

// Function to check authentication for orders page
function checkAuthenticationForOrdersPage() {
    fetch('auth/check_login.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.loggedIn) {
            // User is not logged in, redirect to login
            alert('Please login or sign up to view your orders!');
            window.location.href = 'login.html';
        } else {
            // User is authenticated, show the orders content and load orders
            document.getElementById('orders-content').style.display = 'block';
            loadOrders();
        }
    })
    .catch(error => {
        console.error('Auth check error:', error);
        // On error, redirect to login for safety
        window.location.href = 'login.html';
    });
}

$(document).ready(function() {
    // Check if user is logged in before accessing orders
    checkAuthenticationForOrdersPage();
    
    // Initialize cart count display
    if (typeof loadCartCount === 'function') {
        loadCartCount();
    }
    
    // Reorder button click handler
    $(document).on('click', '.btn-reorder', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        reorderItems(orderId);
    });
});

function loadOrders() {
    // Only fetch from database - no localStorage
    fetchOrdersFromDatabase().then(dbOrders => {
        if (dbOrders.length === 0) {
            showEmptyOrdersState();
        } else {
            showOrdersWithData(dbOrders);
        }
    }).catch(error => {
        console.error('Database fetch failed:', error);
        showEmptyOrdersState();
    });
}

function showEmptyOrdersState() {
    // Hide the regular orders content
    document.getElementById('orders-content').style.display = 'none';
    
    // Show the cart-like empty orders layout
    document.getElementById('empty-orders-content').style.display = 'block';
}

function showOrdersWithData(orders) {
    // Show regular orders content
    document.getElementById('orders-content').style.display = 'block';
    // Hide empty orders content
    document.getElementById('empty-orders-content').style.display = 'none';
    
    displayOrders(orders);
}

function fetchOrdersFromDatabase() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api/user_orders.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.orders) {
                    resolve(response.orders);
                } else {
                    resolve([]);
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

function displayOrders(orders) {
    // Make sure we're showing the correct content sections
    document.getElementById('orders-content').style.display = 'block';
    document.getElementById('empty-orders-content').style.display = 'none';
    $('#no-orders').hide();
    
    let ordersHTML = '';
    
    // Sort orders by date (newest first)
    orders.sort((a, b) => new Date(b.date || b.order_date) - new Date(a.date || a.order_date));
    
    orders.forEach(order => {
        ordersHTML += generateOrderHTML(order);
    });
    
    $('#orders-container').html(ordersHTML);
}

function generateOrderHTML(order) {
    const orderId = order.order_id || order.id;
    const orderDate = formatDate(order.date || order.order_date || new Date().toISOString());
    const orderStatus = order.status || 'confirmed';
    const orderTotal = order.total || order.total_amount;
    const orderItems = order.items || [];
    
    let itemsHTML = '';
    orderItems.forEach(item => {
        const itemTotal = (item.price * item.quantity);
        const productName = item.product_name || item.name || 'Unknown Product';
        
        // Fix image path - ensure it's correct from database
        let imagePath = item.image || item.product_image || item.image_path || 'images/placeholder.jpg';
        
        // Validate and fix image path
        if (!imagePath || imagePath === 'undefined' || imagePath === '' || imagePath === null || imagePath === 'null') {
            imagePath = 'images/placeholder.jpg';
        } else if (imagePath.startsWith('uploads/')) {
            imagePath = 'admin/' + imagePath;
        } else if (!imagePath.startsWith('images/') && !imagePath.startsWith('admin/') && !imagePath.startsWith('http')) {
            imagePath = 'images/' + imagePath;
        }
        
        itemsHTML += `
            <div class="order-item">
                <img src="${imagePath}" alt="${productName}" title="${productName}" onerror="this.src='images/placeholder.jpg'">
                <div class="order-item-details">
                    <h4>${productName}</h4>
                    <p>Qty: ${item.quantity}</p>
                    <p class="order-price">₹${itemTotal}</p>
                </div>
            </div>
        `;
    });
    
    const statusClass = getStatusClass(orderStatus);
    const statusText = getStatusText(orderStatus);
    
    // Determine which buttons to show based on order status
    let actionButtons = '';
    if (orderStatus === 'delivered' || orderStatus === 'cancelled') {
        actionButtons = `<a href="#" class="btn btn-reorder" data-order-id="${orderId}">Reorder</a>`;
    } else if (orderStatus === 'confirmed' || orderStatus === 'processing') {
        actionButtons = `
            <a href="#" class="btn btn-cancel" data-order-id="${orderId}" onclick="cancelOrder('${orderId}')">Cancel Order</a>
        `;
    }
    
    return `
        <div class="order-card card">
            <div class="order-header">
                <div class="order-meta">
                    <span class="order-label">Order #</span>
                    <span class="order-id">${orderId}</span>
                </div>
                <div class="order-meta">
                    <span class="order-label">Placed on:</span>
                    <span>${orderDate}</span>
                </div>
                <span class="order-status ${statusClass}">${statusText}</span>
            </div>
            <div class="order-items">
                ${itemsHTML}
            </div>
            <div class="order-footer">
                <p class="order-total">Total: <strong>₹${orderTotal}</strong></p>
                <div class="order-actions">
                    ${actionButtons}
                </div>
            </div>
        </div>
    `;
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'delivered': return 'delivered';
        case 'shipped': 
        case 'in-transit': 
        case 'out-for-delivery': return 'in-transit';
        case 'processing': return 'processing';
        case 'cancelled': return 'cancelled';
        case 'confirmed':
        default: return 'confirmed';
    }
}

function getStatusText(status) {
    switch(status.toLowerCase()) {
        case 'delivered': return 'Delivered';
        case 'shipped': return 'Shipped';
        case 'in-transit': return 'In Transit';
        case 'out-for-delivery': return 'Out for Delivery';
        case 'processing': return 'Processing';
        case 'cancelled': return 'Cancelled';
        case 'confirmed':
        default: return 'Confirmed';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('en-IN', options);
}

function showNoOrders() {
    $('#orders-container').hide();
    $('#no-orders').show();
}

function reorderItems(orderId) {
    // Find the order from database only
    fetchOrdersFromDatabase().then(dbOrders => {
        const order = dbOrders.find(o => (o.order_id || o.id) === orderId);
        
        if (order && order.items) {
            // Store order items temporarily for checkout (not in cart)
            const reorderItems = order.items.map(item => ({
                id: item.id,
                name: item.name,
                price: item.price,
                image: item.image,
                quantity: item.quantity
            }));
            
            localStorage.setItem('buy_now_item', JSON.stringify(reorderItems));
            
            // Redirect to checkout immediately without adding to cart
            window.location.href = 'checkout.html';
        } else {
            console.error('Order not found in database');
        }
    }).catch(error => {
        console.error('Failed to fetch order for reorder:', error);
    });
}

function cancelOrder(orderId) {
    // Show loading state immediately
    const cancelBtn = $(`.btn-cancel[data-order-id="${orderId}"]`);
    const originalText = cancelBtn.text();
    cancelBtn.text('Cancelling...').prop('disabled', true);
    
    // Try to cancel in database first
    $.ajax({
        url: 'api/orders.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'cancel_order',
            order_id: orderId
        }),
        success: function(response) {
            if (response.success) {
                // Update local storage as well
                updateLocalOrderStatus(orderId, 'cancelled');
                
                // Reload orders to show updated status
                loadOrders();
            } else {
                cancelBtn.text(originalText).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.log('Database cancel failed, updating locally:', error);
            
            // Fallback to local storage update
            updateLocalOrderStatus(orderId, 'cancelled');
            loadOrders();
        }
    });
}

function updateLocalOrderStatus(orderId, newStatus) {
    // Since we're only using database, this function is no longer needed
    // Order status updates are handled by the database API
    console.log(`Order ${orderId} status updated to ${newStatus} in database`);
}

function getImagePathByName(productName) {
    // Map product names to image paths for fallback
    const imageMap = {
        'baby lotion': 'images/lotion.webp',
        'baby shampoo': 'images/shampoo.webp',
        'baby diapers': 'images/diapers.webp',
        'baby nutrition': 'images/nutrition.webp',
        'bathing products': 'images/bathing products.jpg',
        'feeding essentials': 'images/feeding.webp',
        'baby body wash': 'images/baby body wash.webp'
    };
    
    const normalizedName = productName.toLowerCase().trim();
    return imageMap[normalizedName] || 'images/placeholder.jpg';
}

// Helper function to get order status badge color
function getStatusBadgeStyle(status) {
    switch(status.toLowerCase()) {
        case 'delivered': 
            return 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;';
        case 'shipped':
        case 'in-transit':
        case 'out-for-delivery':
            return 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;';
        case 'processing':
            return 'background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;';
        case 'cancelled':
            return 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;';
        case 'confirmed':
        default:
            return 'background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db;';
    }
}