/**
 * Authentication JavaScript for Mazhalai Mart
 * Handles dynamic navigation updates based on authentication status
 */

$(document).ready(function() {
    updateNavigationForAuth();
});

function updateNavigationForAuth() {
    // Check if user is authenticated
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            // User is logged in - update navigation
            updateNavForLoggedInUser(data.user);
        } else {
            // User is not logged in - show login/signup links
            updateNavForGuestUser();
        }
    })
    .catch(error => {
        console.error('Auth status check failed:', error);
        // Default to guest navigation
        updateNavForGuestUser();
    });
}

function updateNavForLoggedInUser(user) {
    // Find navigation container
    const nav = $('.nav');
    
    if (nav.length > 0) {
        // Remove login/signup links if they exist
        nav.find('a[href="login.html"], a[href="signup.html"]').remove();
        
        // Add logout link if it doesn't exist
        if (nav.find('.logout-link').length === 0) {
            nav.append('<a href="#" class="logout-link" onclick="handleQuickLogout()">Logout</a>');
        }
        
        // Add user greeting if there's space
        if (nav.find('.user-greeting').length === 0) {
            nav.append(`<span class="user-greeting">Hi, ${user.username}!</span>`);
        }
    }
}

function updateNavForGuestUser() {
    const nav = $('.nav');
    
    if (nav.length > 0) {
        // Remove logout and user greeting if they exist
        nav.find('.logout-link, .user-greeting').remove();
        
        // Add login/signup links if they don't exist
        if (nav.find('a[href="login.html"]').length === 0) {
            nav.append('<a href="login.html">Login</a>');
        }
        
        if (nav.find('a[href="signup.html"]').length === 0) {
            nav.append('<a href="signup.html">Sign Up</a>');
        }
    }
}

function handleQuickLogout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('auth/logout.php', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update navigation immediately
                updateNavForGuestUser();
                
                // Show success message
                if (typeof showNotification === 'function') {
                    showNotification('Logged out successfully', 'success');
                }
                
                // Redirect if on a protected page
                // No protected pages to redirect from
            } else {
                alert('Logout failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            // Fallback - still update navigation
            updateNavForGuestUser();
            // No protected pages to redirect from
        });
    }
}

// Function to check if user needs to be redirected to login
function requireAuth() {
    fetch('auth/user_status.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.authenticated) {
            alert('Please login to access this page.');
            window.location.href = 'login.html';
        }
    })
    .catch(error => {
        console.error('Auth check failed:', error);
        alert('Please login to access this page.');
        window.location.href = 'login.html';
    });
}

// Export functions for global use
window.updateNavigationForAuth = updateNavigationForAuth;
window.handleQuickLogout = handleQuickLogout;
window.requireAuth = requireAuth;