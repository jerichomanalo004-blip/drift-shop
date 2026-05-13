<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Models\Product;

SessionManager::start();

if (!Auth::check()) {
    header("Location: /shop/php/store.php");
    exit;
}

$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$pid) die("Error: No product ID provided.");

$productModel = new Product();
$product = $productModel->getProductWithBrand($pid);  // FIXED: includes brand
if (!$product) die("Error: Product not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review: <?= htmlspecialchars($product['product_name'] ?? '') ?> | DRIFT</title>
    <link rel="stylesheet" href="/shop/css/store.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/shop/css/review.css?v=<?= time() ?>">
</head>
<body>
    <div class="store-background-layer">
        <iframe src="/shop/php/users/orders.php"></iframe>
    </div>

    <div class="review-focus-container">
        <div class="product-mini-preview">
            <img src="/shop/<?= htmlspecialchars($product['main_image'] ?? '') ?>" alt="">
            <h4><?= htmlspecialchars($product['product_name'] ?? '') ?></h4>
            <p><?= htmlspecialchars($product['brand'] ?? '') ?></p>   <!-- FIXED: null coalescing -->
        </div>

        <div class="review-form-side">
            <h2>Write a Review</h2>
            <p>Tell the community about your experience with this item.</p>

            <form action="submit_review.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                
                <div class="rating-input-container">
                    <label>SELECT RATING</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>" title="<?= $i ?> stars">
                                <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <label>COMMENTS</label>
                <textarea name="comment" class="review-textarea" placeholder="How's the fit? Is the fabric premium?" required></textarea>

                <button type="submit" class="btn-submit-review">SUBMIT FEEDBACK</button>
                <a href="/shop/php/users/orders.php" class="back-link">&larr; Back to Orders</a>
            </form>
        </div>
    </div>
</body>
</html>