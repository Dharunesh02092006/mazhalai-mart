// Cart functionality for Mazhalai Mart - User-Specific Version

let cart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];

$(document).ready(function() {
    // Check if user is logged in and load appropriate cart system
    checkAuthAndInitializeCart();
    
    // Add to cart button click handler
    $('.add-to-cart-btn').click(function(e) {
        e.preventDefault();
        
        checkUserAuthentication(function(isLoggedIn) {
            if (!isLoggedIn) {
                alert('Please log in or sign up to add items to cart.');
                window.location.href = 'login.html';
                return;
            }
            
            const productData = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                price: parseFloat($(this).data('price')),
                image: $(this).data('image'),
                quantity: 1
            };
            
            // Use user-specific cart if logged in
            if (typeof addToUserCart === 'function') {
                addToUserCart(productData.id, productData.name, productData.price, productData.image, productData.quantity);
            } else {
                addToCart(productData);
            }
        });
    });
    
    // Buy now button click handler
    $('.buy-now-btn').click(function(e) {
        e.preventDefault();
        
        checkUserAuthentication(function(isLoggedIn) {
            if (!isLoggedIn) {
                alert('Please log in or sign up to purchase products.');
                window.location.href = 'login.html';
                return;
            }
            
            const productData = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                price: parseFloat($(this).data('price')),
                image: $(this).data('image'),
                quantity: 1
            };
            
            // Use user-specific buy now if logged in
            if (typeof buyNow === 'function') {
                buyNow(productData.id, productData.name, productData.price, productData.image);
            }
        });
    });
});

function checkUserAuthentication(callback) {
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        callback(data.authenticated);
    })
    .catch(error => {
        console.error('Auth check error:', error);
        callback(false);
    });
}

function checkAuthAndInitializeCart() {
    // Check if user is logged in
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            // User is logged in - migrate localStorage cart to user cart if exists
            migrateLocalStorageCart();
            
            // Load user-specific cart
            // Cart count functionality removed
        } else {
            // User not logged in - use localStorage cart
            // Cart count functionality removed
        }
    })
    .catch(error => {
        console.error('Auth check error:', error);
        // Fallback to localStorage cart
        updateCartBadge();
    });
}

function migrateLocalStorageCart() {
    const localCart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
    
    if (localCart.length > 0) {
        // Add each item from localStorage to user cart
        localCart.forEach(item => {
            if (typeof addToUserCart === 'function') {
                addToUserCart(item.id, item.name, item.price, item.image, item.quantity);
            }
        });
        
        // Clear localStorage cart after migration
        localStorage.removeItem('mazhalai_cart');
        localStorage.setItem('cartCount', '0');
    }
}

function addToCart(product) {
    // Fallback function for non-logged in users
    const existingProductIndex = cart.findIndex(item => item.id === product.id);
    
    if (existingProductIndex > -1) {
        // Product exists, increase quantity
        cart[existingProductIndex].quantity += 1;
    } else {
        // New product, add to cart
        cart.push(product);
    }
    
    // Save cart to localStorage
    localStorage.setItem('mazhalai_cart', JSON.stringify(cart));
    
    // Add visual feedback to button
    const button = $(`.add-to-cart-btn[data-id="${product.id}"]`);
    const originalText = button.text();
    button.text('Added!').css('background', '#4CAF50');
    
    setTimeout(() => {
        button.text(originalText).css('background', '#ff6b6b');
    }, 1000);
}

// Function to get cart data (for other pages)
function getCart() {
    return cart;
}

// Function to clear cart (for checkout completion)
function clearCart() {
    cart = [];
    localStorage.removeItem('mazhalai_cart');
}

// Function to remove item from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    localStorage.setItem('mazhalai_cart', JSON.stringify(cart));
}

// Function to update item quantity
function updateQuantity(productId, newQuantity) {
    const productIndex = cart.findIndex(item => item.id == productId);
    if (productIndex > -1) {
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else {
            cart[productIndex].quantity = newQuantity;
            localStorage.setItem('mazhalai_cart', JSON.stringify(cart));
        }
    }
}