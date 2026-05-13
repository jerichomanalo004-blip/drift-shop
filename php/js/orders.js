function cancelOrder(orderId) {
    if (confirm("Are you sure you want to cancel Order #" + orderId + "?")) {
        fetch('cancel_orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=' + orderId + '&csrf_token=' + encodeURIComponent(window.csrfToken)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI without reload
                updateOrderStatus(orderId, 'Cancelled');
                alert("Order #" + orderId + " has been cancelled.");
            } else {
                alert("Error: " + (data.message || "Could not cancel order."));
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert("Connection failed. Please try again.");
        });
    }
}

function updateOrderStatus(orderId, newStatus) {
    // Find the order card
    const orderCards = document.querySelectorAll('.order-card');
    for (let card of orderCards) {
        const orderIdElement = card.querySelector('h4');
        if (orderIdElement && orderIdElement.textContent.includes('#' + orderId)) {
            // Update status badge
            const statusBadge = card.querySelector('.status-badge-cute');
            if (statusBadge) {
                statusBadge.className = 'status-badge-cute ' + newStatus.toLowerCase();
                statusBadge.textContent = newStatus.toUpperCase();
            }

            // Remove cancel button if it exists
            const cancelBtn = card.querySelector('.cancel-order-btn');
            if (cancelBtn) {
                cancelBtn.remove();
            }

            // Update arrival timestamp for cancelled orders
            const arrivalTimestamp = card.querySelector('.arrival-timestamp');
            if (arrivalTimestamp && newStatus.toLowerCase() === 'cancelled') {
                arrivalTimestamp.innerHTML = `
                    <span class="arrival-label">CANCELLED ON:</span>
                    <span class="arrival-date">${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                `;
            }

            break;
        }
    }
}

// Real-time order status updates for customers
let orderStatuses = {};

// Initialize order statuses
document.addEventListener('DOMContentLoaded', function() {
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach(card => {
        const orderIdElement = card.querySelector('h4');
        if (orderIdElement) {
            const orderId = orderIdElement.textContent.match(/#(\d+)/)?.[1];
            if (orderId) {
                const statusBadge = card.querySelector('.status-badge-cute');
                if (statusBadge) {
                    orderStatuses[orderId] = statusBadge.textContent.toLowerCase();
                }
            }
        }
    });
});

function checkOrderStatusUpdates() {
    const orderIds = Object.keys(orderStatuses);
    if (orderIds.length === 0) return;

    fetch('check_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_ids=' + encodeURIComponent(JSON.stringify(orderIds))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.updates) {
            let hasUpdates = false;
            data.updates.forEach(update => {
                const orderId = update.id.toString();
                const newStatus = update.status.toLowerCase();
                
                if (orderStatuses[orderId] !== newStatus) {
                    // Status changed
                    updateOrderStatus(orderId, update.status);
                    orderStatuses[orderId] = newStatus;
                    hasUpdates = true;
                }
            });
            
            if (hasUpdates) {
                showNotification('Your order status has been updated!', 'success');
            }
        }
    })
    .catch(error => {
        console.error('Error checking order status:', error);
    });
}

function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.notification-toast');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">×</button>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#1a7f37' : type === 'info' ? '#0969da' : '#d1242f'};
        color: white;
        padding: 12px 16px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        max-width: 300px;
        cursor: pointer;
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 10000);
}

// Check for status updates every 30 seconds
setInterval(checkOrderStatusUpdates, 30000);

// Initial check after 10 seconds
setTimeout(checkOrderStatusUpdates, 10000);