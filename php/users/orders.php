<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\CSRF;
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

// Get filter parameters
$month = isset($_GET['month']) ? (int)$_GET['month'] : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

// Pagination
$limit = 8;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total count and fetch orders
$totalOrders = $orderModel->countUserOrders($userId, $month, $year, $status_filter);
$totalPages = ceil($totalOrders / $limit);
$orders = $orderModel->getUserOrders($userId, $limit, $offset, $month, $year, $status_filter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Orders | DRIFT</title>
    <link rel="stylesheet" href="/shop/css/customer_portal.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        .filter-bar {
            margin-bottom: 30px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
        }
        
        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-form select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        
        .filter-form select:hover {
            border-color: #1a2b23;
        }
        
        .btn-filter {
            padding: 5px 24px;
            background: #1a2b23;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.3s ease;
        }
        
        .btn-filter:hover {
            background: #000;
        }
        
        .btn-reset {
            padding: 5px 24px;
            background: transparent;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            border-color: #1a2b23;
            color: #1a2b23;
        }
        
        .orders-scroll-container {
            max-height: 800px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .orders-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .orders-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .orders-scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .orders-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            border-color: #1a2b23;
            color: #1a2b23;
        }
        
        .pagination a.active, .pagination span.active {
            background: #1a2b23;
            color: white;
            border-color: #1a2b23;
        }
        
        .no-orders-message {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
    </style>
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

        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <select name="month">
                    <option value="">All Months</option>
                    <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year">
                    <option value="">All Years</option>
                    <?php for ($y=2024; $y<=2026; $y++): ?>
                    <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select name="status_filter">
                    <option value="">All Statuses</option>
                    <option value="Processing" <?= ($status_filter == 'Processing') ? 'selected' : '' ?>>Processing</option>
                    <option value="Shipped" <?= ($status_filter == 'Shipped') ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= ($status_filter == 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= ($status_filter == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <a href="orders.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="orders-scroll-container">
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
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&month=<?= $month ?>&year=<?= $year ?>&status_filter=<?= urlencode($status_filter) ?>">First</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                    <a href="?page=<?= $i ?>&month=<?= $month ?>&year=<?= $year ?>&status_filter=<?= urlencode($status_filter) ?>" 
                       class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $totalPages ?>&month=<?= $month ?>&year=<?= $year ?>&status_filter=<?= urlencode($status_filter) ?>">Last</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-orders-message">
                <p>No orders found for the selected filters.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<script src="/shop/php/js/orders.js?v=<?= time() ?>"></script>
<script>
    window.csrfToken = <?= json_encode(\Core\CSRF::token()) ?>;
</script>
</body>
</html>