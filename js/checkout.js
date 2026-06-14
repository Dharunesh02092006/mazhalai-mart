// Checkout functionality for Mazhalai Mart

// Global variable to store checkout items
let checkoutItems = [];
let checkoutIsBuyNow = false;

$(document).ready(function() {
    // Check if user is logged in before accessing checkout
    checkAuthenticationForCheckout();
    
    loadCheckoutSummary();
    initializeCheckoutForm();
    
    // Payment method selection
    $('input[name="payment"]').change(function() {
        const selectedMethod = $(this).val();
        showPaymentDetails(selectedMethod);
    });
    
    // Form validation
    $('#checkout-form').on('submit', function(e) {
        e.preventDefault();
        if (validateCheckoutForm()) {
            processOrder();
        }
    });
    
    // Place order button - use event delegation for dynamically created button
    $(document).on('click', '.place-order-btn', function(e) {
        e.preventDefault();
        if (validateCheckoutForm()) {
            processOrder();
        }
    });
});

// Function to check authentication for checkout page
function checkAuthenticationForCheckout() {
    fetch('auth/check_login.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.loggedIn) {
            // User is not logged in, redirect to login
            alert('Please login or sign up to proceed with checkout!');
            window.location.href = 'login.html';
        }
    })
    .catch(error => {
        console.error('Auth check error:', error);
        // On error, redirect to login for safety
        window.location.href = 'login.html';
    });
}

function loadCheckoutSummary() {
    // Check for buy now items first (takes priority over cart)
    let buyNowItems = JSON.parse(localStorage.getItem('buy_now_item')) || [];
    let isBuyNow = buyNowItems.length > 0;
    
    // If buy now item exists, use it
    if (isBuyNow) {
        checkoutIsBuyNow = true;
        console.log('Checkout - Buy Now Mode:', true);
        console.log('Checkout - Items:', buyNowItems);
        displayCheckoutSummary(buyNowItems);
        return;
    }
    
    // For logged-in users, load from database
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            // User is logged in - load from database
            fetch('api/user_cart.php', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(cartData => {
                if (cartData.success && cartData.cart_items && cartData.cart_items.length > 0) {
                    // Convert database format to checkout format
                    const items = cartData.cart_items.map(item => ({
                        id: item.product_id,
                        name: item.product_name,
                        price: parseFloat(item.product_price),
                        image: item.product_image,
                        quantity: parseInt(item.quantity)
                    }));
                    checkoutIsBuyNow = false;
                    console.log('Checkout - Loaded from database:', items);
                    displayCheckoutSummary(items);
                } else {
                    showEmptyCheckout();
                }
            })
            .catch(error => {
                console.error('Error loading cart from database:', error);
                showEmptyCheckout();
            });
        } else {
            // User not logged in - use localStorage
            let items = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
            checkoutIsBuyNow = false;
            console.log('Checkout - Not logged in, using localStorage:', items);
            displayCheckoutSummary(items);
        }
    })
    .catch(error => {
        console.error('Auth status check error:', error);
        // Fallback to localStorage
        let items = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
        checkoutIsBuyNow = false;
        displayCheckoutSummary(items);
    });
}

