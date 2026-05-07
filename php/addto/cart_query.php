<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Database;
use Services\CartService;

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
            if ($item_key && $qty > 0) {
                $cartService->updateQuantity($item_key, $qty);
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
    }
}

echo json_encode($response);
exit;