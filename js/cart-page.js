// Cart page functionality for Mazhalai Mart

$(document).ready(function() {
    loadCartPage();
    
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

function loadCartPage() {
    const cart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
    
    if (cart.length === 0) {
        displayEmptyCart();
        return;
    }
    
    displayCartItems(cart);
    displayCartSummary(cart);
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