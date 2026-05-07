<?php
namespace Services;

use Core\SessionManager;
use Models\Product;

class CartService {
    private $cartKey = 'cart';
    private $productModel;

    public function __construct() {
        $this->productModel = new Product();
    }

    public function add($productId, $size, $quantity = 1) {
        $key = $productId . '_' . $size;
        $cart = SessionManager::get($this->cartKey, []);
        if (isset($cart[$key])) $cart[$key]['qty'] += $quantity;
        else $cart[$key] = ['qty' => $quantity];
        SessionManager::set($this->cartKey, $cart);
        $this->syncToDatabase();
    }

    public function updateQuantity($key, $newQty) {
        $cart = SessionManager::get($this->cartKey, []);
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = max(1, $newQty);
            SessionManager::set($this->cartKey, $cart);
            $this->syncToDatabase();
        }
    }

    public function remove($key) {
        $cart = SessionManager::get($this->cartKey, []);
        unset($cart[$key]);
        SessionManager::set($this->cartKey, $cart);
        $this->syncToDatabase();
    }

    public function getItems() {
        $cart = SessionManager::get($this->cartKey, []);
        $items = [];
        foreach ($cart as $key => $item) {
            $parts = explode('_', $key);
            // Use getProductWithBrand() instead of find()
            $product = $this->productModel->getProductWithBrand($parts[0]);
            if ($product) {
                $items[$key] = [
                    'product' => $product,   // now contains 'brand' key
                    'size'    => $parts[1] ?? 'N/A',
                    'qty'     => $item['qty'],
                    'subtotal'=> $product['price'] * $item['qty']
                ];
            }
        }
        return $items;
    }

    public function getCount() {
        $count = 0;
        foreach (SessionManager::get($this->cartKey, []) as $item) $count += $item['qty'];
        return $count;
    }

    public function getTotal() {
        $total = 0;
        foreach ($this->getItems() as $item) $total += $item['subtotal'];
        return $total;
    }

    public function clear() {
        SessionManager::remove($this->cartKey);
        $this->syncToDatabase(true);
    }

    private function syncToDatabase($clear = false) {
        $userId = SessionManager::get('user_id');
        if (!$userId) return;
        $db = \Core\Database::getInstance()->getConnection();
        if ($clear) {
            $db->prepare("DELETE FROM user_cart WHERE user_id = ?")->execute([$userId]);
            return;
        }
        $db->prepare("DELETE FROM user_cart WHERE user_id = ?")->execute([$userId]);
        foreach (SessionManager::get($this->cartKey, []) as $key => $item) {
            $parts = explode('_', $key);
            $stmt = $db->prepare("INSERT INTO user_cart (user_id, product_id, size, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $parts[0], $parts[1] ?? 'N/A', $item['qty']]);
        }
    }

    public function loadFromDatabase($userId) {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT product_id, size, quantity FROM user_cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = [];
        foreach ($stmt->fetchAll() as $row) {
            $key = $row['product_id'] . '_' . $row['size'];
            $cart[$key] = ['qty' => $row['quantity']];
        }
        SessionManager::set($this->cartKey, $cart);
    }
}