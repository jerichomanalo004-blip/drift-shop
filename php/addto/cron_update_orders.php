<?php
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

$db = Database::getInstance()->getConnection();
$sql = "UPDATE orders SET status = 'Delivered' WHERE status = 'Shipped' AND created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)";
$stmt = $db->prepare($sql);
$stmt->execute();

echo "Updated " . $stmt->rowCount() . " orders to Delivered.\n";