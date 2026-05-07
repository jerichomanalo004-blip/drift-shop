<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Models\Order;

SessionManager::start();

if (!Auth::check()) {
    header('Location: /shop/php/index.php');
    exit;
}

$userId = SessionManager::get('user_id');
$orderModel = new Order();

$orders = $orderModel->getUserOrders($userId, 100, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Orders | DRIFT</title>
    <link rel="stylesheet" href="/shop/css/customer_portal.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap" rel="stylesheet">
</head>
<body>
<div class="account-wrapper">
    <aside class="account-sidebar">
        <div class="logo">DRIFT</div>
        <ul class="sidebar-menu">
            <li><a href="customer.php">Account Settings</a></li>
            <li><a href="orders.php" class="active">Order History</a></li>
            <li><a href="/shop/php/store.php">Return to Store</a></li>
        </ul>
    </aside>

    <main class="account-content">
        <div class="account-header">
            <h2>Order History</h2>
        </div>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <?php $items = $orderModel->getOrderItems($order['id']); ?>
                <div class="order-card">
                    <div class="order-details-left">
                        <h4 style="color: #888;">Order #<?= $order['id'] ?></h4>
                        <p>Placed on <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                        <div class="order-items-list">
                            <?php foreach ($items as $item): ?>
                                <div class="item-row">
                                    <div class="item-details">
                                        <span><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)</span>
                                    </div>
                                    <span>₱<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                                </div>
                                <?php if (strtolower($order['status']) == 'delivered'): ?>
                                    <a href="/shop/php/product/review.php?id=<?= $item['product_id'] ?>" class="btn-review-trigger">WRITE A REVIEW</a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="order-status-right">
                        <div class="status-badge-cute <?= strtolower($order['status']) ?>"><?= strtoupper($order['status']) ?></div>
                        <?php if (strtolower($order['status']) == 'processing'): ?>
                            <button class="cancel-order-btn" onclick="cancelOrder(<?= $order['id'] ?>)">Cancel Order</button>
                        <?php endif; ?>
                        <div class="order-price-bold">₱<?= number_format($order['total_amount'], 2) ?></div>
                        <div class="arrival-timestamp">
                            <?php if (strtolower($order['status']) == 'delivered'): ?>
                                <span class="arrival-label">ARRIVED ON:</span>
                                <span class="arrival-date"><?= date('M d, Y', strtotime($order['updated_at'])) ?></span>
                            <?php elseif (strtolower($order['status']) == 'shipped'): ?>
                                <span class="arrival-label">EST. ARRIVAL:</span>
                                <span class="arrival-date"><?= date('M d, Y', strtotime($order['created_at'] . ' + 3 days')) ?></span>
                            <?php else: ?>
                                <span class="arrival-label">ETA:</span>
                                <span class="arrival-date">TBD (Pending Shipment)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't placed any orders yet.</p>
        <?php endif; ?>
    </main>
</div>

<script src="/shop/php/js/orders.js?v=<?= time() ?>"></script>
</body>
</html>