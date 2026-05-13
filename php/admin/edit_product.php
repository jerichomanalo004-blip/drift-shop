<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\CSRF;
use Core\Database;
use Core\UploadHelper;

$db = Database::getInstance()->getConnection();
$uploadPath = __DIR__ . '/../../shirts/';

$id = (int)$_GET['id'];
$currentPage = $_GET['page'] ?? 1;
$currentSearch = $_GET['search'] ?? '';

// Fetch product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) die("Product not found.");

// Fetch variants
$variants = [];
$varStmt = $db->prepare("SELECT size, stock_quantity FROM product_variants WHERE product_id = ?");
$varStmt->execute([$id]);
while ($v = $varStmt->fetch()) {
    $variants[$v['size']] = $v['stock_quantity'];
}

// Fetch gallery – FIXED
$galleryStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ?");
$galleryStmt->execute([$id]);
$gallery = $galleryStmt->fetchAll();

// Fetch categories
$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Handle update
if (isset($_POST['update'])) {
    if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
        header("Location: products.php?error=invalid_request");
        exit();
    }

    $product_name = trim($_POST['product_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $automated_cost = $price * 0.70;

    $main_image = $product['main_image'];
    if (!empty($_FILES['main_image_file']['name'])) {
        $savedName = UploadHelper::saveImage($_FILES['main_image_file'], $uploadPath);
        if (!$savedName) {
            header("Location: edit_product.php?id=$id&error=invalid_image");
            exit();
        }
        $main_image = "shirts/" . $savedName;
    }

    $db->beginTransaction();
    try {
        $upd = $db->prepare("UPDATE products SET product_name=?, department=?, category_id=?, price=?, description=?, main_image=?, is_new=? WHERE id=?");
        $upd->execute([$product_name, $department, $category_id, $price, $description, $main_image, $is_new, $id]);

        foreach (['S','M','L','XL'] as $size) {
            $newQty = isset($_POST['sizes'][$size]) ? (int)$_POST['sizes'][$size] : 0;
            $oldQty = $variants[$size] ?? 0;
            $change = $newQty - $oldQty;
            
            // Upsert variant
            $varUpd = $db->prepare("INSERT INTO product_variants (product_id, size, stock_quantity, cost_price) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE stock_quantity=?, cost_price=?");
            $varUpd->execute([$id, $size, $newQty, $automated_cost, $newQty, $automated_cost]);
            
            // Get variant id for logging
            $vidStmt = $db->prepare("SELECT id FROM product_variants WHERE product_id = ? AND size = ?");
            $vidStmt->execute([$id, $size]);
            $variantId = $vidStmt->fetchColumn();
            
            if ($change != 0 && $variantId) {
                $log = $db->prepare("INSERT INTO inventory_log (variant_id, change_amount, admin_id, reason, remarks) VALUES (?, ?, ?, 'Restock', ?)");
                $log->execute([$variantId, $change, $_SESSION['admin_id'] ?? 0, "Stock updated via edit product"]);
            }
        }

        // Handle new gallery images
        if (!empty($_FILES['extra_images']['name'][0])) {
            foreach ($_FILES['extra_images']['tmp_name'] as $key => $tmp) {
                if (empty($_FILES['extra_images']['name'][$key])) {
                    continue;
                }
                $imageFile = [
                    'name' => $_FILES['extra_images']['name'][$key],
                    'type' => $_FILES['extra_images']['type'][$key] ?? '',
                    'tmp_name' => $tmp,
                    'error' => $_FILES['extra_images']['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['extra_images']['size'][$key] ?? 0,
                ];
                $savedName = UploadHelper::saveImage($imageFile, $uploadPath);
                if ($savedName) {
                    $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                    $imgStmt->execute([$id, "shirts/" . $savedName]);
                }
            }
        }

        $db->commit();
        header("Location: products.php?success=updated&page=$currentPage&search=" . urlencode($currentSearch));
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        die("Error: " . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / MODIFIER</div>
    <h1>Asset Modification Terminal <span style="font-family:'JetBrains Mono'; opacity:0.6;">ID: #<?= str_pad($id,4,'0',STR_PAD_LEFT) ?></span></h1>
</div>

<div class="main-content">
    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\CSRF::token()) ?>">
            <label>Product Specification Name</label>
            <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>

            <div style="display:flex; gap:16px;">
                <div style="flex:1;">
                    <label>Department</label>
                    <select name="department">
                        <option value="Men" <?= $product['department'] == 'Men' ? 'selected' : '' ?>>Men</option>
                        <option value="Women" <?= $product['department'] == 'Women' ? 'selected' : '' ?>>Women</option>
                        <option value="Unisex" <?= $product['department'] == 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label>Vendor / Brand Logic</label>
                    <select name="category_id">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['brand']) ?> - <?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:12px; align-items:flex-end;">
                <div style="flex:1.5;"><label>MSRP (PHP)</label><input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required></div>
                <?php foreach (['S','M','L','XL'] as $size): ?>
                <div style="flex:1;"><label style="text-align:center;"><?= $size ?></label><input type="number" name="sizes[<?= $size ?>]" value="<?= $variants[$size] ?? 0 ?>" class="stock-input"></div>
                <?php endforeach; ?>
            </div>

            <label>Primary Image</label>
            <div style="background:#f6f8fa; padding:12px; border:1px solid var(--suite-border); border-radius:6px;">
                <img src="/shop/<?= $product['main_image'] ?>" style="width:40px; height:40px; border-radius:4px;">
                <input type="file" name="main_image_file" accept="image/*" style="margin-top:8px;">
            </div>

            <label>Gallery Library</label>
            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($gallery as $img): ?>
                <div class="gallery-item existing">
                    <img src="/shop/<?= $img['image_path'] ?>">
                    <a href="delete_image.php?image_id=<?= $img['id'] ?>&product_id=<?= $id ?>" class="remove-link" onclick="return confirm('Delete image?')">REMOVE</a>
                </div>
                <?php endforeach; ?>
                <!-- New previews will be appended here -->
                <div class="add-image-box" id="addImageBox">
                    <input type="file" id="extra_images_input" name="extra_images[]" multiple accept="image/*">
                    <span>+ ATTACH ASSETS</span>
                </div>
            </div>

            <script>
            document.getElementById('extra_images_input').addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                const container = document.getElementById('galleryGrid');
                const addBox = document.getElementById('addImageBox');
                
                files.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'gallery-item new-preview';
                        previewDiv.innerHTML = `
                            <img src="${event.target.result}" style="width:100%; height:100px; object-fit:cover;">
                            <a href="#" class="remove-link" onclick="this.parentElement.remove(); return false;">REMOVE</a>
                            <input type="hidden" name="extra_images_keep[]" value="${file.name}" disabled>
                        `;
                        container.insertBefore(previewDiv, addBox);
                    };
                    reader.readAsDataURL(file);
                });
                // Clear the input so same files can be re-added if needed
                e.target.value = '';
            });
            </script>

            <label>Description</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>

            <div style="margin: 20px 0 0 0; display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" name="is_new" id="is_new" <?= $product['is_new'] ? 'checked' : '' ?> style="width: 16px; height: 16px; margin: 0;">
                <label for="is_new" style="font-size: 12px; font-weight: 600; color: var(--suite-text-sub); margin: 0;">FLAG AS NEW ARRIVAL</label>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" name="update" class="btn-primary">Execute Database Update</button>
                <form method="POST" action="delete_product.php" style="margin:0;">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\CSRF::token()) ?>">
                    <button type="submit" class="btn-danger" onclick="return confirm('Permanently delete this product?');">Delete Asset</button>
                </form>
            </div>
        </form>
        <a href="products.php?page=<?= $currentPage ?>&search=<?= urlencode($currentSearch) ?>" class="btn-secondary">← Return to Asset List</a>
    </div>
    <div class="footer">DRIFT Inventory Management System © 2026</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
