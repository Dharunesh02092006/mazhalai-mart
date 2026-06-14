// Order Success Page JavaScript - Simplified

$(document).ready(function() {
    // Load order details from URL parameters or localStorage
    loadOrderDetails();
});

function loadOrderDetails() {
    // Get order details from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('orderId');
    
    if (orderId) {
        // Display order ID
        $('#orderIdDisplay').text(orderId);
        
        // Try to get order data from localStorage
        const storedOrderData = localStorage.getItem('lastOrderData');
        if (storedOrderData) {
            try {
                const parsedOrderData = JSON.parse(storedOrderData);
                displayOrderSummary(parsedOrderData);
                // Clear stored order data after displaying
                localStorage.removeItem('lastOrderData');
            } catch (e) {
                console.log('Could not parse order data from localStorage');
                $('#orderSummary').hide();
            }
        } else {
            // If no order data available, hide the summary section
            $('#orderSummary').hide();
        }
    } else {
        // No order ID in URL - redirect to orders page
        console.log('No order ID found, redirecting...');
        setTimeout(() => {
            window.location.href = 'orders.html';
        }, 3000);
        
        $('#orderIdDisplay').text('Redirecting to orders...');
    }
}

