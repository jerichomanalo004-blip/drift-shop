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

// Handle import (CSV) - we'll keep the logic but adapt to PDO
if (isset($_POST['run_import']) && isset($_FILES['excel_file'])) {
    $filename = $_FILES['excel_file']['tmp_name'];
    if (is_uploaded_file($filename)) {
        $file = fopen($filename, "r");
        fgetcsv($file); // skip header
        $db->beginTransaction();
        try {
            $updates = 0; $inserts = 0;
            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (empty($column[1])) continue;
                $db_id = !empty($column[0]) && is_numeric($column[0]) ? (int)$column[0] : null;
                $name = $column[1];
                $dept = $column[2];
                $price = (float)$column[3];
                $desc = $column[4];
                $img = $column[5];
                $cat_id = (int)$column[6];
                $calc_cost = $price * 0.70;
                if ($db_id) {
                    $check = $db->prepare("SELECT id FROM products WHERE id = ?")->execute([$db_id]);
                    if ($check->fetch()) {
                        $upd = $db->prepare("UPDATE products SET product_name=?, department=?, price=?, description=?, main_image=?, category_id=? WHERE id=?");
                        $upd->execute([$name, $dept, $price, $desc, $img, $cat_id, $db_id]);
                        $updates++;
                    } else {
                        $ins = $db->prepare("INSERT INTO products (id, product_name, department, price, description, main_image, category_id) VALUES (?,?,?,?,?,?,?)");
                        $ins->execute([$db_id, $name, $dept, $price, $desc, $img, $cat_id]);
                        $inserts++;
                    }
                    $product_id = $db_id;
                } else {
                    $ins = $db->prepare("INSERT INTO products (product_name, department, price, description, main_image, category_id) VALUES (?,?,?,?,?,?)");
                    $ins->execute([$name, $dept, $price, $desc, $img, $cat_id]);
                    $product_id = $db->lastInsertId();
                    $inserts++;
                }
                // Update variants
                $sizes = ['S' => 7, 'M' => 8, 'L' => 9, 'XL' => 10];
                foreach ($sizes as $size => $colIndex) {
                    $stock = isset($column[$colIndex]) ? (int)$column[$colIndex] : 0;
                    $varSql = "INSERT INTO product_variants (product_id, size, stock_quantity, cost_price) 
                               VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE stock_quantity=?, cost_price=?";
                    $varStmt = $db->prepare($varSql);
                    $varStmt->execute([$product_id, $size, $stock, $calc_cost, $stock, $calc_cost]);
                }
            }
            $db->commit();
            fclose($file);
            echo "<script>alert('Sync Complete: $updates Updated, $inserts New assets.'); window.location.href='products.php';</script>";
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            die("Import Error: " . $e->getMessage());
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / ASSETS</div>
    <h1>Asset Management Terminal</h1>
    <div class="suite-ctrl-group">
        <a href="dashboard.php" class="btn-ctrl">Dashboard</a>
        <a href="export_products.php" class="btn-ctrl">Export .CSV</a>
        <button class="btn-ctrl" onclick="document.getElementById('excel_file').click();">Sync Import</button>
        <a href="add_product.php" class="btn-ctrl btn-ctrl-primary">New Entry</a>
    </div>
</div>

<div class="main-content">
    <form id="importForm" method="POST" enctype="multipart/form-data" style="display:none;">
        <input type="file" id="excel_file" name="excel_file" accept=".csv">
        <input type="hidden" name="run_import" value="1">
    </form>

    <div class="suite-filter-bar">
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" class="suite-search-input" placeholder="Query assets by name, variant, or brand..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-ctrl btn-ctrl-primary">Execute Search</button>
        </form>
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
document.getElementById('excel_file')?.addEventListener('change', function() {
    if (this.files && this.files[0]) document.getElementById('importForm').submit();
});
</script>
<?php include __DIR__ . '/includes/footer.php'; // we'll create footer.php to close tags ?>
</main></div></body></html>