<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Services\CartService;
use Models\Product;

SessionManager::start();

if (!Auth::check()) {
    header('Location: /shop/php/store.php');
    exit;
}

$cartService = new CartService();
$items = $cartService->getItems();
$grand_total = $cartService->getTotal();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | DRIFT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/shop/css/store.css">
    <link rel="stylesheet" href="/shop/css/cart.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo-area">
                <a href="/shop/php/index.php"><h1>DRIFT</h1></a>
            </div>
        </div>
        <style>
        .header-logo h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 2px;
            margin: 0;
        }
        .header-logo a {
            text-decoration: none;
            color: inherit;
        }
        </style>
    </header>

    <!-- CATEGORY NAVIGATION -->
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

    <!-- BREADCRUMB -->
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
                <table class="cart-table">
                    <thead>
                        <tr><th>Product</th><th>Size</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $key => $item): 
                            $product = $item['product'];
                            $size = $item['size'];
                            $qty = $item['qty'];
                            $subtotal = $item['subtotal'];
                        ?>
                        <tr id="cart-row-<?= htmlspecialchars($key) ?>">
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
                            <td><button class="buy-now-btn" onclick="buyIndividual('<?= htmlspecialchars($key) ?>')">BUY NOW</button></td>
                            <td><a href="javascript:void(0)" class="remove-cart-item" onclick="removeCartItem('<?= htmlspecialchars($key) ?>')">&times;</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-summary">
                    <h3>Total: ₱<?= number_format($grand_total, 2) ?></h3>
                    <button class="checkout-btn" onclick="buyAll()">PROCEED TO CHECKOUT</button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="/shop/php/js/cart.js?v=<?= time() ?>"></script>
</body>
</html>