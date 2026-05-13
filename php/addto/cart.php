<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Services\CartService;
use Models\User; // added to get user data for modal

SessionManager::start();

if (!Auth::check()) {
    header('Location: /shop/php/store.php');
    exit;
}

$cartService = new CartService();
$items = $cartService->getItems();
$grand_total = $cartService->getTotal();

// Get user data for the modal
$userModel = new User();
$user = $userModel->find(SessionManager::get('user_id'));
$user_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
$user_phone = $user['contact_number'] ?? '';
$user_address = $user['address'] ?? '';

// Build cart items array for JavaScript (ensure each item has product_name, main_image, size, qty, subtotal)
$cartItemsJs = [];
foreach ($items as $key => $item) {
    $product = $item['product'];
    $cartItemsJs[] = [
        'product_name' => $product['product_name'] ?? 'Product',
        'main_image'   => $product['main_image'] ?? '',
        'size'         => $item['size'],
        'qty'          => $item['qty'],
        'subtotal'     => $item['subtotal'],
        'price'        => $product['price'] ?? 0,
        'product_id'   => $product['id'] ?? null,
        'item_key'     => $key
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Shopping Cart | DRIFT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/shop/css/store.css">
    <link rel="stylesheet" href="/shop/css/cart.css">
</head>
<body>
    <!-- TOP BAR (with My Account / Back to Dashboard) -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                Free shipping · marketing@drift.com · contact@drift.com
            </div>
            <div class="top-bar-center"></div>
            <div class="top-bar-right">
                <a href="../users/customer.php" class="my-account">
                    <img width="18" height="18" src="https://img.icons8.com/fluency-systems-regular/48/user.png" alt="user" style="vertical-align: middle; margin-right: 5px;"/>
                    My Account
                </a>
            </div>
        </div>
    </div>

    <header class="main-header">
        <div class="container">
            <div class="logo-area">
                <a href="/shop/php/index.php"><h1>DRIFT</h1></a>
            </div>
        </div>
        <style>
        .table-wrapper {
            max-height: 700px;
            overflow-y: auto;
        }

        .cart-table thead th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }

        /* Larger checkboxes */
        .cart-table input[type="checkbox"] {
            width: 15px;
            height: 15px;
            transform: scale(1.2);
            cursor: pointer;
            margin-right: 10px;
        }

        .cart-table th:first-child {
            white-space: nowrap;
            min-width: 100px;
        }
        </style>
    </header>

    <nav class="category-nav">
        <div class="container">
            <ul>
                <li><a href="../store.php">All</a></li>
                <li><a href="../store.php?category=DBTK">DBTK</a></li>
                <li><a href="../store.php?category=HIGHMINDS">HIGHMINDS</a></li>
                <li><a href="../store.php?category=CHARLOTTE FOLK">CHARLOTTE FOLK</a></li>
            </ul>
        </div>
    </nav>

    <section class="subheader">
        <div class="container">
            <div class="subheader-nav">
                <a href="../store.php">Shop</a> / <span>MY CART</span>
            </div>
        </div>
    </section>

    <main class="cart-main-container">
        <div class="header-container">
            <div class="cart-title-section">
                <h2>Your Shopping Cart</h2>
                <p>Review your items before proceeding to checkout.</p>
            </div>

            <?php if (empty($items)): ?>
                <div class="empty-cart-display">
                    <p>Your cart is currently empty.</p>
                    <a href="/shop/php/store.php" class="btn-add-cart" style="text-decoration:none; padding: 15px 40px;">CONTINUE SHOPPING</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox">Select all</th>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $key => $item): 
                                $product = $item['product'];
                                $size = $item['size'];
                                $qty = $item['qty'];
                                $subtotal = $item['subtotal'];
                            ?>
                            <tr id="cart-row-<?= htmlspecialchars($key) ?>">
                                <td><input type="checkbox" class="item-checkbox" data-item-key="<?= htmlspecialchars($key) ?>"></td>
                                <td>
                                    <div class="cart-prod-info">
                                        <img src="/shop/<?= htmlspecialchars($product['main_image']) ?>" alt="">
                                        <div class="cart-prod-details">
                                            <span class="brand-label"><?= htmlspecialchars($product['brand'] ?? '') ?></span>
                                            <h4><?= htmlspecialchars($product['product_name'] ?? '') ?></h4>
                                        </div>
                                    </div>
                                </td>
                                <td><strong><?= htmlspecialchars($size) ?></strong></td>
                                <td>₱<?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <div class="qty-controls">
                                        <button class="qty-btn" onclick="updateCart('<?= htmlspecialchars($key) ?>', -1)">-</button>
                                        <input type="number" id="qty-input-<?= htmlspecialchars($key) ?>" class="qty-input" value="<?= $qty ?>" onchange="typeCartQty('<?= htmlspecialchars($key) ?>', this.value)">
                                        <button class="qty-btn" onclick="updateCart('<?= htmlspecialchars($key) ?>', 1)">+</button>
                                    </div>
                                </td>
                                <td><strong>₱<?= number_format($subtotal, 2) ?></strong></td>
                                <td><button class="buy-now-btn" data-item-key="<?= htmlspecialchars($key) ?>">BUY NOW</button></td>
                                <td><a href="javascript:void(0)" class="remove-cart-item" data-item-key="<?= htmlspecialchars($key) ?>" onclick="removeCartItem('<?= htmlspecialchars($key) ?>')">&times;</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cart-summary">
                    <h3>Total: <span id="cart-total-display">₱<?= number_format($grand_total, 2) ?></span></h3>
                    <button class="checkout-btn" id="openCheckoutModal">PROCEED TO CHECKOUT</button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/payment.php'; ?>

    <script src="/shop/php/js/cart.js?v=<?= time() ?>"></script>
    <script>
        // Global cart data
        window.cartItems = <?= json_encode($cartItemsJs) ?>;
        window.cartTotal = <?= (float)$grand_total ?>;
        window.userData = {
            fullname: <?= json_encode($user_name) ?>,
            phone: <?= json_encode($user_phone) ?>,
            address: <?= json_encode($user_address) ?>
        };
        window.csrfToken = <?= json_encode(\Core\CSRF::token()) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Initial total based on default checked state
            updateSelectedTotal();
        });

        function checkoutSelected() {
            const selectedItems = [];
            let total = 0;

            document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
                const itemKey = cb.getAttribute('data-item-key');
                const cartItem = window.cartItems.find(item => item.item_key === itemKey);
                if (cartItem) {
                    selectedItems.push(cartItem);
                    total += cartItem.subtotal;
                }
            });

            if (selectedItems.length === 0) {
                alert('Please select at least one item to checkout.');
                return;
            }

            // Open modal with only the selected items
            openPaymentModal(selectedItems, total, null);
        }

        // Remove any existing listener and attach the new one
        const checkoutBtn = document.getElementById('openCheckoutModal');
        if (checkoutBtn) {
            // Remove old listeners to avoid conflicts
            const newBtn = checkoutBtn.cloneNode(true);
            checkoutBtn.parentNode.replaceChild(newBtn, checkoutBtn);
            newBtn.addEventListener('click', checkoutSelected);
        }
    </script>
</body>
</html>