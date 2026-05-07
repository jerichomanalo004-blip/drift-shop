function cancelOrder(orderId) {
    if (confirm("Are you sure you want to cancel Order #" + orderId + "?")) {
        fetch('cancel_orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Order #" + orderId + " has been cancelled.");
                location.reload();
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