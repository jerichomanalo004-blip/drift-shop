<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Core\Database;
use Models\Order;
use Models\User;
use Services\CartService;

SessionManager::start();
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if user has a shipping address
$userId = SessionManager::get('user_id');
$userModel = new User();
$user = $userModel->find($userId);

if (empty($user['address']) || trim($user['address']) === '') {
    echo json_encode(['success' => false, 'message' => 'Please add a shipping address before checkout', 'redirect' => '/shop/php/users/customer.php']);
    exit;
}

$itemKey = $_GET['item'] ?? '';
if (!$itemKey || !isset($_SESSION['cart'][$itemKey])) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

$cartService = new CartService();
$items = $cartService->getItems();
if (!isset($items[$itemKey])) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit;
}

$item = $items[$itemKey];
$product = $item['product'];
$size = $item['size'];
$qty = $item['qty'];

$orderModel = new Order();
$userId = SessionManager::get('user_id');

try {
    // Create an order with just this single item
    $db = \Core\Database::getInstance()->getConnection();
    $db->beginTransaction();

    $total = $product['price'] * $qty;
    // Insert order header
    $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, total_quantity, status, created_at) VALUES (?, ?, ?, 'Processing', NOW())");
    $stmt->execute([$userId, $total, $qty]);
    $orderId = $db->lastInsertId();

    // Get variant ID
    $varStmt = $db->prepare("SELECT id FROM product_variants WHERE product_id = ? AND size = ?");
    $varStmt->execute([$product['id'], $size]);
    $variant = $varStmt->fetch(\PDO::FETCH_ASSOC);
    if (!$variant) throw new \Exception("Variant not found");

    // Insert order item
    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
    $itemStmt->execute([$orderId, $product['id'], $variant['id'], $qty, $product['price']]);

    // Update stock
    $stockStmt = $db->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ?");
    $stockStmt->execute([$qty, $variant['id']]);

    // Log inventory change
    \Services\InventoryLogger::log($variant['id'], -$qty, 'sale', "Order #$orderId", $orderId);

    // Remove item from cart
    $cartService->remove($itemKey);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (\Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;