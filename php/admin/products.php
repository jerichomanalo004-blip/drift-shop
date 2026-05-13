<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Models\Product;

$db = Database::getInstance()->getConnection();
$productModel = new Product();

// Pagination
$limit = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereClause = '';
$params = [];
if (!empty($search)) {
    $whereClause = " WHERE p.product_name LIKE ? OR c.brand LIKE ? OR pv.size LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

// Count total
$countSql = "SELECT COUNT(DISTINCT p.id) FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             LEFT JOIN product_variants pv ON p.id = pv.product_id $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Main query
$sql = "SELECT p.id, p.product_name, p.price, c.brand, 
        MIN(pv.cost_price) as cost_price,
        GROUP_CONCAT(CASE WHEN pv.stock_quantity > 0 THEN pv.size END ORDER BY FIELD(pv.size, 'S','M','L','XL')) AS available_sizes,
        SUM(pv.stock_quantity) as total_stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        $whereClause
        GROUP BY p.id ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / ASSETS</div>
    <h1>Asset Management Terminal</h1>
    <div class="suite-ctrl-group">
        <a href="export_products.php" class="btn-ctrl">Export .XLSX</a>
        <a href="import_products.php" class="btn-ctrl">Import .XLSX</a>
        <a href="add_product.php" class="btn-ctrl btn-ctrl-primary">New Entry</a>
    </div>
</div>

<div class="main-content">
    <div class="suite-filter-bar">
        <div class="live-search-container" style="display: flex; gap: 8px;">
            <input type="text" id="liveSearchInput" class="suite-search-input" placeholder="Query assets by name, variant, or brand..." value="<?= htmlspecialchars($search) ?>">
            <button id="searchButton" class="btn-ctrl btn-ctrl-primary">Execute Search</button>
        </div>
    </div>

    <div class="suite-data-grid">
        <table class="suite-table">
            <thead><tr><th>Asset ID</th><th>Specification</th><th>Brand</th><th>Variants</th><th>Cost</th><th>MSRP</th><th>Net Margin</th><th>Stock</th><th>Action</th></tr></thead>
            <tbody>
                <?php if ($products): foreach ($products as $row): 
                    $cost = $row['cost_price'] ?? 0;
                    $price = $row['price'];
                    $profit = $price - $cost;
                ?>
                <tr>
                    <td class="id-tag">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= htmlspecialchars($row['brand'] ?? 'N/A') ?></td>
                    <td>
                        <?php if ($row['available_sizes']): 
                            $sizes = explode(',', $row['available_sizes']);
                            foreach ($sizes as $s): ?><span class="size-badge"><?= trim($s) ?></span><?php endforeach;
                        else: ?><span style="color:#d1242f; font-size:10px;">NULL_STOCK</span><?php endif; ?>
                    </td>
                    <td>₱<?= number_format($cost, 0) ?></td>
                    <td>₱<?= number_format($price, 0) ?></td>
                    <td class="profit-positive">₱<?= number_format($profit, 0) ?></td>
                    <td><span style="font-weight:700; <?= ($row['total_stock'] < 10) ? 'color:#d1242f' : '' ?>"><?= $row['total_stock'] ?? 0 ?></span></td>
                    <td class="action-links">
                        <a class="edit" href="edit_product.php?id=<?= $row['id'] ?>&page=<?= $page ?>&search=<?= urlencode($search) ?>" title="Edit product">
                            <img width="16" height="16" src="https://img.icons8.com/forma-bold-filled/24/edit.png" alt="edit">
                        </a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="9" style="text-align:center; padding:40px;">No asset records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if($page > 1): ?><a href="?page=1&search=<?= urlencode($search) ?>">First</a><?php endif; ?>
        <?php for($i=max(1,$page-1); $i<=min($totalPages,$page+1); $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= ($page == $i) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if($page < $totalPages): ?><a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>">Last</a><?php endif; ?>
    </div>
    <div class="footer">DRIFT Inventory Management System © 2026 | Deployment: Stable</div>
</div>

<script>
const searchInput = document.getElementById('liveSearchInput');
const productTable = document.querySelector('.suite-table tbody');
let timeout;

function performSearch() {
    const searchValue = searchInput.value;
    const url = new URL(window.location.href);
    url.searchParams.set('search', searchValue);
    url.searchParams.set('ajax', '1');  // marker for AJAX request
    fetch(url)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('.suite-table tbody');
            if (newTbody) {
                productTable.innerHTML = newTbody.innerHTML;
            }
        });
}

// Live search on typing
searchInput.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(performSearch, 400);
});
</script>
<?php include __DIR__ . '/includes/footer.php';?>
</main></div></body></html>