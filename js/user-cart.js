/**
 * User-Specific Cart JavaScript for Mazhalai Mart
 * Handles cart operations for authenticated users
 */

// Cart management functions
function addToUserCart(productId, productName, productPrice, productImage, quantity = 1) {
    // Check if user is logged in
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.authenticated) {
            // User not logged in - show login prompt
            if (confirm('Please login to add items to cart. Would you like to login now?')) {
                window.location.href = 'login.html';
            }
            return;
        }
        
        // User is logged in - add to cart
        const cartData = {
            product_id: productId,
            product_name: productName,
            product_price: parseFloat(productPrice),
            product_image: productImage,
            quantity: parseInt(quantity)
        };
        
        fetch('api/user_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(cartData)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification(result.message, 'success');
                
                // Update button state if on products page
                const button = document.querySelector(`[data-product-id="${productId}"] .add-to-cart-btn`);
                if (button) {
                    button.textContent = result.action === 'updated' ? 'Updated!' : 'Added!';
                    setTimeout(() => {
                        button.textContent = 'Add to Cart';
                    }, 2000);
                }
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Add to cart error:', error);
            showNotification('Failed to add item to cart', 'error');
        });
    })
    .catch(error => {
        console.error('Auth check error:', error);
        showNotification('Please login to add items to cart', 'error');
    });
}

function loadUserCart() {
    fetch('api/user_cart.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCartItems(data.cart_items, data.summary);
        } else {
            console.error('Failed to load cart:', data.message);
            displayEmptyCart();
        }
    })
    .catch(error => {
        console.error('Cart loading error:', error);
        displayEmptyCart();
    });
}

function updateCartQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        removeFromUserCart(productId);
        return;
    }
    
    const updateData = {
        product_id: productId,
        quantity: parseInt(newQuantity)
    };
    
    fetch('api/user_cart.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(updateData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadUserCart(); // Reload cart to update totals
        } else {
            showNotification(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Update cart error:', error);
        showNotification('Failed to update cart', 'error');
    });
}

function removeFromUserCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    const removeData = {
        product_id: productId
    };
    
    fetch('api/user_cart.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(removeData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(result.message, 'success');
            loadUserCart(); // Reload cart
        } else {
            showNotification(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Remove from cart error:', error);
        showNotification('Failed to remove item from cart', 'error');
    });
}

function displayCartItems(items, summary) {
    const cartContainer = document.querySelector('.cart-items');
    const summaryContainer = document.querySelector('.cart-summary');
    
    if (!cartContainer) return;
    
    if (items.length === 0) {
        displayEmptyCart();
        return;
    }
    
    let cartHTML = '';
    items.forEach(item => {
        cartHTML += `
            <div class="cart-item" data-product-id="${item.product_id}">
                <img src="${item.product_image}" alt="${item.product_name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h3 class="cart-item-name">${item.product_name}</h3>
                    <p class="cart-item-price">₹${item.product_price}</p>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn minus" onclick="updateCartQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="quantity-btn plus" onclick="updateCartQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                </div>
                <div class="cart-item-total">
                    <span class="item-total">₹${item.total_price}</span>
                    <button class="remove-btn" onclick="removeFromUserCart(${item.product_id})">Remove</button>
                </div>
            </div>
        `;
    });
    
    cartContainer.innerHTML = cartHTML;
    
    // Update summary
    if (summaryContainer) {
        summaryContainer.innerHTML = `
            <div class="summary-row">
                <span>Items (${summary.total_items})</span>
                <span>₹${summary.subtotal}</span>
            </div>
            <div class="summary-row">
                <span>Delivery</span>
                <span>₹${summary.delivery_charges}</span>
            </div>
            <hr>
            <div class="summary-row total">
                <strong>Total</strong>
                <strong>₹${summary.total}</strong>
            </div>
            <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
        `;
    }
}

function displayEmptyCart() {
    const cartContainer = document.querySelector('.cart-items');
    const summaryContainer = document.querySelector('.cart-summary');
    
    if (cartContainer) {
        cartContainer.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h3>Your cart is empty</h3>
                <p>Add some products to get started!</p>
                <a href="products.html" class="continue-shopping-btn">Continue Shopping</a>
            </div>
        `;
    }
    
    if (summaryContainer) {
        summaryContainer.innerHTML = `
            <div class="empty-summary">
                <p>No items in cart</p>
            </div>
        `;
    }
}

function updateCartCount() {
    // Cart count functionality removed - function kept for compatibility
    return;
}

function proceedToCheckout() {
    // Check if user is logged in
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.authenticated) {
            if (confirm('Please login to proceed to checkout. Would you like to login now?')) {
                window.location.href = 'login.html';
            }
            return;
        }
        
        // User is logged in - proceed to checkout
        window.location.href = 'checkout.html';
    })
    .catch(error => {
        console.error('Auth check error:', error);
        showNotification('Please login to proceed to checkout', 'error');
    });
}

// Buy now functionality for individual products
function buyNow(productId, productName, productPrice, productImage) {
    // Check if user is logged in
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.authenticated) {
            if (confirm('Please login to buy this product. Would you like to login now?')) {
                window.location.href = 'login.html';
            }
            return;
        }
        
        // Store buy now item temporarily
        const buyNowItem = {
            id: productId,
            name: productName,
            price: parseFloat(productPrice),
            image: productImage,
            quantity: 1
        };
        
        localStorage.setItem('buy_now_item', JSON.stringify([buyNowItem]));
        window.location.href = 'checkout.html';
    })
    .catch(error => {
        console.error('Auth check error:', error);
        showNotification('Please login to buy this product', 'error');
    });
}

// Initialize cart functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Load cart items if on cart page
    if (window.location.pathname.includes('cart.html')) {
        loadUserCart();
    }
});

// Notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#27ae60';
            break;
        case 'error':
            notification.style.backgroundColor = '#e74c3c';
            break;
        case 'warning':
            notification.style.backgroundColor = '#f39c12';
            break;
        default:
            notification.style.backgroundColor = '#3498db';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);