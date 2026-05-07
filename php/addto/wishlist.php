<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Services\WishlistService;

SessionManager::start();

if (!Auth::check()) {
    header('Location: /shop/php/store.php');
    exit;
}

$wishlistService = new WishlistService();
$items = $wishlistService->getItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist | DRIFT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/shop/css/store.css">
    <link rel="stylesheet" href="/shop/css/wishlist.css">
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

    <main class="wishlist-container">
        <div class="header-container" style="width: 95%; max-width: 1400px; margin: 0 auto; padding: 0 20px;">
            <div class="wishlist-title-section">
                <h2>My Wishlist</h2>
                <p>Items added to your wishlist will be saved here even if you leave the site.</p>
            </div>

            <?php if (empty($items)): ?>
                <div class="empty-wishlist">
                    <p>Your wishlist is currently empty.</p>
                    <a href="/shop/php/store.php" class="btn-add-cart" style="text-decoration:none; padding: 15px 40px;">CONTINUE SHOPPING</a>
                </div>
            <?php else: ?>
                <table class="wishlist-table">
                    <thead><tr><th>Product Details</th><th>Price</th><th>Availability</th><th></th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $product): ?>
                        <tr class="wishlist-row" id="wish-row-<?= $product['id'] ?>">
                            <td>
                                <div class="wish-prod-info">
                                    <img src="/shop/<?= htmlspecialchars($product['main_image']) ?>" alt="">
                                    <div class="wish-prod-details">
                                        <span class="brand-label"><?= htmlspecialchars($product['brand'] ?? '') ?></span>
                                        <h4><?= htmlspecialchars($product['product_name'] ?? '') ?></h4>
                                    </div>
                                </div>
                            </td>
                            <td><span class="wishlist-price">₱<?= number_format($product['price'], 2) ?></span></td>
                            <td><span class="stock-status in-stock">In Stock</span></td>
                            <td><button class="btn-wish-cart" onclick="window.location.href='/shop/php/store.php?open_id=<?= $product['id'] ?>'">ADD TO CART</button></td>
                            <td><a href="javascript:void(0)" class="remove-wish" onclick="removeWish(<?= $product['id'] ?>)" title="Remove item">&times;</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <div id="product-modal" class="auth-overlay" style="display:none;">
        <div class="auth-container product-modal-container">
            <a href="javascript:void(0)" class="close-btn" onclick="closeProductModal()">&times;</a>
            <div id="modal-body"><p>Loading product details...</p></div>
        </div>
    </div>

    <script src="/shop/php/js/wishlist.js?v=<?= time() ?>"></script>
</body>
</html>