<?php
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

$userId = SessionManager::get('user_id');
$input = json_decode(file_get_contents('php://input'), true);
$selectedKeys = $input['selected_items'] ?? [];
$address = $input['address'] ?? '';

if (empty($selectedKeys)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}
if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Address required']);
    exit;
}

$cartService = new CartService();
$allItems = $cartService->getItems();

// Build selected items array
$selectedItems = [];
foreach ($selectedKeys as $key) {
    if (isset($allItems[$key])) {
        $selectedItems[$key] = $allItems[$key];
    }
}

if (empty($selectedItems)) {
    echo json_encode(['success' => false, 'message' => 'Selected items not found']);
    exit;
}

// Calculate totals for selected items
$totalAmount = 0;
$totalQty = 0;
$cartForOrder = [];
foreach ($selectedItems as $key => $item) {
    $cartForOrder[$key] = ['qty' => $item['qty']];
    $totalAmount += $item['subtotal'];
    $totalQty += $item['qty'];
}

$orderModel = new Order();
try {
    $orderId = $orderModel->createFromCart($userId, $cartForOrder, $totalAmount, $totalQty);
    // Remove only the selected items from cart
    foreach ($selectedKeys as $key) {
        $cartService->remove($key);
    }
    echo json_encode(['success' => true, 'order_id' => $orderId]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}