const CART_QUERY_URL = '/shop/php/addto/cart_query.php';

// Global state for payment modal
let cartItems = [];
let cartTotal = 0;

// Initialize cart data from page
function initializeCart(items, total) {
    console.log('Initializing cart with items:', items);
    console.log('Cart total:', total);
    
    // Validate and structure cart items
    const validItems = [];
    if (Array.isArray(items)) {
        items.forEach((item, index) => {
            if (item && item.product) {
                validItems.push(item);
                console.log(`Item ${index}:`, {
                    productId: item.product.id,
                    productName: item.product.product_name,
                    size: item.size,
                    qty: item.qty,
                    subtotal: item.subtotal,
                    imageUrl: item.product.main_image
                });
            } else {
                console.warn(`Item ${index} is invalid:`, item);
            }
        });
    }
    
    window.cartItems = validItems;
    window.cartTotal = parseFloat(total) || 0;
    
    console.log('Cart initialized:', {
        itemCount: validItems.length,
        total: window.cartTotal
    });
}

function updateCart(itemKey, change) {
    const input = document.getElementById(`qty-input-${itemKey}`);
    if (!input) return;
    let newQty = parseInt(input.value) + change;
    if (isNaN(newQty) || newQty < 1) return;
    
    const formData = new FormData();
    formData.append('item_key', itemKey);
    formData.append('new_qty', newQty);
    formData.append('action', 'set_exact_qty');

    fetch(CART_QUERY_URL, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update UI without reload
                updateCartUI(itemKey, newQty);
            } else {
                alert("Error updating quantity: " + (data.message || "Unknown error"));
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network error. Please try again.");
        });
}

function typeCartQty(itemKey, newQty) {
    newQty = parseInt(newQty);
    if (isNaN(newQty) || newQty < 1) return;
    
    const formData = new FormData();
    formData.append('item_key', itemKey);
    formData.append('new_qty', newQty);
    formData.append('action', 'set_exact_qty');

    fetch(CART_QUERY_URL, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update UI without reload
                updateCartUI(itemKey, newQty);
            } else {
                alert("Error updating quantity: " + (data.message || "Unknown error"));
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network error. Please try again.");
        });
}

function updateCartUI(itemKey, newQty) {
    // Update input value
    const input = document.getElementById(`qty-input-${itemKey}`);
    if (input) input.value = newQty;
    
    // Find item in cartItems and update
    const itemIndex = window.cartItems.findIndex(item => item.item_key === itemKey);
    if (itemIndex !== -1) {
        window.cartItems[itemIndex].qty = newQty;
        window.cartItems[itemIndex].subtotal = newQty * window.cartItems[itemIndex].price;
        
        // Update subtotal in DOM
        const row = document.getElementById(`cart-row-${itemKey}`);
        if (row) {
            const subtotalCell = row.querySelector('td:nth-child(6) strong'); // Assuming subtotal is 6th column
            if (subtotalCell) {
                subtotalCell.textContent = '₱' + window.cartItems[itemIndex].subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
        }
    }
    
    // Update total display based on selected items
    updateSelectedTotal();
}

function removeCartItem(itemKey) {
    if (!confirm("Remove this item from your cart?")) return;
    const formData = new FormData();
    formData.append('item_key', itemKey);
    formData.append('action', 'remove');

    fetch(CART_QUERY_URL, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Error removing item: " + (data.message || "Unknown error"));
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network error. Please try again.");
        });
}

// Buy single item - open payment modal
function buyIndividual(itemKey) {
    console.log('Buy individual triggered for key:', itemKey);
    const item = window.cartItems.find(i => i.item_key === itemKey);
    if (!item) {
        console.warn('Cart item not found for key:', itemKey);
        return alert('Item not found');
    }

    const total = parseFloat(item.subtotal) || 0;
    const itemData = Object.assign({}, item);

    console.log('Item data for modal:', itemData);

    // Open payment modal for a single item
    openPaymentModal([itemData], total, itemKey);
}

// Attach Buy Now button handlers
function registerBuyNowButtons() {
    const buyNowButtons = document.querySelectorAll('.buy-now-btn');
    buyNowButtons.forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            const itemKey = button.getAttribute('data-item-key');
            if (itemKey) {
                buyIndividual(itemKey);
            }
        });
    });
}

function updateSelectedTotal() {
    let total = 0;
    document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const itemKey = cb.getAttribute('data-item-key');
        if (window.cartItems && Array.isArray(window.cartItems)) {
            const item = window.cartItems.find(i => i.item_key === itemKey);
            if (item) total += item.subtotal;
        }
    });
    const totalSpan = document.getElementById('cart-total-display');
    if (totalSpan) {
        totalSpan.innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }
}

// Register button listeners after DOM loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        registerBuyNowButtons();
        attachCheckboxListeners();
    });
} else {
    registerBuyNowButtons();
    attachCheckboxListeners();
}

function attachCheckboxListeners() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.item-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedTotal();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked && selectAll) {
                selectAll.checked = false;
            }
            const allChecked = [...rowCheckboxes].every(cb => cb.checked);
            if (selectAll) selectAll.checked = allChecked;
            updateSelectedTotal();
        });
    });
}