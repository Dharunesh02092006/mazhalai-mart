// Checkout functionality for Mazhalai Mart

$(document).ready(function() {
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
    
    // Place order button
    $('.place-order-btn').click(function(e) {
        e.preventDefault();
        if (validateCheckoutForm()) {
            processOrder();
        }
    });
});

function loadCheckoutSummary() {
    // Check for buy now items first (takes priority over cart)
    let items = JSON.parse(localStorage.getItem('buy_now_item')) || [];
    let isBuyNow = items.length > 0;
    
    // If no buy now items, use regular cart
    if (!isBuyNow) {
        items = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
    }
    
    // If no items at all, redirect to cart page or show empty message
    if (items.length === 0) {
        $('.checkout-summary').html(`
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h3>No items to checkout</h3>
                <p>Add some products to your cart before checkout.</p>
                <a href="products.html" class="continue-shopping-btn">Continue Shopping</a>
            </div>
        `);
        return;
    }
    
    let summaryHTML = '<h3 class="summary-heading">Order Summary</h3>';
    let subtotal = 0;
    
    items.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        summaryHTML += `
            <div class="summary-item">
                <img src="${item.image}" alt="${item.name}">
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
    
    // Get items from buy now or cart
    let items = JSON.parse(localStorage.getItem('buy_now_item')) || [];
    let isBuyNow = items.length > 0;
    
    // If no buy now items, use regular cart
    if (!isBuyNow) {
        items = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
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
                // Clear items based on source (no localStorage order saving)
                if (isBuyNow) {
                    localStorage.removeItem('buy_now_item');
                } else {
                    localStorage.removeItem('mazhalai_cart');
                    localStorage.setItem('cartCount', '0');
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
    // Get items from buy now or cart
    let items = JSON.parse(localStorage.getItem('buy_now_item')) || [];
    
    // If no buy now items, use regular cart
    if (items.length === 0) {
        items = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
    }
    
    const subtotal = items.reduce((total, item) => total + (item.price * item.quantity), 0);
    return subtotal + 60; // Add delivery charges
}

function showOrderSuccess(orderId) {
    const successHTML = `
        <div class="order-success">
            <div class="success-icon">🎉</div>
            <h2>Order Placed Successfully!</h2>
            <p>Your order ID is: <strong>${orderId}</strong></p>
            <p>Thank you for shopping with Mazhalai Mart!</p>
            <div class="success-actions">
                <a href="orders.html" class="btn">View Orders</a>
                <a href="products.html" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </div>
    `;
    
    $('.checkout-page').html(successHTML);
}