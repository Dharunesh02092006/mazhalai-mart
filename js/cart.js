// Cart functionality for Mazhalai Mart - User-Specific Version

let cart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];

$(document).ready(function() {
    // Check if user is logged in and load appropriate cart system
    checkAuthAndInitializeCart();
    
    // Add to cart button click handler - using event delegation for dynamically loaded products
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const buttonElement = this; // Store the raw DOM element
        const productData = {
            id: button.data('id'),
            name: button.data('name'),
            price: parseFloat(button.data('price')),
            image: button.data('image'),
            quantity: 1
        };
        
        // Check if user is logged in first
        checkLoginBeforeAction(() => {
            // Use user-specific cart if logged in, otherwise fallback to localStorage
            if (typeof addToUserCart === 'function') {
                // Pass the button DOM element as well so we can update it directly
                addToUserCart(productData.id, productData.name, productData.price, productData.image, productData.quantity, buttonElement);
            } else {
                addToCart(productData);
            }
        }, 'add to cart');
    });
    
    // Buy now button click handler - using event delegation for dynamically loaded products
    $(document).on('click', '.buy-now-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const productData = {
            id: button.data('id'),
            name: button.data('name'),
            price: parseFloat(button.data('price')),
            image: button.data('image'),
            quantity: 1
        };
        
        // Check if user is logged in first
        checkLoginBeforeAction(() => {
            // Use user-specific buy now if logged in
            if (typeof buyNow === 'function') {
                buyNow(productData.id, productData.name, productData.price, productData.image);
            } else {
                // Fallback to localStorage for non-logged in users
                localStorage.setItem('buy_now_item', JSON.stringify([productData]));
                window.location.href = 'checkout.html';
            }
        }, 'buy now');
    });
});

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
        if (typeof updateCartBadge === 'function') {
            updateCartBadge();
        }
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
        
        // Update cart count from server
        if (typeof updateCartCount === 'function') {
            updateCartCount();
        }
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
    
    // Calculate total cart items
    const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (typeof updateCartBadge === 'function') {
        updateCartBadge(totalCount);
    }
    
    // Add visual feedback to button
    const button = $(`.add-to-cart-btn[data-id="${product.id}"]`);
    const originalText = button.text();
    button.text('Added!').css('background', '#4CAF50');
    
    setTimeout(() => {
        button.text(originalText).css('background', '#ff6b6b');
    }, 1000);
}

// Function to add item to user-specific cart via API
function addToUserCart(productId, productName, productPrice, productImage, quantity) {
    fetch('api/user_cart.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            product_name: productName,
            product_price: productPrice,
            product_image: productImage,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add visual feedback to button
            const button = $(`.add-to-cart-btn[data-id="${productId}"]`);
            const originalText = button.text();
            button.text('Added!').css('background', '#4CAF50');
            
            setTimeout(() => {
                button.text(originalText).css('background', '#ff6b6b');
            }, 1000);
        } else {
            alert('Error adding item to cart: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error adding to user cart:', error);
        alert('Error adding item to cart');
    });
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
    // Check if user is logged in and use API, otherwise use localStorage
    fetch('auth/check_login.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.loggedIn) {
            // Use API to remove from user cart
            fetch('api/user_cart.php?product_id=' + productId, {
                method: 'DELETE',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('Item removed from user cart');
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                }
            })
            .catch(error => console.error('Error removing from user cart:', error));
        } else {
            // Use localStorage for non-logged-in users
            cart = cart.filter(item => item.id != productId);
            localStorage.setItem('mazhalai_cart', JSON.stringify(cart));
            
            // Update cart count badge
            const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(totalCount);
            }
        }
    });
}

// Function to update item quantity
function updateQuantity(productId, newQuantity) {
    // Check if user is logged in and use API, otherwise use localStorage
    fetch('auth/check_login.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.loggedIn) {
            // Use API to update user cart
            if (newQuantity <= 0) {
                removeFromCart(productId);
            } else {
                fetch('api/user_cart.php', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: newQuantity
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        console.log('Quantity updated in user cart');
                    }
                })
                .catch(error => console.error('Error updating user cart:', error));
            }
        } else {
            // Use localStorage for non-logged-in users
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
    });
}

// Function to check if user is logged in before allowing cart/buy actions
function checkLoginBeforeAction(callback, actionName) {
    if (!actionName) actionName = 'action';
    
    fetch('auth/check_login.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.loggedIn) {
            // User is logged in, proceed with action
            if (typeof callback === 'function') {
                callback();
            }
        } else {
            // User is not logged in, show alert and redirect
            alert('Please login or sign up to ' + actionName + '!');
            window.location.href = 'login.html';
        }
    })
    .catch(error => {
        console.error('Login check error:', error);
        alert('Please login or sign up to ' + actionName + '!');
        window.location.href = 'login.html';
    });
}