<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$db = Database::getInstance()->getConnection();

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $filename = $_FILES['excel_file']['tmp_name'];
    $extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'])) {
        $errors[] = 'Please upload an Excel (.xlsx or .xls) file.';
    } else {
        try {
            $spreadsheet = IOFactory::load($filename);
            $db->beginTransaction();

            // ----- 1. Process categories sheet -----
            $categoriesSheet = $spreadsheet->getSheetByName('categories');
            if (!$categoriesSheet) throw new Exception('Sheet "categories" not found');
            $catRows = $categoriesSheet->toArray();
            $catHeader = array_shift($catRows);
            $catExpected = ['id', 'brand', 'category_name'];
            if ($catHeader !== $catExpected) {
                throw new Exception('Categories sheet header mismatch. Expected: ' . implode(',', $catExpected));
            }
            $catUpdated = 0;
            $catInserted = 0;
            foreach ($catRows as $row) {
                if (empty($row[1]) || empty($row[2])) continue; // brand and category_name required
                $id = (int)$row[0];
                $brand = trim($row[1]);
                $catName = trim($row[2]);
                if ($id > 0) {
                    $check = $db->prepare("SELECT id FROM categories WHERE id = ?");
                    $check->execute([$id]);
                    if ($check->fetch()) {
                        $upd = $db->prepare("UPDATE categories SET brand = ?, category_name = ? WHERE id = ?");
                        $upd->execute([$brand, $catName, $id]);
                        $catUpdated++;
                    } else {
                        $ins = $db->prepare("INSERT INTO categories (id, brand, category_name) VALUES (?, ?, ?)");
                        $ins->execute([$id, $brand, $catName]);
                        $catInserted++;
                    }
                } else {
                    $ins = $db->prepare("INSERT INTO categories (brand, category_name) VALUES (?, ?)");
                    $ins->execute([$brand, $catName]);
                    $catInserted++;
                }
            }

            // ----- 2. Process products sheet -----
            $productsSheet = $spreadsheet->getSheetByName('products');
            if (!$productsSheet) throw new Exception('Sheet "products" not found');
            $prodRows = $productsSheet->toArray();
            $prodHeader = array_shift($prodRows);
            $prodExpected = ['id', 'product_name', 'department', 'price', 'description', 'main_image', 'category_id', 'is_new', 'created_at'];
            if ($prodHeader !== $prodExpected) {
                throw new Exception('Products sheet header mismatch. Expected: ' . implode(',', $prodExpected));
            }
            $prodUpdated = 0;
            $prodInserted = 0;
            foreach ($prodRows as $row) {
                if (empty($row[1])) continue; // product_name required
                $id = !empty($row[0]) ? (int)$row[0] : null;
                $productName = trim($row[1]);
                $department = trim($row[2]);
                $price = (float)$row[3];
                $description = trim($row[4]);
                $mainImage = trim($row[5]);
                $categoryId = (int)$row[6];
                $isNew = (int)$row[7];
                // created_at ignored, we set automatically
                if ($id) {
                    $check = $db->prepare("SELECT id FROM products WHERE id = ?");
                    $check->execute([$id]);
                    if ($check->fetch()) {
                        $upd = $db->prepare("UPDATE products SET product_name=?, department=?, price=?, description=?, main_image=?, category_id=?, is_new=? WHERE id=?");
                        $upd->execute([$productName, $department, $price, $description, $mainImage, $categoryId, $isNew, $id]);
                        $prodUpdated++;
                    } else {
                        $ins = $db->prepare("INSERT INTO products (id, product_name, department, price, description, main_image, category_id, is_new) VALUES (?,?,?,?,?,?,?,?)");
                        $ins->execute([$id, $productName, $department, $price, $description, $mainImage, $categoryId, $isNew]);
                        $prodInserted++;
                    }
                    $productId = $id;
                } else {
                    $ins = $db->prepare("INSERT INTO products (product_name, department, price, description, main_image, category_id, is_new) VALUES (?,?,?,?,?,?,?)");
                    $ins->execute([$productName, $department, $price, $description, $mainImage, $categoryId, $isNew]);
                    $productId = $db->lastInsertId();
                    $prodInserted++;
                }

                // ----- 3. Process variants for this product (from variants sheet) -----
                // We'll do variants after all products? Better to process variants sheet separately,
                // but variants sheet contains product_id. We'll process variants sheet after products.
                // We'll store product_id for later use? Actually we process variants in a separate loop.
            }

            // ----- Process variants sheet after products (so product_id exists) -----
            $variantsSheet = $spreadsheet->getSheetByName('variants');
            if (!$variantsSheet) throw new Exception('Sheet "variants" not found');
            $varRows = $variantsSheet->toArray();
            $varHeader = array_shift($varRows);
            $varExpected = ['variant_id', 'product_id', 'size', 'stock_quantity', 'cost_price'];
            if ($varHeader !== $varExpected) {
                throw new Exception('Variants sheet header mismatch. Expected: ' . implode(',', $varExpected));
            }
            $varUpdated = 0;
            $varInserted = 0;
            foreach ($varRows as $row) {
                if (empty($row[1]) || empty($row[2])) continue; // product_id and size required
                $variantId = (int)$row[0];
                $productId = (int)$row[1];
                $size = $row[2];
                $stock = (int)$row[3];
                $cost = (float)$row[4];
                // Ensure product exists
                $prodCheck = $db->prepare("SELECT id FROM products WHERE id = ?");
                $prodCheck->execute([$productId]);
                if (!$prodCheck->fetch()) {
                    $errors[] = "Variant skipped: product_id $productId does not exist.";
                    continue;
                }
                if ($variantId > 0) {
                    $check = $db->prepare("SELECT id FROM product_variants WHERE id = ?");
                    $check->execute([$variantId]);
                    if ($check->fetch()) {
                        $upd = $db->prepare("UPDATE product_variants SET product_id=?, size=?, stock_quantity=?, cost_price=? WHERE id=?");
                        $upd->execute([$productId, $size, $stock, $cost, $variantId]);
                        $varUpdated++;
                    } else {
                        $ins = $db->prepare("INSERT INTO product_variants (id, product_id, size, stock_quantity, cost_price) VALUES (?,?,?,?,?)");
                        $ins->execute([$variantId, $productId, $size, $stock, $cost]);
                        $varInserted++;
                    }
                } else {
                    $ins = $db->prepare("INSERT INTO product_variants (product_id, size, stock_quantity, cost_price) VALUES (?,?,?,?)");
                    $ins->execute([$productId, $size, $stock, $cost]);
                    $varInserted++;
                }
            }

            // ----- Process gallery sheet -----
            $gallerySheet = $spreadsheet->getSheetByName('gallery');
            if (!$gallerySheet) throw new Exception('Sheet "gallery" not found');
            $galleryRows = $gallerySheet->toArray();
            $galleryHeader = array_shift($galleryRows);
            $galleryExpected = ['image_id', 'product_id', 'image_path'];
            if ($galleryHeader !== $galleryExpected) {
                throw new Exception('Gallery sheet header mismatch. Expected: ' . implode(',', $galleryExpected));
            }
            // Delete existing gallery images for products that will be updated? We'll replace all gallery entries.
            // First, clear all existing gallery (optional: we can clear per product, but easier to truncate and reinsert).
            // However, to keep it safe, we'll delete only images for products that appear in the import.
            $affectedProducts = [];
            foreach ($galleryRows as $row) {
                if (empty($row[1])) continue;
                $affectedProducts[] = (int)$row[1];
            }
            $affectedProducts = array_unique($affectedProducts);
            foreach ($affectedProducts as $pid) {
                $del = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
                $del->execute([$pid]);
            }
            // Now insert new gallery images
            $galleryInserted = 0;
            foreach ($galleryRows as $row) {
                if (empty($row[1]) || empty($row[2])) continue;
                $productId = (int)$row[1];
                $imagePath = trim($row[2]);
                // Ensure product exists
                $prodCheck = $db->prepare("SELECT id FROM products WHERE id = ?");
                $prodCheck->execute([$productId]);
                if (!$prodCheck->fetch()) {
                    $errors[] = "Gallery skipped: product_id $productId does not exist.";
                    continue;
                }
                $ins = $db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                $ins->execute([$productId, $imagePath]);
                $galleryInserted++;
            }

            $db->commit();
            $message = "Import completed successfully.<br>
                        Categories: $catInserted new, $catUpdated updated.<br>
                        Products: $prodInserted new, $prodUpdated updated.<br>
                        Variants: $varInserted new, $varUpdated updated.<br>
                        Gallery: $galleryInserted images imported.";
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Import failed: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / IMPORT</div>
    <h1>Product Data Import</h1>
    <div class="suite-ctrl-group">
        <a href="products.php" class="btn-ctrl">Back to Products</a>
        <a href="export_products.php" class="btn-ctrl">Export XLSX</a>
    </div>
</div>

<div class="main-content">
    <?php if ($message): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-left: 4px solid #28a745; border-radius: 4px;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-left: 4px solid #dc3545; border-radius: 4px;">
            <strong>Errors:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-container" style="max-width: 600px; margin: 0 auto;">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Select Excel File (.xlsx or .xls):</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required style="margin-top: 8px;">
            </div>
            <button type="submit" class="btn-primary" style="margin-top: 20px;">Import Products</button>
        </form>
    </div>

    <div style="margin-top: 40px; padding: 20px; background: var(--bg-secondary, #f6f8fa); border-radius: 8px;">
        <h3>Notes:</h3>
        <ul>
            <li>The Excel file must have exactly four sheets named: <strong>products</strong>, <strong>variants</strong>, <strong>gallery</strong>, <strong>categories</strong>.</li>
            <li>Export using the "Export XLSX" button on this page or on the Products page to get the correct format.</li>
            <li>The import will upsert (update if exists, insert otherwise) all records.</li>
            <li>Existing gallery images for a product are removed and replaced with the ones from the Excel file.</li>
            <li>Categories are processed first, then products, then variants, then gallery to ensure foreign keys are valid.</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>