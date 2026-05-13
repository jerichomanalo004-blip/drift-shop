<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Models\Order;

SessionManager::start();

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = SessionManager::get('user_id');
$orderModel = new Order();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_ids'])) {
    $orderIds = json_decode($_POST['order_ids'], true);
    
    if (!is_array($orderIds) || empty($orderIds)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order IDs']);
        exit();
    }
    
    // Sanitize order IDs
    $orderIds = array_map('intval', $orderIds);
    
    try {
        // Get current status for these orders
        $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
        $stmt = $orderModel->db->prepare("SELECT id, status FROM orders WHERE id IN ($placeholders) AND user_id = ?");
        
        $params = array_merge($orderIds, [$userId]);
        $stmt->execute($params);
        
        $updates = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $updates[] = [
                'id' => (int)$row['id'],
                'status' => $row['status']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'updates' => $updates
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>