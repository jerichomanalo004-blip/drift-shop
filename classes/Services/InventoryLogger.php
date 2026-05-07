<?php
namespace Services;

use Core\Database;
use Core\SessionManager;

class InventoryLogger {
    public static function log($variantId, $changeAmount, $reason, $remarks = '', $orderId = null) {
        $db = Database::getInstance()->getConnection();
        $adminId = SessionManager::get('admin_id', 0);
        $stmt = $db->prepare("INSERT INTO inventory_log (variant_id, order_id, change_amount, admin_id, reason, remarks, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$variantId, $orderId, $changeAmount, $adminId, $reason, $remarks]);
    }
}