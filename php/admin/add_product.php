<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

$db = Database::getInstance()->getConnection();
$uploadPath = __DIR__ . '/../../shirts/'; // absolute path, but we store relative in DB

if (isset($_POST['save'])) {
    $product_name = $_POST['product_name'];
    $department = $_POST['department'];
    $price = (float)$_POST['price'];
    $description = $_POST['description'];
    $category_id = (int)$_POST['category_id'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $automated_cost = $price * 0.70;

    $main_image = '';
    if (!empty($_FILES['main_image_file']['name'])) {
        $fileName = time() . '_' . basename($_FILES['main_image_file']['name']);
        if (move_uploaded_file($_FILES['main_image_file']['tmp_name'], $uploadPath . $fileName)) {
            $main_image = "shirts/" . $fileName;
        }
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO products (product_name, department, price, description, main_image, is_new, category_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$product_name, $department, $price, $description, $main_image, $is_new, $category_id]);
        $product_id = $db->lastInsertId();

        $sizes = ['S', 'M', 'L', 'XL'];
        foreach ($sizes as $size) {
            $qty = isset($_POST['sizes'][$size]) ? (int)$_POST['sizes'][$size] : 0;
            $varStmt = $db->prepare("INSERT INTO product_variants (product_id, size, stock_quantity, cost_price) VALUES (?,?,?,?)");
            $varStmt->execute([$product_id, $size, $qty, $automated_cost]);
        }

        // Gallery images
        if (!empty($_FILES['extra_images']['name'][0])) {
            foreach ($_FILES['extra_images']['tmp_name'] as $key => $tmp) {
                $extraName = time() . '_' . basename($_FILES['extra_images']['name'][$key]);
                if (move_uploaded_file($tmp, $uploadPath . $extraName)) {
                    $dbPath = "shirts/" . $extraName;
                    $db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?,?)")->execute([$product_id, $dbPath]);
                }
            }
        }

        $db->commit();
        header("Location: products.php?success=added");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// Fetch categories for dropdown
$categories = $db->query("SELECT * FROM categories")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / ENTRY</div>
    <h1>New Asset Registration</h1>
</div>

<div class="main-content">
    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <label>Asset Name / Specification</label>
            <input type="text" name="product_name" required>

            <div style="display:flex; gap:16px;">
                <div style="flex:1;">
                    <label>Department</label>
                    <select name="department">
                        <option value="Men">Men</option><option value="Women">Women</option><option value="Unisex">Unisex</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label>Vendor / Brand Category</label>
                    <select name="category_id">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['brand']) ?> - <?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:12px; align-items:flex-end;">
                <div style="flex:1.5;">
                    <label>MSRP (PHP)</label>
                    <input type="number" step="0.01" name="price" id="msrp" required>
                </div>
                <?php foreach (['S','M','L','XL'] as $size): ?>
                <div style="flex:1;">
                    <label style="text-align:center;"><?= $size ?></label>
                    <input type="number" name="sizes[<?= $size ?>]" value="0" class="stock-input">
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex; gap:16px; margin-top:8px;">
                <div style="flex:1;">
                    <label>Primary Image</label>
                    <input type="file" name="main_image_file" accept="image/*" required>
                </div>
                <div style="flex:1;">
                    <label>Gallery Assets</label>
                    <input type="file" name="extra_images[]" multiple accept="image/*">
                </div>
            </div>

            <label>Technical Description</label>
            <textarea name="description" rows="4"></textarea>

            <div style="margin-top:20px; display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="is_new" style="width:16px; margin:0;"> <span style="font-size:12px; font-weight:600;">FLAG AS NEW ARRIVAL</span>
            </div>

            <button type="submit" name="save" class="btn-primary">Commit to Inventory Database</button>
        </form>
        <a href="products.php" class="btn-secondary">← Cancel and Return to Assets</a>
    </div>
    <div class="footer">DRIFT Analytical Suite © 2026 | System Status: Secure</div>
</div>
<script>
document.getElementById('msrp')?.addEventListener('input', function(e) {
    let msrp = parseFloat(e.target.value) || 0;
    let cost = (msrp * 0.70).toFixed(2);
    document.querySelector('.btn-primary').innerHTML = `Commit to Database (Calculated Cost: PHP ${cost})`;
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>