function displayCheckoutSummary(items) {
    // Store items globally for use in processOrder
    checkoutItems = items;
    
    // If no items at all, show empty message
    if (!items || items.length === 0) {
        showEmptyCheckout();
        return;
    }
    
    let summaryHTML = '<h3 class="summary-heading">Order Summary</h3>';
    let subtotal = 0;
    
    items.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        // Fix image path
        let imageSrc = item.image || 'images/placeholder.jpg';
        if (imageSrc === 'undefined' || !imageSrc || imageSrc === '' || imageSrc === 'null') {
            imageSrc = 'images/placeholder.jpg';
        } else if (imageSrc.startsWith('uploads/')) {
            imageSrc = 'admin/' + imageSrc;
        } else if (!imageSrc.startsWith('images/') && !imageSrc.startsWith('admin/') && !imageSrc.startsWith('http')) {
            imageSrc = 'images/' + imageSrc;
        }
        
        summaryHTML += `
            <div class="summary-item">
                <img src="${imageSrc}" alt="${item.name}" onerror="this.src='images/placeholder.jpg'">
                <div>
                    <h4>${item.name}</h4>
                    <p>Qty: ${item.quantity}</p>
                </div>
                <span class="summary-price">₹${itemTotal}</span>
            </div>
        `;
    });
    
    const delivery = 60; // Always ₹60 delivery charge
    const discount = 0;
    const total = subtotal + delivery - discount;
    
    summaryHTML += `
        <hr>
        <div class="summary-row">
            <span>Subtotal</span>
            <span>₹${subtotal}</span>
        </div>
        <div class="summary-row">
            <span>Delivery</span>
            <span>₹${delivery}</span>
        </div>
        <div class="summary-row discount">
            <span>Discount</span>
            <span>-₹${discount}</span>
        </div>
        <hr>
        <div class="summary-row total">
            <strong>Total</strong>
            <strong>₹${total}</strong>
        </div>
        <button class="place-order-btn">Place Order</button>
        <p class="secure-note">🔒 Your payment is 100% secure</p>
    `;
    
    $('.checkout-summary').html(summaryHTML);
}

function showEmptyCheckout() {
    $('.checkout-summary').html(`
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h3>No items to checkout</h3>
            <p>Add some products to your cart before checkout.</p>
            <a href="products.html" class="continue-shopping-btn">Continue Shopping</a>
        </div>
    `);
}

function initializeCheckoutForm() {
    // Load saved address if available
    const savedAddress = JSON.parse(localStorage.getItem('savedAddress')) || {};
    
    Object.keys(savedAddress).forEach(key => {
        $(`input[name="${key}"]`).val(savedAddress[key]);
    });
}

function validateCheckoutForm() {
    let isValid = true;
    const requiredFields = ['first_name', 'last_name', 'address', 'city', 'state', 'pincode', 'phone', 'email'];
    
    // Clear previous errors
    $('.error').removeClass('error');
    
    requiredFields.forEach(field => {
        const input = $(`input[name="${field}"]`);
        if (!input.val().trim()) {
            input.addClass('error');
            isValid = false;
        }
    });
    
    // Validate email
    const email = $('input[name="email"]').val();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
        $('input[name="email"]').addClass('error');
        isValid = false;
    }
    
    // Validate phone
    const phone = $('input[name="phone"]').val();
    const phoneRegex = /^[6-9]\d{9}$/;
    if (phone && !phoneRegex.test(phone)) {
        $('input[name="phone"]').addClass('error');
        isValid = false;
    }
    
    // Validate pincode
    const pincode = $('input[name="pincode"]').val();
    const pincodeRegex = /^\d{6}$/;
    if (pincode && !pincodeRegex.test(pincode)) {
        $('input[name="pincode"]').addClass('error');
        isValid = false;
    }
    
    return isValid;
}

function showPaymentDetails(method) {
    // Remove existing payment details
    $('.payment-details').remove();
    
    let detailsHTML = '';
    
    switch(method) {
        case 'card':
            detailsHTML = `
                <div class="payment-details">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" placeholder="123" maxlength="3">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" placeholder="Name on card">
                    </div>
                </div>
            `;
            break;
        case 'upi':
            detailsHTML = `
                <div class="payment-details">
                    <div class="form-group">
                        <label>UPI ID</label>
                        <input type="text" placeholder="yourname@paytm">
                    </div>
                </div>
            `;
            break;
        case 'netbanking':
            detailsHTML = `
                <div class="payment-details">
                    <div class="form-group">
                        <label>Select Bank</label>
                        <select>
                            <option>Choose your bank</option>
                            <option>State Bank of India</option>
                            <option>HDFC Bank</option>
                            <option>ICICI Bank</option>
                            <option>Axis Bank</option>
                            <option>Punjab National Bank</option>
                        </select>
                    </div>
                </div>
            `;
            break;
    }
    
    if (detailsHTML) {
        $('.payment-grid').after(detailsHTML);
    }
}

