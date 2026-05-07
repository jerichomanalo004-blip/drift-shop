<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Core\Database;
use Models\Order;

SessionManager::start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && Auth::check() && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $userId = SessionManager::get('user_id');
    
    $orderModel = new Order();
    $success = $orderModel->cancel($orderId, $userId);
    
    echo json_encode(['success' => $success]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
exit;