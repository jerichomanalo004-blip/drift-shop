<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

if (isset($_GET['image_id']) && isset($_GET['product_id'])) {
    $imgId = (int)$_GET['image_id'];
    $prodId = (int)$_GET['product_id'];
    $db = Database::getInstance()->getConnection();
    $del = $db->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
    $del->execute([$imgId, $prodId]);
    header("Location: edit_product.php?id=$prodId&msg=image_deleted");
} else {
    echo "Missing information.";
}
exit();
?>