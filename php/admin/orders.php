<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Models\Order;

$db = Database::getInstance()->getConnection();

// Auto-update shipped to delivered after 3 days
$sql = "UPDATE orders SET status = 'Delivered', updated_at = NOW() WHERE status = 'Shipped' AND created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)";
$db->exec($sql);

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$where = [];
$params = [];
if ($month) {
    $where[] = "MONTH(orders.created_at) = ?";
    $params[] = $month;
}
if ($year) {
    $where[] = "YEAR(orders.created_at) = ?";
    $params[] = $year;
}
if ($status_filter) {
    $where[] = "orders.status = ?";
    $params[] = $status_filter;
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Total count
$countSql = "SELECT COUNT(*) FROM orders $whereSql";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Fetch orders with user names
$sql = "SELECT orders.*, users.first_name, users.last_name 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        $whereSql
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Handle status update form
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $upd = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$newStatus, $orderId]);
    header("Location: orders.php?success=updated&month=$month&year=$year&page=$page");
    exit();
}

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / LOGISTICS</div>
    <h1>Fulfillment Control Terminal</h1>
</div>

<div class="main-content">
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

    <div class="table-box">
        <table class="data-table">
            <thead>
                <tr><th>Order ID</th><th>Customer</th><th>Units</th><th>Total</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if ($orders): foreach ($orders as $order): ?>
                <tr>
                    <td class="order-id">#ORD-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br><small><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></small></td>
                    <td><?= $order['total_quantity'] ?> units</td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                    <td><span class="badge <?= strtolower($order['status']) ?>"><?= $order['status'] ?></span></td>
                    <td>
                        <?php if ($order['status'] === 'Processing'): ?>
                        <form method="POST" onsubmit="return confirm('Ship this order?')">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="status" value="Shipped">
                            <button type="submit" name="update_status" class="btn-ship">SHIP</button>
                        </form>
                        <?php elseif ($order['status'] === 'Shipped'): ?>
                        <span style="font-size:11px;color:var(--text-muted);">In transit...</span>
                        <?php else: ?>
                        <span style="font-size:11px;color:var(--accent-green);">Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="padding:40px; text-align:center;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=1&month=<?= $month ?>&year=<?= $year ?>&status_filter=<?= urlencode($status_filter) ?>">First</a><?php endif; ?>
        <?php for ($i = max(1, $page-1); $i <= min($totalPages, $page+1); $i++): ?>
        <a href="?page=<?= $i ?>&month=<?= $month ?>&year=<?= $year ?>&status_filter=<?= urlencode($status_filter) ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $totalPages ?>&month=<?= $month ?>&year=<?= $year ?>&status_filter=<?= urlencode($status_filter) ?>">Last</a><?php endif; ?>
    </div>

    <div class="footer">DRIFT Logistics Terminal © 2026</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>