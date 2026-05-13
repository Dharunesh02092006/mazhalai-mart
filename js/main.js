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
    $('.add-to-cart-btn, .buy-now-btn, .place-order-btn, .btn-cancel, .btn-reorder').click(function() {
        const $btn = $(this);
        const originalText = $btn.text();
        
        if (!$btn.hasClass('no-loading')) {
            $btn.addClass('loading').text('Loading...');
            
            setTimeout(() => {
                $btn.removeClass('loading').text(originalText);
            }, 2000);
        }
    });
});

function initializeApp() {
    console.log('Mazhalai Mart initialized');
    loadCartCount();
}

function loadCartCount() {
    // Get cart count from localStorage or server
    const cartCount = localStorage.getItem('cartCount') || 0;
    updateCartBadge(cartCount);
}

function updateCartBadge(count) {
    $('.cart-count').text(count);
    if (count > 0) {
        $('.cart-count').show();
    } else {
        $('.cart-count').hide();
    }
}

// Utility functions
function showNotification(message, type = 'success') {
    // Notifications disabled - no popups
    console.log(`${type}: ${message}`);
}