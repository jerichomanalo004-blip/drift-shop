
<div id="paymentModal" class="payment-modal-overlay" style="display: none;">
    <div class="payment-modal-content">
        <span class="close-modal" onclick="closePaymentModal()">&times;</span>
        <div class="checkout-wrapper">
            <div class="order-summary-section">
                <h3>Product Review <span id="modal-item-count">0 Items</span></h3>
                <div id="modal-items-container" class="items-scroll-area"></div>
                <div class="price-breakdown">
                    <div class="price-row"><span>Subtotal</span> <span id="modal-subtotal">₱0.00</span></div>
                    <div class="price-row"><span>Shipping</span> <span class="free-text">FREE</span></div>
                    <hr>
                    <div class="price-row grand-total"><span>Total</span> <span id="modal-total">₱0.00</span></div>
                </div>
            </div>
            <div class="payment-form-section">
                <div class="checkout-stepper">
                    <span class="step-link active" id="step1-label" data-step="1">Information</span>
                    <span class="step-link" id="step2-label" data-step="2">Payment</span>
                    <span class="step-link" id="step3-label" data-step="3">Completed</span>
                </div>
                <div id="stage-information" class="form-stage active">
                    <h2>Shipping Information</h2>
                    <div class="input-field readonly"><label>Name</label><input type="text" id="displayName" readonly></div>
                    <div class="input-field readonly"><label>Phone</label><input type="text" id="displayPhone" readonly></div>
                    <div class="input-field"><label>Shipping Address (Editable)</label><textarea id="shipping_address" rows="3" required></textarea></div>
                    <button type="button" class="btn-pay-now" onclick="goToPayment()">Continue to Payment</button>
                </div>
                <div id="stage-payment" class="form-stage" style="display:none;">
                    <h2>Payment Method</h2>
                    <div class="cod-box"><div class="cod-header"><strong>Cash on Delivery (COD)</strong><span>✓</span></div></div>
                    <div class="arrival-info"><strong>Estimated Arrival:</strong> <?= date('M d, Y', strtotime('+3 days')) ?></div>
                    <div class="summary-review"><strong>Deliver to:</strong> <span id="review-address"></span></div>
                    <button type="button" class="btn-pay-now" onclick="completeOrder()">Place Order (COD)</button>
                    <button type="button" class="btn-back" onclick="goToInfo()">← Back to Information</button>
                </div>
                <div id="stage-completed" class="form-stage" style="text-align: center;">
                    <div style="margin-top: 20px;">
                        <div class="success-icon">🎉</div>
                        <h2 class="form-title">Order Successful!</h2>
                        <p>Thank you. Your order will arrive in 3 days.</p>
                        <a href="/shop/php/store.php" class="btn-pay-now" style="display:inline-block; margin-top: 20px; text-decoration:none;">
                            Return to Shop
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>

#stage-completed {
    padding: 30px 20px;
}
#stage-completed .success-icon {
    font-size: 70px;
    margin-bottom: 20px;
    animation: bounce 0.6s ease;
}
#stage-completed h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 10px;
}
#stage-completed p {
    color: var(--text-secondary, #555);
    margin-bottom: 30px;
}
#stage-completed .btn-pay-now {
    display: inline-block;
    width: auto;
    min-width: 200px;
    background: #1a2b23;
    text-decoration: none;
}
#stage-completed .btn-pay-now:hover {
    background: #000;
}

