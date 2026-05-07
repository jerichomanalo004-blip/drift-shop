<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Database;
use Models\Product;
use Models\Review;

SessionManager::start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Product not found.");

$productModel = new Product();
$product = $productModel->getProductWithBrand($id); // Use the new method that includes brand
if (!$product) die("Product not found.");

// Fetch variants (sizes)
$db = Database::getInstance()->getConnection();
$variantStmt = $db->prepare("SELECT size, stock_quantity FROM product_variants WHERE product_id = ? AND stock_quantity > 0 ORDER BY FIELD(size, 'S','M','L','XL')");
$variantStmt->execute([$id]);
$variants = $variantStmt->fetchAll(\PDO::FETCH_ASSOC);

// Fetch gallery images
$gallery = $productModel->getGallery($id);

// Check wishlist status
$wishlist = SessionManager::get('wishlist', []);
$is_in_wishlist = isset($wishlist[$id]);

// Fetch reviews for this product
$reviewModel = new Review();
$reviews = $reviewModel->getProductReviews($id);
$reviewStats = $reviewModel->getReviewStats($id);
$total_reviews = $reviewStats['total_reviews'] ?? 0;
$average = round($reviewStats['avg_rating'] ?? 0, 1);
?>
<div class="quickview-container">
    <div class="quickview-images">
        <div class="main-image-wrapper" id="zoom-container">
            <div id="img-lens"></div>
            <img src="/shop/<?= htmlspecialchars($product['main_image'] ?? '') ?>" id="mainPopupImg">
        </div>
        <div id="zoom-result" class="zoom-window"></div>
        <div class="thumbnail-list">
            <img src="/shop/<?= htmlspecialchars($product['main_image'] ?? '') ?>" class="thumb active" onclick="updatePopupImg(this)">
            <?php foreach ($gallery as $img): ?>
                <img src="/shop/<?= htmlspecialchars($img['image_path'] ?? '') ?>" class="thumb" onclick="updatePopupImg(this)">
            <?php endforeach; ?>
        </div>
    </div>

    <div class="quickview-info">
        <span class="brand-label"><?= htmlspecialchars($product['brand'] ?? 'DRIFT') ?></span>
        <h1 class="product-title-large"><?= htmlspecialchars($product['product_name'] ?? '') ?></h1>
        <div class="rating-stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span style="color: <?= $i <= $average ? '#FFD700' : '#ddd' ?>;">★</span>
            <?php endfor; ?>
            <span class="review-count">(<?= $total_reviews ?> <?= $total_reviews == 1 ? 'Review' : 'Reviews' ?>)</span>
        </div>
        <p class="product-description"><?= htmlspecialchars($product['description'] ?? '') ?></p>

        <div class="selection-section">
            <label>Size</label>
            <div class="size-options">
                <?php foreach ($variants as $v): ?>
                    <button class="size-btn" onclick="selectSize(this)"><?= htmlspecialchars($v['size']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="price-container" style="display: flex; align-items: baseline; gap: 15px; margin-bottom: 10px;">
            <div class="price-display" id="base-price-display" data-price="<?= $product['price'] ?? 0 ?>">
                ₱<?= number_format($product['price'] ?? 0, 2) ?>
            </div>
            <div class="subtotal-display" style="font-size: 14px; color: #666; font-weight: 600;">
                Subtotal: <span id="dynamic-subtotal">₱<?= number_format($product['price'] ?? 0, 2) ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <div class="qty-input">
                <button type="button" onclick="window.forceQtyUpdate(-1)">-</button>
                <input type="number" id="purchase-qty" value="1" min="1" readonly>
                <button type="button" onclick="window.forceQtyUpdate(1)">+</button>
            </div>
            <button class="btn-add-cart" onclick="addToAction(<?= $id ?>, 'add_to_cart')">ADD TO CART</button>
        </div>

        <button class="add-to-wishlist-btn" 
                onclick="handleWishlist(<?= $id ?>)" 
                style="color: <?= $is_in_wishlist ? '#ff4d4d' : '#000' ?>;">
            <?= $is_in_wishlist ? "❤ IN WISHLIST" : "❤ ADD TO WISHLIST" ?>
        </button>

        <div class="reviews-display-section" style="margin-top: 50px; border-top: 1px solid #eee; padding-top: 30px;">
            <h3 style="font-weight: 900; text-transform: uppercase;">Customer Feedback</h3>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-card" style="margin-bottom: 20px; border-bottom: 1px solid #f5f5f5; padding-bottom: 15px;">
                        <div style="color: #FFD700;"><?= str_repeat('★', $rev['rating']) ?></div>
                        <strong style="font-size: 14px;"><?= htmlspecialchars($rev['first_name'] ?? '') ?></strong>
                        <p style="font-size: 13px; color: #555; margin-top: 5px;"><?= htmlspecialchars($rev['comment'] ?? '') ?></p>
                        <small style="color: #aaa; font-size: 10px;"><?= date('M d, Y', strtotime($rev['created_at'] ?? 'now')) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888; font-size: 13px;">No reviews yet. Be the first to write one!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
if (typeof window.forceQtyUpdate === 'function') {
    window.forceQtyUpdate(0);
}
</script>