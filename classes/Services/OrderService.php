<?php
namespace Services;

use Core\Database;
use Models\Order;
use Models\Product;

class OrderService {
    private $orderModel;
    private $productModel;
    private $db;

    public function __construct() {
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create order from single cart item with shipping address
     */
    public function createOrderFromSingleItem($userId, $itemKey, $shippingAddress, $itemData) {
        $this->db->beginTransaction();
        try {
            $parts = explode('_', $itemKey);
            $productId = $parts[0];
            $size = $parts[1] ?? 'N/A';

            $product = $this->productModel->find($productId);
            if (!$product) throw new \Exception("Product not found");

            $quantity = $itemData['qty'] ?? 1;
            $unitPrice = $product['price'];
            $totalAmount = $unitPrice * $quantity;

            $orderId = $this->insertOrder($userId, $totalAmount, $quantity, $shippingAddress);
            $this->insertOrderItem($orderId, $productId, $size, $quantity, $unitPrice);

            $this->db->commit();
            return ['success' => true, 'order_id' => $orderId];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Create order from full cart with shipping address
     */
    public function createOrderFromCart($userId, $cartItems, $totalAmount, $shippingAddress) {
        $this->db->beginTransaction();
        try {
            if (empty($cartItems)) throw new \Exception("Cart is empty");

            $totalQty = array_sum(array_column($cartItems, 'qty'));
            $orderId = $this->insertOrder($userId, $totalAmount, $totalQty, $shippingAddress);

            foreach ($cartItems as $key => $item) {
                $parts = explode('_', $key);
                $productId = $parts[0];
                $size = $parts[1] ?? 'N/A';
                $quantity = $item['qty'] ?? 1;
                $unitPrice = $item['product']['price'] ?? 0;
                $this->insertOrderItem($orderId, $productId, $size, $quantity, $unitPrice);
            }

            $this->db->commit();
            return ['success' => true, 'order_id' => $orderId];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function insertOrder($userId, $totalAmount, $totalQty, $shippingAddress) {
        $stmt = $this->db->prepare(
            "INSERT INTO orders (user_id, total_amount, total_quantity, status, shipping_address, payment_method, created_at)
             VALUES (?, ?, ?, 'Processing', ?, 'COD', NOW())"
        );
        $stmt->execute([$userId, $totalAmount, $totalQty, $shippingAddress]);
        return $this->db->lastInsertId();
    }

    private function insertOrderItem($orderId, $productId, $size, $quantity, $unitPrice) {
        // First get the variant id
        $varStmt = $this->db->prepare("SELECT id FROM product_variants WHERE product_id = ? AND size = ?");
        $varStmt->execute([$productId, $size]);
        $variant = $varStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$variant) throw new \Exception("Variant not found for product $productId size $size");

        $stmt = $this->db->prepare(
            "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price_at_purchase)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$orderId, $productId, $variant['id'], $quantity, $unitPrice]);
    }
}