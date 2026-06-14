// Main JavaScript functionality for Mazhalai Mart

$(document).ready(function() {
    // Initialize the application
    initializeApp();
    
    // Initialize authentication if auth.js is loaded
    if (typeof updateNavigationForAuth === 'function') {
        updateNavigationForAuth();
    }
    
    // Mobile menu toggle
    $('.mobile-menu-toggle').click(function() {
        $('.nav').toggleClass('active');
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });
    
    // Add loading states for specific buttons only (not navigation buttons)
    // Skip add-to-cart buttons as they have their own feedback mechanism
    $('.buy-now-btn, .place-order-btn, .btn-cancel, .btn-reorder').click(function() {
        const $btn = $(this);
        const originalText = $btn.text();
        
        if (!$btn.hasClass('no-loading')) {
            $btn.addClass('loading').text('Loading...');
            
            setTimeout(() => {
                // Only restore if button still shows "Loading..." (user-cart.js might have changed it)
                if ($btn.text() === 'Loading...') {
                    $btn.removeClass('loading').text(originalText);
                }
            }, 2000);
        }
    });
});

function initializeApp() {
    console.log('Mazhalai Mart initialized');
    loadCartCount();
}

function loadCartCount() {
    // Load cart count from server if user is authenticated, otherwise from localStorage
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            // User is logged in - update cart count via the updateCartCount function
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        } else {
            // User not logged in - load from localStorage
            const localCart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
            const totalCount = localCart.reduce((sum, item) => sum + item.quantity, 0);
            updateCartBadge(totalCount);
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
        // Fallback to localStorage
        const localCart = JSON.parse(localStorage.getItem('mazhalai_cart')) || [];
        const totalCount = localCart.reduce((sum, item) => sum + item.quantity, 0);
        updateCartBadge(totalCount);
    });
}

function updateCartBadge(count) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = count;
        if (count > 0) {
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Utility functions
function showNotification(message, type = 'success') {
    // Notifications disabled - no popups
    console.log(`${type}: ${message}`);
}