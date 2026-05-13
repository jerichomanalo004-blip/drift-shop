// store.js – all JavaScript logic for store.php

// Wishlist toggle
function handleWishlist(productId) {
    const formData = new FormData();
    formData.append('id', productId);
    formData.append('action', 'toggle');
    fetch('addto/wishlist_query.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('wishlist-count-badge');
            if (badge) badge.innerText = data.wishlist_count;
            const btn = document.querySelector('.add-to-wishlist-btn');
            if (btn) {
                if (data.status === 'added') {
                    btn.innerHTML = "❤ IN WISHLIST";
                    btn.style.color = "#ff4d4d";
                } else {
                    btn.innerHTML = "❤ ADD TO WISHLIST";
                    btn.style.color = "#000";
                }
            }
        }
    })
    .catch(err => console.error("Error:", err));
}

// Add to cart from product page modal
function addToAction(productId, action) {
    if (isAdmin) {
        console.log("Admin preview mode: Cart action blocked.");
        return; 
    }
    const selectedSizeBtn = document.querySelector('.size-btn.active');
    if (!selectedSizeBtn) {
        alert("Please select a size first!");
        return;
    }
    const size = selectedSizeBtn.innerText;
    const qty = document.getElementById('purchase-qty').value;

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('size', size);
    formData.append('qty', qty);
    formData.append('action', action);

    fetch('addto/cart_query.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const countDisplay = document.getElementById('cart-count-display');
            if (countDisplay) countDisplay.innerText = data.cart_count;
            const totalDisplay = document.getElementById('cart-total-display');
            if (totalDisplay && data.cart_total) totalDisplay.innerText = '₱' + data.cart_total;
            alert("Success! Item added to your cart.");
        } else {
            alert("Error: " + (data.message || "Could not add item."));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Something went wrong. Please try again.");
    });
}

// Sidebar dropdowns
document.querySelectorAll('.toggle-btn').forEach(button => {
    button.addEventListener('click', function() {
        const parent = this.closest('.has-dropdown');
        parent.classList.toggle('open');
        this.innerText = parent.classList.contains('open') ? '−' : '+';
    });
});

// Size filter
function applySizeFilter(btn) {
    let params = new URLSearchParams(window.location.search);
    let sizeValue = btn.getAttribute('data-size');
    
    // Toggle: if already active, remove; else add
    if (btn.classList.contains('active')) {
        params.delete('size');
    } else {
        params.set('size', sizeValue);
    }
    
    // Reset to page 1 when filter changes
    params.set('p', '1');
    
    window.location.search = params.toString();
}

// Sorting
document.querySelector('.sort-select')?.addEventListener('change', function() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', this.value);
    currentUrl.searchParams.set('p', '1');
    window.location.href = currentUrl.toString();
});

// Grid/List view
function setView(viewType) {
    const container = document.getElementById('product-container');
    const gridBtn = document.getElementById('grid-btn');
    const listBtn = document.getElementById('list-btn');
    if (viewType === 'grid') {
        container.className = 'grid-view';
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
        localStorage.setItem('storeView', 'grid');
    } else {
        container.className = 'list-view';
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
        localStorage.setItem('storeView', 'list');
    }
}

// Load saved view on page load
function loadViewPreference() {
    const savedView = localStorage.getItem('storeView');
    if (savedView === 'list') {
        setView('list');
    } else {
        setView('grid');
    }
}

// Run when DOM is ready
document.addEventListener('DOMContentLoaded', loadViewPreference);

// Product item animation
const observerOptions = { threshold: 0.1 };
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
        }
    });
}, observerOptions);
document.querySelectorAll('.product-item').forEach(el => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    el.style.transition = "all 0.6s ease-out";
    observer.observe(el);
});

// Modal functions
function openProductModal(productId) {
    const modal = document.getElementById('product-modal');
    const modalBody = document.getElementById('modal-body');
    modal.style.opacity = "0";
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.transition = "opacity 0.3s ease";
        modal.style.opacity = "1";
    }, 10);
    fetch('product/product_info.php?id=' + productId)
    .then(response => response.text())
    .then(html => {
        modalBody.innerHTML = html;
        if (typeof imageZoom === "function") imageZoom("mainPopupImg", "zoom-result");
    });
}

function closeProductModal() {
    const modal = document.getElementById('product-modal');
    modal.style.opacity = "0";
    setTimeout(() => {
        modal.style.display = 'none';
        document.getElementById('modal-body').innerHTML = '<p style="padding:20px;">Loading...</p>';
    }, 300);
}

window.onclick = function(event) {
    const modal = document.getElementById('product-modal');
    if (event.target == modal) closeProductModal();
};

// Image zoom (called from modal after load)
function imageZoom(imgID, resultID) {
    let img = document.getElementById(imgID);
    let result = document.getElementById(resultID);
    let lens = document.getElementById("img-lens");
    if (!img || !result || !lens) return;
    img.parentElement.onmouseenter = () => {
        lens.style.display = "block";
        result.style.display = "block";
        result.style.backgroundImage = `url('${img.src}')`;
    };
    img.parentElement.onmouseleave = () => {
        lens.style.display = "none";
        result.style.display = "none";
    };
    img.parentElement.onmousemove = (e) => {
        let pos = getCursorPos(e);
        let x = pos.x - (lens.offsetWidth / 2);
        let y = pos.y - (lens.offsetHeight / 2);
        if (x > img.width - lens.offsetWidth) x = img.width - lens.offsetWidth;
        if (x < 0) x = 0;
        if (y > img.height - lens.offsetHeight) y = img.height - lens.offsetHeight;
        if (y < 0) y = 0;
        lens.style.left = x + "px";
        lens.style.top = y + "px";
        let cx = result.offsetWidth / lens.offsetWidth;
        let cy = result.offsetHeight / lens.offsetHeight;
        result.style.backgroundSize = (img.width * cx) + "px " + (img.height * cy) + "px";
        result.style.backgroundPosition = "-" + (x * cx) + "px -" + (y * cy) + "px";
    };
    function getCursorPos(e) {
        let a = img.getBoundingClientRect();
        let x = e.pageX - a.left - window.pageXOffset;
        let y = e.pageY - a.top - window.pageYOffset;
        return {x, y};
    }
}

function updatePopupImg(el) {
    const mainImg = document.getElementById('mainPopupImg');
    const zoomResult = document.getElementById('zoom-result');
    mainImg.src = el.src;
    if(zoomResult) zoomResult.style.backgroundImage = `url('${el.src}')`;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function selectSize(btn) {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected', 'active'));
    btn.classList.add('selected', 'active');
}

function changeQty(amt) {
    let input = document.getElementById('purchase-qty');
    let val = parseInt(input.value) + amt;
    if (val > 0) {
        input.value = val;
        const priceLabel = document.getElementById('base-price-display');
        const subtotalSpan = document.getElementById('dynamic-subtotal');
        if (priceLabel && subtotalSpan) {
            const price = parseFloat(priceLabel.getAttribute('data-price')) || 0;
            subtotalSpan.innerText = '₱' + (price * val).toLocaleString('en-US', {minimumFractionDigits:2});
        }
    }
}

window.forceQtyUpdate = function(change) {
    changeQty(change);
};

// Auto-open product if open_id in URL
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const productIdToOpen = urlParams.get('open_id');
    if (productIdToOpen) {
        openProductModal(productIdToOpen);
        window.history.replaceState({}, document.title, "store.php");
    }
};


