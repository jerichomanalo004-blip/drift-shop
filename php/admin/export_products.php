<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;

$db = Database::getInstance()->getConnection();

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Products sheet
$productsSheet = $spreadsheet->getActiveSheet();
$productsSheet->setTitle('products');

// Products headers
$productsHeaders = ['id', 'product_name', 'department', 'price', 'description', 'main_image', 'category_id', 'is_new', 'created_at'];
$productsSheet->fromArray($productsHeaders, NULL, 'A1');

// Bold headers
$productsSheet->getStyle('A1:I1')->getFont()->setBold(true);

// Products data
$productsStmt = $db->query("SELECT id, product_name, department, price, description, main_image, category_id, is_new, created_at FROM products ORDER BY id");
$productsData = $productsStmt->fetchAll(\PDO::FETCH_NUM);
$productsSheet->fromArray($productsData, NULL, 'A2');

// Variants sheet
$variantsSheet = $spreadsheet->createSheet();
$variantsSheet->setTitle('variants');

// Variants headers
$variantsHeaders = ['variant_id', 'product_id', 'size', 'stock_quantity', 'cost_price'];
$variantsSheet->fromArray($variantsHeaders, NULL, 'A1');

// Bold headers
$variantsSheet->getStyle('A1:E1')->getFont()->setBold(true);

// Variants data
$variantsStmt = $db->query("SELECT id as variant_id, product_id, size, stock_quantity, cost_price FROM product_variants ORDER BY product_id, FIELD(size, 'S','M','L','XL')");
$variantsData = $variantsStmt->fetchAll(\PDO::FETCH_NUM);
$variantsSheet->fromArray($variantsData, NULL, 'A2');

// Gallery sheet
$gallerySheet = $spreadsheet->createSheet();
$gallerySheet->setTitle('gallery');

// Gallery headers
$galleryHeaders = ['image_id', 'product_id', 'image_path'];
$gallerySheet->fromArray($galleryHeaders, NULL, 'A1');

// Bold headers
$gallerySheet->getStyle('A1:C1')->getFont()->setBold(true);

// Gallery data
$galleryStmt = $db->query("SELECT id as image_id, product_id, image_path FROM product_images ORDER BY product_id");
$galleryData = $galleryStmt->fetchAll(\PDO::FETCH_NUM);
$gallerySheet->fromArray($galleryData, NULL, 'A2');

// Categories sheet
$categoriesSheet = $spreadsheet->createSheet();
$categoriesSheet->setTitle('categories');

// Categories headers
$categoriesHeaders = ['id', 'brand', 'category_name'];
$categoriesSheet->fromArray($categoriesHeaders, NULL, 'A1');

// Bold headers
$categoriesSheet->getStyle('A1:C1')->getFont()->setBold(true);

// Categories data
$categoriesStmt = $db->query("SELECT id, brand, category_name FROM categories ORDER BY id");
$categoriesData = $categoriesStmt->fetchAll(\PDO::FETCH_NUM);
$categoriesSheet->fromArray($categoriesData, NULL, 'A2');

// Autosize columns for all sheets
foreach ($spreadsheet->getAllSheets() as $sheet) {
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
    }
}

// Set active sheet back to products
$spreadsheet->setActiveSheetIndex(0);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="drift_products_full.xlsx"');
header('Cache-Control: max-age=0');

// Create Xlsx Writer and output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>