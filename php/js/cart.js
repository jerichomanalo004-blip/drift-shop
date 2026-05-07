function updateCart(itemKey, change) {
    const input = document.getElementById(`qty-input-${itemKey}`);
    let newQty = parseInt(input.value) + change;
    if (newQty < 1) return;
    input.value = newQty;
    typeCartQty(itemKey, newQty);
}

function typeCartQty(itemKey, newQty) {
    const formData = new FormData();
    formData.append('item_key', itemKey);
    formData.append('new_qty', newQty);
    formData.append('action', 'set_exact_qty');

    fetch('cart_query.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert("Error: " + (data.message || "Could not update quantity"));
    })
    .catch(err => console.error(err));
}

function buyIndividual(itemKey) {
    if (confirm("Confirm purchase? This item will be moved to your orders.")) {
        fetch('checkout.php?item=' + encodeURIComponent(itemKey))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Order placed successfully!");
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Connection failed. Please try again.");
            });
    }
}

function buyAll() {
    if (confirm("Are you sure you want to purchase all items in your cart?")) {
        const btn = document.querySelector('.checkout-btn');
        btn.innerText = "Processing...";
        btn.disabled = true;

        fetch('checkout_all.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("All orders placed successfully!");
                window.location.href = "/shop/php/users/orders.php";
            } else {
                alert("Error: " + data.message);
                btn.innerText = "PROCEED TO CHECKOUT";
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Connection failed.");
            btn.innerText = "PROCEED TO CHECKOUT";
            btn.disabled = false;
        });
    }
}

function removeCartItem(itemKey) {
    if (confirm("Remove this item from your cart?")) {
        const formData = new FormData();
        formData.append('item_key', itemKey);
        formData.append('action', 'remove');

        fetch('cart_query.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) location.reload();
            else alert("Error: " + (data.message || "Could not remove item"));
        })
        .catch(err => console.error(err));
    }
}