function processOrder() {
    // Show loading state
    $('.place-order-btn').text('Processing...').prop('disabled', true);
    
    // Use global items that were loaded in loadCheckoutSummary
    const items = checkoutItems;
    const isBuyNow = checkoutIsBuyNow;
    
    if (!items || items.length === 0) {
        alert('No items in checkout. Please add products to your cart.');
        $('.place-order-btn').text('Place Order').prop('disabled', false);
        return;
    }
    
    const subtotal = items.reduce((total, item) => total + (item.price * item.quantity), 0);
    const deliveryCharges = 60; // Always ₹60 delivery charge
    const totalAmount = subtotal + deliveryCharges;
    
    const orderData = {
        action: 'create_order',
        data: {
            order_id: generateOrderId(),
            customer_name: $('input[name="first_name"]').val() + ' ' + $('input[name="last_name"]').val(),
            customer_email: $('input[name="email"]').val(),
            customer_phone: $('input[name="phone"]').val(),
            shipping_address: JSON.stringify({
                address: $('input[name="address"]').val(),
                city: $('input[name="city"]').val(),
                state: $('input[name="state"]').val(),
                pincode: $('input[name="pincode"]').val()
            }),
            payment_method: $('input[name="payment"]:checked').val(),
            subtotal: subtotal,
            delivery_charges: deliveryCharges,
            total_amount: totalAmount,
            items: items
        }
    };
    
    // Send order to PHP API
    $.ajax({
        url: 'api/orders.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(orderData),
        success: function(response) {
            if (response.success) {
                // Clear items based on source
                if (isBuyNow) {
                    localStorage.removeItem('buy_now_item');
                } else {
                    // For logged-in users, clear database cart
                    fetch('api/user_cart.php', {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ clear_all: true })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Cart cleared:', data);
                    })
                    .catch(error => {
                        console.error('Error clearing cart:', error);
                    });
                    
                    // Also clear localStorage backup
                    localStorage.removeItem('mazhalai_cart');
                    localStorage.setItem('cartCount', '0');
                    
                    // Update cart count badge
                    if (typeof updateCartBadge === 'function') {
                        updateCartBadge(0);
                    }
                }
                
                // Show success
                showOrderSuccess(orderData.data.order_id);
            } else {
                throw new Error(response.message || 'Order creation failed');
            }
        },
        error: function(xhr, status, error) {
            console.error('Order creation failed:', error);
            
            // Show error message - no localStorage fallback
            $('.place-order-btn').text('Place Order').prop('disabled', false);
            alert('Order creation failed. Please try again.');
        }
    });
}

function generateOrderId() {
    const date = new Date();
    const dateStr = date.getFullYear().toString() + 
                   (date.getMonth() + 1).toString().padStart(2, '0') + 
                   date.getDate().toString().padStart(2, '0');
    const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return 'BCR-' + dateStr + '-' + randomNum;
}

function calculateTotal() {
    // Use global items that were loaded
    const items = checkoutItems;
    
    if (!items || items.length === 0) {
        return 60; // Just delivery charges if no items
    }
    
    const subtotal = items.reduce((total, item) => total + (item.price * item.quantity), 0);
    return subtotal + 60; // Add delivery charges
}

function showOrderSuccess(orderId) {
    // Store order data in localStorage for the success page
    const orderData = {
        orderId: orderId,
        items: checkoutItems,
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('lastOrderData', JSON.stringify(orderData));
    
    // Redirect to the dedicated success page
    window.location.href = `order-success.html?orderId=${encodeURIComponent(orderId)}`;
}