@keyframes bounce {
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}
.payment-modal-overlay { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
.payment-modal-content { background: #f8f9fa; width: 90%; max-width: 900px; border-radius: 20px; overflow: hidden; position: relative; font-family: 'Inter', sans-serif; }
.close-modal {
    position: absolute;
    right: 25px;
    top: 20px;
    font-size: 32px;
    line-height: 1;
    cursor: pointer;
    color: #333;
    z-index: 100;
    transition: color 0.2s ease, transform 0.2s ease;
    display: block;
}

.close-modal:hover {
    color: #000;
    transform: scale(1.1);
}

.payment-form-section {
    position: relative;
    padding: 60px 40px 40px 40px;
}
.checkout-wrapper { display: flex; min-height: 550px; }
.order-summary-section { flex: 1; background: #fff; padding: 30px; border-right: 1px solid #eee; }
.items-scroll-area { max-height: 250px; overflow-y: auto; margin-bottom: 20px; }
.summary-item { display: flex; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; }
.summary-item img { width: 50px; height: 50px; object-fit: cover; margin-right: 12px; border-radius: 6px; }
.summary-info { flex: 1; }
.summary-info h4 { font-size: 14px; margin: 0; }
.summary-info p { font-size: 11px; color: #777; margin: 0; }
.summary-price { font-weight: 600; }
.price-breakdown { margin-top: 20px; }
.price-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
.grand-total { font-size: 18px; font-weight: 800; margin-top: 10px; }
.free-text { color: green; font-weight: bold; }
.input-field { margin-bottom: 15px; }
.input-field label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
.input-field input, .input-field textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
.readonly input, .readonly textarea { background: #f0f0f0; cursor: not-allowed; }
.btn-pay-now { width: 100%; background: #1a2b23; color: white; padding: 14px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px; }
.btn-pay-now:hover { background: #000; }
.btn-back { background: none; border: none; color: #777; margin-top: 15px; cursor: pointer; text-decoration: underline; }
/* Stepper Container */
.checkout-stepper { 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    margin-bottom: 40px; 
    padding-bottom: 20px;
    position: relative;
}

/* Connecting Line Background */
.checkout-stepper::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    width: 100%;
    height: 1px;
    background: #eee;
    z-index: 1;
}

/* Individual Step Links */
.step-link { 
    position: relative;
    z-index: 2;
    background: #f8f9fa; /* Matches modal bg to hide the line behind text */
    padding: 0 15px;
    color: #b5b5b5; 
    font-weight: 500; 
    font-size: 13px; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* The Number Circle */
.step-link::before {
    content: attr(data-step); /* We can add data-step="1" to HTML */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #eee;
    color: #999;
    font-size: 11px;
    transition: all 0.4s ease;
}

/* Active State - Matches the black pill in Screenshot 2026-05-08 at 8.14.09 PM.jpg */
.step-link.active { 
    color: #000; 
    font-weight: 700; 
}

.step-link.active::before {
    background: #000;
    color: #fff;
    transform: scale(1.1);
}

/* Completed State - Green Checkmark style */
.step-link.completed { 
    color: #1a2b23; 
}

.step-link.completed::before {
    content: "✓";
    background: #e8f5e9;
    color: #2e7d32;
    font-weight: bold;
}

.form-stage {
    display: none;
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}

.form-stage.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

#review-address {
    display: block;
    margin-top: 5px;
    font-weight: 600;
    color: #1a2b23;
    font-style: italic;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.cod-box { border: 2px solid #1a2b23; padding: 20px; border-radius: 12px; background: #f1f8f5; margin-bottom: 20px; }
.arrival-info { background: #eee; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
.summary-review { font-size: 12px; color: #555; margin-bottom: 20px; }
.success-icon { font-size: 60px; margin-bottom: 20px; }
@media (max-width: 768px) { .checkout-wrapper { flex-direction: column; } }
</style>

<script>
let currentItems = [];
let currentTotal = 0;
let currentSingleKey = null;

function renderModalItems() {
    const container = document.getElementById('modal-items-container');
    const countSpan = document.getElementById('modal-item-count');
    const subtotalSpan = document.getElementById('modal-subtotal');
    const totalSpan = document.getElementById('modal-total');
    if (!container) return;
    container.innerHTML = '';
    let totalQty = 0, totalPrice = 0;
    currentItems.forEach(item => {
        const productName = item.product_name || item.name || 'Product';
        const img = item.main_image ? `/shop/${item.main_image}` : '/shop/css/images/placeholder.png';
        const div = document.createElement('div');
        div.className = 'summary-item';
        div.innerHTML = `
            <img src="${img}" onerror="this.src='/shop/css/images/placeholder.png'">
            <div class="summary-info">
                <h4>${escapeHtml(productName)}</h4>
                <p>${item.size} | Qty: ${item.qty}</p>
            </div>
            <div class="summary-price">₱${item.subtotal.toFixed(2)}</div>
        `;
        container.appendChild(div);
        totalQty += item.qty;
        totalPrice += item.subtotal;
    });
    countSpan.innerText = `${totalQty} ${totalQty === 1 ? 'Item' : 'Items'}`;
    subtotalSpan.innerText = `₱${totalPrice.toFixed(2)}`;
    totalSpan.innerText = `₱${totalPrice.toFixed(2)}`;
    currentTotal = totalPrice;
}

function escapeHtml(str) { return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;')); }

function openPaymentModal(items, total, singleKey = null) {
    currentItems = items;
    currentSingleKey = singleKey;
    // Load user data if available
    if (window.userData) {
        document.getElementById('displayName').value = window.userData.fullname || '';
        document.getElementById('displayPhone').value = window.userData.phone || '';
        document.getElementById('shipping_address').value = window.userData.address || '';
    }
    renderModalItems();
    resetStages();
    document.getElementById('paymentModal').style.display = 'flex';
}

function closePaymentModal() { document.getElementById('paymentModal').style.display = 'none'; }
function resetStages() {
    document.getElementById('stage-information').style.display = 'block';
    document.getElementById('stage-payment').style.display = 'none';
    document.getElementById('stage-completed').style.display = 'none';
    ['step1-label', 'step2-label', 'step3-label'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove('active', 'completed');
    });
    document.getElementById('step1-label').classList.add('active');
}

function goToPayment() {
    const addrInput = document.getElementById('shipping_address');
    const addr = addrInput.value.trim();
    
    // 1. Validation
    if (!addr) {
        alert('Please enter your shipping address');
        return;
    }

    // 2. Update the review text in Stage 2
    document.getElementById('review-address').innerText = addr;

    // 3. Start Animation Logic
    const currentStage = document.getElementById('stage-information');
    const nextStage = document.getElementById('stage-payment');

    // Fade out current stage
    currentStage.style.opacity = '0';
    currentStage.style.transform = 'translateY(-10px)';

    setTimeout(() => {
        // Hide current, show next
        currentStage.style.display = 'none';
        currentStage.classList.remove('active');
        
        nextStage.style.display = 'block';
        
        // Trigger browser reflow to allow transition to play
        void nextStage.offsetWidth; 

        // Fade in next stage
        nextStage.classList.add('active');
        nextStage.style.opacity = '1';
        nextStage.style.transform = 'translateY(0)';

        // 4. Update Stepper Classes (Reference: Screenshot 2026-05-08 at 8.14.09 PM_2.jpg)
        const step1 = document.getElementById('step1-label');
        const step2 = document.getElementById('step2-label');

        step1.classList.remove('active');
        step1.classList.add('completed');
        step2.classList.add('active');
    }, 300); // Matches the 0.3s CSS transition time
}

function goToInfo() {
    document.getElementById('stage-payment').style.display = 'none';
    document.getElementById('stage-information').style.display = 'block';
    document.getElementById('step2-label').classList.remove('active');
}

function completeOrder() {
    const address = document.getElementById('shipping_address').value.trim();
    if (!address) return alert('Please enter your shipping address');

    const btn = document.querySelector('.btn-pay-now');
    btn.disabled = true;
    btn.innerText = 'Processing...';

    const itemKeys = currentItems.map(item => item.item_key).filter(k => k);

    if (itemKeys.length === 0) {
        alert('No items to order');
        btn.disabled = false;
        btn.innerText = 'Place Order (COD)';
        return;
    }

    fetch('/shop/php/addto/checkout_all.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ selected_items: itemKeys, address: address })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Hide stage 2
            document.getElementById('stage-payment').style.display = 'none';
            // Show stage 3
            const stage3 = document.getElementById('stage-completed');
            stage3.style.display = 'block';
            stage3.classList.add('active');
            // Update stepper
            document.getElementById('step2-label').classList.remove('active');
            document.getElementById('step2-label').classList.add('completed');
            document.getElementById('step3-label').classList.add('active');
            setTimeout(() => window.location.href = '/shop/php/users/orders.php', 3000);
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerText = 'Place Order (COD)';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error');
        btn.disabled = false;
        btn.innerText = 'Place Order (COD)';
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('paymentModal');
    const closeBtn = document.querySelector('.close-modal');
    if (closeBtn) closeBtn.addEventListener('click', closePaymentModal);
    window.addEventListener('click', e => { if (e.target === modal) closePaymentModal(); });
});
</script>