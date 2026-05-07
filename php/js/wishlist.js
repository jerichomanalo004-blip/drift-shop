function removeWish(productId) {
    const formData = new FormData();
    formData.append('id', productId);

    fetch('wishlist_query.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.status === 'removed') {
            const row = document.getElementById('wish-row-' + productId);
            if (row) {
                row.style.transition = "all 0.3s ease";
                row.style.opacity = "0";
                row.style.transform = "translateX(20px)";
                setTimeout(() => {
                    row.remove();
                    if (document.querySelectorAll('.wishlist-row').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function openProductModal(productId) {
    // This will be handled by the store's modal logic, but we can keep placeholder
    window.location.href = '/shop/php/store.php?open_id=' + productId;
}