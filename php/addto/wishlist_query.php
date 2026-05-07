<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Services\WishlistService;

SessionManager::start();
header('Content-Type: application/json');

$wishlistService = new WishlistService();
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['id'] ?? 0);
    if ($product_id > 0) {
        $result = $wishlistService->toggle($product_id);
        $response = [
            'success' => true,
            'status' => $result['status'],
            'wishlist_count' => $result['count']
        ];
    } else {
        $response['message'] = 'Invalid product ID';
    }
}

echo json_encode($response);
exit;