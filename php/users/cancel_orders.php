<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\CSRF;
use Core\SessionManager;
use Core\Auth;
use Core\Database;
use Models\Order;

SessionManager::start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && Auth::check() && isset($_POST['order_id'])) {
    if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $userId = SessionManager::get('user_id');
    
    $orderModel = new Order();
    $success = $orderModel->cancel($orderId, $userId);
    
    echo json_encode(['success' => $success]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
exit;