<?php
namespace Services;

use Core\SessionManager;

class WishlistService {
    private $wishlistKey = 'wishlist';

    public function toggle($productId) {
        $wishlist = SessionManager::get($this->wishlistKey, []);
        if (isset($wishlist[$productId])) {
            unset($wishlist[$productId]);
            $status = 'removed';
        } else {
            $wishlist[$productId] = true;
            $status = 'added';
        }
        SessionManager::set($this->wishlistKey, $wishlist);
        $this->syncToDatabase();
        return ['status' => $status, 'count' => count($wishlist)];
    }

    public function getItems() {
        $wishlist = SessionManager::get($this->wishlistKey, []);
        if (empty($wishlist)) return [];
        
        $db = \Core\Database::getInstance()->getConnection();
        $placeholders = implode(',', array_fill(0, count($wishlist), '?'));
        $stmt = $db->prepare("
            SELECT p.*, c.brand 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.id IN ($placeholders)
        ");
        $stmt->execute(array_keys($wishlist));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCount() {
        return count(SessionManager::get($this->wishlistKey, []));
    }

    private function syncToDatabase() {
        $userId = SessionManager::get('user_id');
        if (!$userId) return;
        $db = \Core\Database::getInstance()->getConnection();
        $db->prepare("DELETE FROM wishlist WHERE user_id = ?")->execute([$userId]);
        foreach (array_keys(SessionManager::get($this->wishlistKey, [])) as $productId) {
            $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
        }
    }

    public function loadFromDatabase($userId) {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wishlist = [];
        foreach ($stmt->fetchAll() as $row) $wishlist[$row['product_id']] = true;
        SessionManager::set($this->wishlistKey, $wishlist);
    }
}