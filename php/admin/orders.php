<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\CSRF;
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

// Handle status update AJAX
if (isset($_POST['update_status'])) {
    if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    // Validate status
    $validStatuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses, true) || $orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    $upd = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $upd->execute([$newStatus, $orderId]);

    if ($result) {
        echo json_encode(['success' => true, 'order_id' => $orderId, 'new_status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
    exit();
}

// Handle real-time updates check
if (isset($_GET['check_updates'])) {
    // Get current filters
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

    // Get total count
    $countSql = "SELECT COUNT(*) FROM orders $whereSql";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalOrders = $stmt->fetchColumn();

    echo json_encode(['totalOrders' => (int)$totalOrders]);
    exit();
}

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / ORDERS</div>
    <h1>Manage Orders</h1>
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
                <tr><th>Order ID</th><th>Customer</th><th>Units</th><th>Total</th><th>Status</th><th>Receipt</th><th>Action</th></tr>
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
                        <?php if ($order['status'] !== 'Cancelled'): ?>
                            <a href="generate_receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="btn-receipt">📄 Receipt</a>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($order['status'] === 'Processing'): ?>
                        <button type="button" class="btn-ship" onclick="shipOrder(<?= $order['id'] ?>)">SHIP</button>
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

<script>
function shipOrder(orderId) {
    if (!confirm('Confirm shipment for order #' + orderId + '? This will generate a receipt for the customer.')) {
        return;
    }

    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Processing...';

    // First update order status to Shipped
    fetch('orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'update_status=1&order_id=' + orderId + '&status=Shipped&csrf_token=' + encodeURIComponent(window.csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show status changed
            const row = button.closest('tr');
            // Status cell is the 5th column (OrderID, Customer, Units, Total, Status)
            const statusCell = row.querySelector('td:nth-child(5) .badge');
            if (statusCell) {
                statusCell.className = 'badge shipped';
                statusCell.textContent = 'Shipped';
            }
            // Action cell is the 7th column (OrderID, Customer, Units, Total, Status, Receipt, Action)
            const actionCell = row.querySelector('td:nth-child(7)');
            if (actionCell) {
                actionCell.innerHTML = '<span style="font-size:11px;color:var(--text-muted);">In transit...</span>';
            }
            // Also update the receipt column (6th) – no change needed
            // Generate and view receipt (inline, not forced download)
            window.open('generate_receipt.php?order_id=' + orderId, '_blank');
            
            alert('Order #' + orderId + ' has been marked as Shipped. Receipt opened in new tab.');
        } else {
            alert('Error: ' + (data.message || 'Failed to update order status'));
            button.disabled = false;
            button.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('Connection failed. Please try again.');
        button.disabled = false;
        button.textContent = originalText;
    });
}

// Rest of your existing JavaScript (real-time updates, notifications) remains unchanged
let lastOrderCount = <?= $totalOrders ?>;
let currentFilters = {
    month: '<?= $month ?>',
    year: '<?= $year ?>',
    status_filter: '<?= $status_filter ?>',
    page: <?= $page ?>
};

function checkForNewOrders() {
    const params = new URLSearchParams();
    if (currentFilters.month) params.append('month', currentFilters.month);
    if (currentFilters.year) params.append('year', currentFilters.year);
    if (currentFilters.status_filter) params.append('status_filter', currentFilters.status_filter);
    params.append('check_updates', '1');

    fetch('orders.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.totalOrders !== lastOrderCount) {
                showNotification(`New order activity detected! Total orders: ${data.totalOrders}`, 'info');
                lastOrderCount = data.totalOrders;
                if (confirm('New orders detected. Refresh the page to see updates?')) {
                    location.reload();
                }
            }
        })
        .catch(error => console.error('Error checking for updates:', error));
}

function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification-toast');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()">×</button>`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'info' ? '#0969da' : '#d1242f'};
        color: white;
        padding: 12px 16px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        max-width: 300px;
        cursor: pointer;
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        if (notification.parentElement) notification.remove();
    }, 10000);
}

setInterval(checkForNewOrders, 5000);
setTimeout(checkForNewOrders, 5000);
</script>

<script>
    window.csrfToken = <?= json_encode(\Core\CSRF::token()) ?>;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>