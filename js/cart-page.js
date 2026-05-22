// Cart page functionality for Mazhalai Mart
// Support for both user-based (database) and guest (localStorage) carts

let useUserCart = false; // Flag to track if using user-based cart

$(document).ready(function() {
    // Check user authentication and initialize appropriate cart system
    checkUserAuthAndLoadCart();
    
    // Listen for cart updates from user-cart.js
    window.addEventListener('cartUpdated', function() {
        loadCartPage();
    });
    
    // Quantity change handler
    $(document).on('change', '.quantity-select', function() {
        const productId = $(this).data('product-id');
        const newQuantity = parseInt($(this).val());
        
        if (useUserCart) {
            updateCartQuantity(productId, newQuantity);
        } else {
            updateQuantity(productId, newQuantity);
        }
        
        loadCartPage(); // Refresh the cart display
    });
    
    // Remove item handler
    $(document).on('click', '.delete-link', function(e) {
        e.preventDefault();
        const productId = $(this).data('product-id');
        
        if (useUserCart) {
            removeFromUserCart(productId);
        } else {
            removeFromCart(productId);
        }
        
        loadCartPage(); // Refresh the cart display
    });
});

function checkUserAuthAndLoadCart() {
    // Check if user is authenticated
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            // User is logged in - use API-based cart
            useUserCart = true;
            loadUserCartFromAPI();
        } else {
            // User not logged in - use localStorage
            useUserCart = false;
            loadCartPageFromStorage();
        }
    })
    .catch(error => {
        console.error('Auth check failed:', error);
        // Fall back to localStorage
        useUserCart = false;
        loadCartPageFromStorage();
    });
}

function loadCartPage() {
    if (useUserCart) {
        loadUserCartFromAPI();
    } else {
        loadCartPageFromStorage();
    }
}

function loadUserCartFromAPI() {
    fetch('api/user_cart.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.cart_items.length > 0) {
            displayAPICartItems(data.cart_items);
            displayAPISummary(data.summary);
        } else {
            displayEmptyCart();
        }
    })
    .catch(error => {
        console.error('Failed to load user cart:', error);
        displayEmptyCart();
    });
}

function loadCartPageFromStorage() {
    const cart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
    
    if (cart.length === 0) {
        displayEmptyCart();
        return;
    }
    
    displayCartItems(cart);
    displayCartSummary(cart);

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

function displayAPICartItems(cartItems) {
    let cartHTML = '';
    
    cartItems.forEach(item => {
        // Handle both possible image path formats
        let imagePath = item.product_image;
        if (imagePath && imagePath.startsWith('admin/')) {
            imagePath = '../' + imagePath;
        } else if (imagePath && !imagePath.startsWith('images/')) {
            imagePath = 'images/' + imagePath;
        }
        
        cartHTML += `
            <div class="cart-item">
                <img src="${imagePath}" alt="${item.product_name}" onerror="this.src='images/placeholder-product.png'">
                <div class="item-details">
                    <h3>${item.product_name}</h3>
                    <p class="stock">In Stock</p>
                    <p class="price">₹${item.product_price}</p>
                    <div class="item-actions">
                        <label>Qty:</label>
                        <select class="quantity-select" data-product-id="${item.product_id}">
                            ${generateQuantityOptions(item.quantity)}
                        </select>
                        <a href="#" class="delete-link" data-product-id="${item.product_id}" data-product-name="${item.product_name}">Delete</a>
                    </div>
                </div>
                <div class="item-total">
                    <p class="item-price">₹${(item.product_price * item.quantity).toFixed(2)}</p>
                </div>
            </div>
        `;
    });
    
    $('#cart-items').html(cartHTML);
}

function displayAPISummary(summary) {
    const summaryHTML = `
        <h3 class="summary-heading">Price Details</h3>
        <hr>
        <div class="summary-line">
            <span>Subtotal (${summary.total_items} item${summary.total_items > 1 ? 's' : ''})</span>
            <span>₹${summary.subtotal.toFixed(2)}</span>
        </div>
        <div class="summary-line">
            <span>Delivery Charges</span>
            <span>₹${summary.delivery_charges}</span>
        </div>
        <hr>
        <div class="summary-line total">
            <strong>Total Amount</strong>
            <strong>₹${summary.total.toFixed(2)}</strong>
        </div>
        <a href="checkout.html">
            <button class="checkout-btn">Proceed to Checkout (₹${summary.total.toFixed(2)})</button>
        </a>
        <div class="continue-shopping">
            <a href="products.html">← Continue Shopping</a>
        </div>
    `;
    
    $('#cart-summary').html(summaryHTML);
}

function displayCartItems(cart) {
    let cartHTML = '';
    
    cart.forEach(item => {
        cartHTML += `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}">
                <div class="item-details">
                    <h3>${item.name}</h3>
                    <p class="stock">In Stock</p>
                    <p class="price">₹${item.price}</p>
                    <div class="item-actions">
                        <label>Qty:</label>
                        <select class="quantity-select" data-product-id="${item.id}">
                            ${generateQuantityOptions(item.quantity)}
                        </select>
                        <a href="#" class="delete-link" data-product-id="${item.id}" data-product-name="${item.name}">Delete</a>
                    </div>
                </div>
                <div class="item-total">
                    <p class="item-price">₹${item.price * item.quantity}</p>
                </div>
            </div>
        `;
    });
    
    $('#cart-items').html(cartHTML);
}

function displayCartSummary(cart) {
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
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