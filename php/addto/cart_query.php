<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Services\CartService;
use Services\OrderService;

SessionManager::start();
header('Content-Type: application/json');

$cartService = new CartService();
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $size = $_POST['size'] ?? '';
    $qty = (int)($_POST['qty'] ?? 1);
    $item_key = $_POST['item_key'] ?? '';

    switch ($action) {
        case 'add_to_cart':
            if ($product_id && $size) {
                $cartService->add($product_id, $size, $qty);
                $response = [
                    'success' => true,
                    'cart_count' => $cartService->getCount(),
                    'cart_total' => number_format($cartService->getTotal(), 2)
                ];
            } else {
                $response['message'] = 'Missing product or size';
            }
            break;

        case 'set_exact_qty':
            // 🔧 FIX: read 'new_qty' (sent from JavaScript)
            $newQty = (int)($_POST['new_qty'] ?? 0);
            if ($item_key && $newQty > 0) {
                $cartService->updateQuantity($item_key, $newQty);
                $response = ['success' => true];
            } else {
                $response['message'] = 'Invalid quantity';
            }
            break;

        case 'remove':
            if ($item_key) {
                $cartService->remove($item_key);
                $response = ['success' => true];
            } else {
                $response['message'] = 'Missing item key';
            }
            break;

        case 'submit_order':
            $checkoutType = $_POST['checkout_type'] ?? 'cart';
            $address = $_POST['address'] ?? '';
            
            if (!Auth::check()) {
                $response['message'] = 'Not authenticated';
                break;
            }

            $userId = SessionManager::get('user_id');
            if (!$userId) {
                $response['message'] = 'User ID not found in session';
                break;
            }

            if (!$address) {
                $response['message'] = 'Address is required';
                break;
            }

            try {
                $orderService = new OrderService();

                if ($checkoutType === 'single') {
                    // Single item order
                    $itemKey = $_POST['item_key'] ?? '';
                    if (!$itemKey) {
                        $response['message'] = 'Item key required';
                        break;
                    }

                    $cart = SessionManager::get('cart', []);
                    $itemData = $cart[$itemKey] ?? null;

                    if (!$itemData) {
                        $response['message'] = 'Invalid item';
                        break;
                    }

                    // Create single item order
                    $result = $orderService->createOrderFromSingleItem($userId, $itemKey, $address, $itemData);
                    
                    if ($result['success']) {
                        // Remove from cart
                        $cartService->remove($itemKey);
                        $response = [
                            'success' => true,
                            'message' => 'Order placed successfully',
                            'order_id' => $result['order_id']
                        ];
                    }

                } else if ($checkoutType === 'cart') {
                    // Full cart order
                    $cartItems = $cartService->getItems();

                    if (empty($cartItems)) {
                        $response['message'] = 'Cart is empty';
                        break;
                    }

                    $cartTotal = $cartService->getTotal();

                    // Create cart order
                    $result = $orderService->createOrderFromCart($userId, $cartItems, $cartTotal, $address);

                    if ($result['success']) {
                        // Clear cart (already done in OrderService, but ensure it)
                        $cartService->clear();
                        $response = [
                            'success' => true,
                            'message' => 'Order placed successfully',
                            'order_id' => $result['order_id']
                        ];
                    }

                } else {
                    $response['message'] = 'Invalid checkout type';
                }

            } catch (\Exception $e) {
                error_log("Order Submission Error: " . $e->getMessage());
                $response['message'] = $e->getMessage();
            }
            break;
    }
}

echo json_encode($response);
exit;