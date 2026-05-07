<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

$db = Database::getInstance()->getConnection();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=drift_products_inventory.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID','Product Name','Department','Price','Description','Image Path','Category ID','S_Stock','M_Stock','L_Stock','XL_Stock']);

$sql = "SELECT p.*, 
        MAX(CASE WHEN pv.size = 'S' THEN pv.stock_quantity ELSE 0 END) AS S,
        MAX(CASE WHEN pv.size = 'M' THEN pv.stock_quantity ELSE 0 END) AS M,
        MAX(CASE WHEN pv.size = 'L' THEN pv.stock_quantity ELSE 0 END) AS L,
        MAX(CASE WHEN pv.size = 'XL' THEN pv.stock_quantity ELSE 0 END) AS XL
        FROM products p
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        GROUP BY p.id";
$result = $db->query($sql);
while ($row = $result->fetch()) {
    fputcsv($output, [
        $row['id'], $row['product_name'], $row['department'], $row['price'],
        $row['description'], $row['main_image'], $row['category_id'],
        $row['S'], $row['M'], $row['L'], $row['XL']
    ]);
}
fclose($output);
exit();
?>