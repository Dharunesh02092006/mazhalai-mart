/**
 * Admin Panel Responsive JavaScript
 * Handles mobile menu, responsive tables, and touch interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initResponsiveTables();
    initTouchInteractions();
    initModalResponsive();
    handleOrientationChange();
});

/**
 * Initialize Mobile Menu Functionality
 */
function initMobileMenu() {
    // Create mobile menu toggle button if it doesn't exist
    let mobileToggle = document.querySelector('.mobile-menu-toggle');
    
    if (!mobileToggle) {
        // Create the hamburger menu button
        mobileToggle = document.createElement('button');
        mobileToggle.className = 'mobile-menu-toggle';
        mobileToggle.innerHTML = '☰';
        mobileToggle.setAttribute('aria-label', 'Toggle Menu');
        mobileToggle.setAttribute('type', 'button');
        
        // Insert at the beginning of admin-header
        const adminHeader = document.querySelector('.admin-header');
        if (adminHeader) {
            adminHeader.insertBefore(mobileToggle, adminHeader.firstChild);
        }
    }
    
    // Create sidebar overlay if it doesn't exist
    let sidebarOverlay = document.querySelector('.sidebar-overlay');
    if (!sidebarOverlay) {
        sidebarOverlay = document.createElement('div');
        sidebarOverlay.className = 'sidebar-overlay';
        document.body.appendChild(sidebarOverlay);
    }
    
    // Add close button to sidebar if it doesn't exist
    const sidebar = document.querySelector('.admin-sidebar');
    let sidebarCloseBtn = sidebar ? sidebar.querySelector('.sidebar-close-btn') : null;
    
    if (sidebar && !sidebarCloseBtn) {
        sidebarCloseBtn = document.createElement('button');
        sidebarCloseBtn.className = 'sidebar-close-btn';
        sidebarCloseBtn.innerHTML = '✕';
        sidebarCloseBtn.setAttribute('aria-label', 'Close Menu');
        sidebarCloseBtn.setAttribute('type', 'button');
        
        // Insert at the top of sidebar header
        const sidebarHeader = sidebar.querySelector('.admin-sidebar-header');
        if (sidebarHeader) {
            sidebarHeader.appendChild(sidebarCloseBtn);
        }
    }
    
    if (mobileToggle && sidebar && sidebarOverlay && sidebarCloseBtn) {
        // Remove any existing event listeners
        mobileToggle.replaceWith(mobileToggle.cloneNode(true));
        sidebarCloseBtn.replaceWith(sidebarCloseBtn.cloneNode(true));
        
        // Get fresh references
        mobileToggle = document.querySelector('.mobile-menu-toggle');
        sidebarCloseBtn = document.querySelector('.sidebar-close-btn');
        
        // Function to open sidebar
        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            mobileToggle.innerHTML = '✕';
            sidebarCloseBtn.style.display = 'flex';
        }
        
        // Function to close sidebar
        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
            mobileToggle.innerHTML = '☰';
            sidebarCloseBtn.style.display = 'none';
        }
        
        // Toggle functionality for hamburger menu
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isActive = sidebar.classList.contains('active');
            
            if (isActive) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        
        // Close button functionality
        sidebarCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSidebar();
        });
        
        // Close on overlay click
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
        
        // Close on nav item click (mobile)
        const navItems = document.querySelectorAll('.admin-nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 767) {
                    closeSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                closeSidebar();
            }
            
            // Update button visibility
            if (window.innerWidth <= 767) {
                mobileToggle.style.display = 'flex';
            } else {
                mobileToggle.style.display = 'none';
                sidebarCloseBtn.style.display = 'none';
            }
        });
        
        // Set initial state based on screen size
        if (window.innerWidth <= 767) {
            mobileToggle.style.display = 'flex';
            sidebarCloseBtn.style.display = 'none';
        } else {
            mobileToggle.style.display = 'none';
            sidebarCloseBtn.style.display = 'none';
        }
        
        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });
    }
}

/**
 * Initialize Responsive Tables
 */
function initResponsiveTables() {
    const tables = document.querySelectorAll('.admin-table');
    
    tables.forEach(table => {
        // Add responsive wrapper if not exists
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        // Add mobile-friendly data attributes
        const headers = table.querySelectorAll('th');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index].textContent);
                }
            });
        });
    });
}

/**
 * Initialize Touch Interactions
 */
function initTouchInteractions() {
    // Add touch-friendly hover effects for mobile
    const cards = document.querySelectorAll('.stat-card, .action-card, .admin-btn');
    
    cards.forEach(card => {
        card.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        });
        
        card.addEventListener('touchend', function() {
            setTimeout(() => {
                this.classList.remove('touch-active');
            }, 150);
        });
    });
    
    // Improve button tap targets
    const smallButtons = document.querySelectorAll('.admin-btn-small');
    smallButtons.forEach(btn => {
        if (window.innerWidth <= 767) {
            btn.style.minHeight = '44px';
            btn.style.minWidth = '44px';
        }
    });
}

/**
 * Initialize Modal Responsive Behavior
 */
function initModalResponsive() {
    const modals = document.querySelectorAll('.modal-overlay');
    
    modals.forEach(modal => {
        const modalContent = modal.querySelector('.modal-content');
        
        if (modalContent) {
            // Add touch scrolling for mobile
            modalContent.style.webkitOverflowScrolling = 'touch';
            
            // Adjust modal size on mobile
            function adjustModalSize() {
                if (window.innerWidth <= 767) {
                    modalContent.style.maxHeight = '90vh';
                    modalContent.style.width = '95%';
                    modalContent.style.margin = '5vh auto';
                } else {
                    modalContent.style.maxHeight = '80vh';
                    modalContent.style.width = '90%';
                    modalContent.style.margin = '10vh auto';
                }
            }
            
            adjustModalSize();
            window.addEventListener('resize', adjustModalSize);
        }
    });
}

/**
 * Handle Orientation Change
 */
function handleOrientationChange() {
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            // Recalculate layouts after orientation change
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            if (sidebar && overlay && mobileToggle) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                mobileToggle.innerHTML = '☰';
            }
            
            // Refresh table responsiveness
            initResponsiveTables();
            
            // Update mobile toggle visibility
            if (mobileToggle) {
                if (window.innerWidth <= 767) {
                    mobileToggle.style.display = 'flex';
                } else {
                    mobileToggle.style.display = 'none';
                }
            }
        }, 100);
    });
}

/**
 * Utility Functions
 */

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check if device is mobile
function isMobile() {
    return window.innerWidth <= 767;
}

// Check if device is tablet
function isTablet() {
    return window.innerWidth > 767 && window.innerWidth <= 1024;
}