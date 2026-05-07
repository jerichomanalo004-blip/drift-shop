<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

$db = Database::getInstance()->getConnection();

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, in, out
$offset = ($page - 1) * $limit;

// Build WHERE clause based on filter
$whereClause = '';
if ($filter == 'in') {
    $whereClause = 'WHERE change_amount > 0';
} elseif ($filter == 'out') {
    $whereClause = 'WHERE change_amount < 0';
}

// Total count (with filter)
$countSql = "SELECT COUNT(*) FROM inventory_log $whereClause";
$totalLogs = (int)$db->query($countSql)->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Summary (unfiltered, shown as badges)
$totalIn = (int)$db->query("SELECT SUM(change_amount) FROM inventory_log WHERE change_amount > 0")->fetchColumn();
$totalOut = abs((int)$db->query("SELECT SUM(change_amount) FROM inventory_log WHERE change_amount < 0")->fetchColumn());

// Fetch logs (with filter)
$sql = "SELECT il.*, p.product_name, pv.size 
        FROM inventory_log il
        JOIN product_variants pv ON il.variant_id = pv.id
        JOIN products p ON pv.product_id = p.id
        $whereClause
        ORDER BY il.created_at DESC LIMIT $limit OFFSET $offset";
$logs = $db->query($sql)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<style>
    .main-content {
        animation: fadeIn 0.25s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="top-box">
    <div class="brand-label">DRIFT / LOGS</div>
    <h1>Inventory Asset History</h1>
    <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

<div class="main-content">
    <div class="folder-container">
        <div class="folder-tab">
            <div class="folder-filter">
                <a href="?filter=all" class="folder-pill <?= $filter == 'all' ? 'active' : '' ?>">📁 All Logs</a>
                <a href="?filter=in" class="folder-pill <?= $filter == 'in' ? 'active' : '' ?>">📥 Stock In (<?= number_format($totalIn) ?> units)</a>
                <a href="?filter=out" class="folder-pill <?= $filter == 'out' ? 'active' : '' ?>">📤 Stock Out (<?= number_format($totalOut) ?> units)</a>
            </div>
        </div>
        <div class="folder-body">
            <div class="table-box">
                <table class="data-table">
                    <thead>
                        <tr><th>Timestamp</th><th>Product</th><th style="text-align:center;">Change</th><th style="text-align:center;">Authority</th><th>Reason & Remarks</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): $isAdd = $log['change_amount'] > 0; ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono'; font-size:12px;"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                            <?php 
                            $sizeNames = [
                                'S'  => 'Small',
                                'M'  => 'Medium',
                                'L'  => 'Large',
                                'XL' => 'Extra Large'
                            ];
                            $fullSize = $sizeNames[$log['size']] ?? $log['size'];
                            ?>
                            <td><strong><?= htmlspecialchars($log['product_name']) ?></strong><br><small><?= htmlspecialchars($fullSize) ?></small></td>
                            <td style="text-align:center; font-weight:700; color:<?= $isAdd ? '#1a7f37' : '#d1242f' ?>;"><?= ($isAdd ? '+' : '') . $log['change_amount'] ?></td>
                            <td style="text-align:center;"><span class="authority-badge"><?= $log['admin_id'] ? "ADM-".$log['admin_id'] : "SYSTEM" ?></span></td>
                            <td><span class="reason-tag"><?= strtoupper($log['reason']) ?></span><br><small><?= htmlspecialchars($log['remarks']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px;">No logs found for this filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=1&filter=<?= $filter ?>">First</a><?php endif; ?>
        <?php for ($i = max(1,$page-1); $i <= min($totalPages,$page+1); $i++): ?>
        <a href="?page=<?= $i ?>&filter=<?= $filter ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $totalPages ?>&filter=<?= $filter ?>">Last</a><?php endif; ?>
    </div>
    <div class="footer">DRIFT Inventory Management System © 2026 | Logs are Read-Only</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>