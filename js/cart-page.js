// Cart page functionality for Mazhalai Mart

// Function to check authentication for cart page
function checkAuthenticationForPage() {
    fetch('auth/check_login.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.loggedIn) {
            // User is not logged in, redirect to login
            alert('Please login or sign up to access your cart!');
            window.location.href = 'login.html';
        } else {
            // User is authenticated, show the cart content and load cart
            document.getElementById('cart-content').style.display = 'grid';
            loadCartPage();
        }
    })
    .catch(error => {
        console.error('Auth check error:', error);
        // On error, redirect to login for safety
        window.location.href = 'login.html';
    });
}

function loadCartPage() {
    // Load user-based cart from database via API
    fetch('api/user_cart.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.cart_items && data.cart_items.length > 0) {
            displayCartItems(data.cart_items);
            displayCartSummary(data.cart_items);
        } else {
            displayEmptyCart();
        }
    })
    .catch(error => {
        console.error('Error loading cart:', error);
        displayEmptyCart();
    });
}

function displayEmptyCart() {
    $('#cart-items').html(`
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any products to your cart yet.</p>
            <a href="products.html" class="continue-shopping-btn">Continue Shopping</a>
        </div>
    `);
    
    $('#cart-summary').html(`
        <div class="empty-summary">
            <h3>Cart Summary</h3>
            <p>No items in cart</p>
        </div>
    `);
}

function displayCartItems(cart) {
    let cartHTML = '';
    
    cart.forEach(item => {
        // Ensure product_image is properly formatted
        let imageSrc = item.product_image || 'images/placeholder.jpg';
        if (imageSrc === 'undefined' || !imageSrc || imageSrc === '' || imageSrc === 'null') {
            imageSrc = 'images/placeholder.jpg';
        } else if (imageSrc.startsWith('uploads/')) {
            imageSrc = 'admin/' + imageSrc;
        } else if (!imageSrc.startsWith('images/') && !imageSrc.startsWith('admin/') && !imageSrc.startsWith('http')) {
            imageSrc = 'images/' + imageSrc;
        }
        
        cartHTML += `
            <div class="cart-item">
                <img src="${imageSrc}" alt="${item.product_name}" onerror="this.src='images/placeholder.jpg'">
                <div class="item-details">
                    <h3>${item.product_name}</h3>
                    <p class="stock">In Stock</p>
                    <p class="price">₹${item.product_price}</p>
                    <div class="item-actions">
                        <label>Qty:</label>
                        <select class="quantity-select" data-product-id="${item.product_id}">
                            ${generateQuantityOptions(item.quantity)}
                        </select>
                        <a href="#" class="delete-link" data-product-id="${item.product_id}">Delete</a>
                    </div>
                </div>
                <div class="item-total">
                    <p class="item-price">₹${item.product_price * item.quantity}</p>
                </div>
            </div>
        `;
    });
    
    $('#cart-items').html(cartHTML);
}

function displayCartSummary(cart) {
    const subtotal = cart.reduce((total, item) => total + (item.product_price * item.quantity), 0);
    const deliveryCharges = 60; // Always ₹60 delivery charge
    const totalAmount = subtotal + deliveryCharges;
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    
    const summaryHTML = `
        <h3 class="summary-heading">Price Details</h3>
        <hr>
        <div class="summary-line">
            <span>Subtotal (${totalItems} item${totalItems > 1 ? 's' : ''})</span>
            <span>₹${subtotal}</span>
        </div>
        <div class="summary-line">
            <span>Delivery Charges</span>
            <span>₹${deliveryCharges}</span>
        </div>
        <hr>
        <div class="summary-line total">
            <strong>Total Amount</strong>
            <strong>₹${totalAmount}</strong>
        </div>
        <a href="checkout.html">
            <button class="checkout-btn">Proceed to Checkout (₹${totalAmount})</button>
        </a>
        <div class="continue-shopping">
            <a href="products.html">← Continue Shopping</a>
        </div>
    `;
    
    $('#cart-summary').html(summaryHTML);
}

function generateQuantityOptions(currentQuantity) {
    let options = '';
    for (let i = 1; i <= 10; i++) {
        options += `<option value="${i}" ${i === currentQuantity ? 'selected' : ''}>${i}</option>`;
    }
    return options;
}

$(document).ready(function() {
    // Check if user is logged in before accessing cart
    checkAuthenticationForPage();
    
    // Initialize cart count display
    if (typeof loadCartCount === 'function') {
        loadCartCount();
    }
    
    // Quantity change handler
    $(document).on('change', '.quantity-select', function() {
        const productId = $(this).data('product-id');
        const newQuantity = parseInt($(this).val());
        updateQuantity(productId, newQuantity);
        loadCartPage(); // Refresh the cart display
    });
    
    // Remove item handler
    $(document).on('click', '.delete-link', function(e) {
        e.preventDefault();
        const productId = $(this).data('product-id');
        
        removeFromCart(productId);
        loadCartPage(); // Refresh the cart display
    });
});

function updateQuantity(productId, quantity) {
    fetch('api/user_cart.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            loadCartPage(); // Refresh cart display
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
    });
}

function removeFromCart(productId) {
    if (confirm('Remove this item from cart?')) {
        fetch('api/user_cart.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
                loadCartPage(); // Refresh cart display
            }
        })
        .catch(error => {
            console.error('Error removing from cart:', error);
        });
    }
}