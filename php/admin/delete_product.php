<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $db->commit();
        header("Location: products.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: products.php?error=failed");
        exit();
    }
}
header("Location: products.php");
exit();
?>