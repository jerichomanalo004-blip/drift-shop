<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Models\Order;
use Services\CartService;

SessionManager::start();
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$cartService = new CartService();
$items = $cartService->getItems();
if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$userId = SessionManager::get('user_id');
$orderModel = new Order();

try {
    $cartTotal = $cartService->getTotal();
    $cartQty = $cartService->getCount();
    $cartItems = $cartService->getItems(); // array with keys like "productId_size"

    // Reformat for createFromCart: it expects $cartItems as [ productId_size => ['qty' => X] ]
    $cartForOrder = [];
    foreach ($cartItems as $key => $item) {
        $cartForOrder[$key] = ['qty' => $item['qty']];
    }

    $orderId = $orderModel->createFromCart($userId, $cartForOrder, $cartTotal, $cartQty);
    $cartService->clear();

    echo json_encode(['success' => true, 'order_id' => $orderId]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;