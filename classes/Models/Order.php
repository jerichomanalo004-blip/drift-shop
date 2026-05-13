<?php
namespace Models;

use Core\Model;
use Services\InventoryLogger;

class Order extends Model {
    protected $table = 'orders';
    protected $fillable = ['user_id', 'total_amount', 'total_quantity', 'status'];

    public function createFromCart($userId, $cartItems, $cartTotal, $cartQty) {
        $this->db->beginTransaction();
        try {
            $orderId = $this->insertOrder($userId, $cartTotal, $cartQty);
            foreach ($cartItems as $key => $item) {
                $this->insertOrderItem($orderId, $key, $item['qty']);
            }
            $this->db->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function insertOrder($userId, $total, $qty) {
        $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_amount, total_quantity, status, created_at) VALUES (?, ?, ?, 'Processing', NOW())");
        $stmt->execute([$userId, $total, $qty]);
        return $this->db->lastInsertId();
    }

    private function insertOrderItem($orderId, $cartKey, $qty) {
        $parts = explode('_', $cartKey);
        $productId = $parts[0];
        $size = $parts[1] ?? 'N/A';
        $varStmt = $this->db->prepare("SELECT id FROM product_variants WHERE product_id = ? AND size = ?");
        $varStmt->execute([$productId, $size]);
        $variant = $varStmt->fetch(\PDO::FETCH_ASSOC);
        $priceStmt = $this->db->prepare("SELECT price FROM products WHERE id = ?");
        $priceStmt->execute([$productId]);
        $price = $priceStmt->fetchColumn();

        $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $productId, $variant['id'], $qty, $price]);

        // Update stock
        $update = $this->db->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $update->execute([$qty, $variant['id']]);

        InventoryLogger::log($variant['id'], -$qty, 'sale', "Order #$orderId", $orderId);
    }

    public function getUserOrders($userId, $limit = 10, $offset = 0, $month = '', $year = '', $status = '') {
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $where = ['user_id = ?'];
        $params = [$userId];
        
        if ($month) {
            $where[] = "MONTH(created_at) = ?";
            $params[] = (int)$month;
        }
        if ($year) {
            $where[] = "YEAR(created_at) = ?";
            $params[] = (int)$year;
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereSql = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE {$whereSql} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function countUserOrders($userId, $month = '', $year = '', $status = '') {
        $where = ['user_id = ?'];
        $params = [$userId];
        
        if ($month) {
            $where[] = "MONTH(created_at) = ?";
            $params[] = (int)$month;
        }
        if ($year) {
            $where[] = "YEAR(created_at) = ?";
            $params[] = (int)$year;
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereSql = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE {$whereSql}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("SELECT oi.*, p.product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function cancel($orderId, $userId) {
        $order = $this->find($orderId);
        if (!$order || $order['user_id'] != $userId || strtolower($order['status']) !== 'processing') return false;
        $this->db->beginTransaction();
        try {
            $items = $this->getOrderItems($orderId);
            foreach ($items as $item) {
                $this->db->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['variant_id']]);
                InventoryLogger::log($item['variant_id'], $item['quantity'], 'return', "Order cancelled #$orderId", $orderId);
            }
            $this->update(['id' => $orderId, 'status' => 'Cancelled']);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Admin methods
    public function getTotalRevenue() {
        $stmt = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'Cancelled'");
        return (float) $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    }

    public function getPendingCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Processing'");
        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    public function getTotalCost() {
        $stmt = $this->db->query("SELECT SUM(oi.quantity * pv.cost_price) as total 
            FROM order_items oi 
            JOIN product_variants pv ON oi.variant_id = pv.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status != 'Cancelled'");
        return (float) $stmt->fetchColumn();
    }
    public function getRevenueByMonth($yearMonth) {
        $stmt = $this->db->prepare("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled' AND created_at LIKE ?");
        $stmt->execute(["$yearMonth%"]);
        return (float) $stmt->fetchColumn();
    }

    public function getCostByMonth($yearMonth) {
        $stmt = $this->db->prepare("SELECT SUM(oi.quantity * pv.cost_price) 
            FROM order_items oi
            JOIN product_variants pv ON oi.variant_id = pv.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status != 'Cancelled' AND o.created_at LIKE ?");
        $stmt->execute(["$yearMonth%"]);
        return (float) $stmt->fetchColumn();
    }

    public function getProfitByMonth($yearMonth) {
        return $this->getRevenueByMonth($yearMonth) - $this->getCostByMonth($yearMonth);
    }
    public function getCategorySales() {
        $sql = "
            SELECT c.category_name, COALESCE(SUM(oi.quantity), 0) as units
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN (
                SELECT variant_id, SUM(quantity) as quantity
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id AND o.status != 'Cancelled'
                GROUP BY variant_id
            ) oi ON oi.variant_id = pv.id
            GROUP BY c.category_name
            ORDER BY units DESC
            LIMIT 5
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function getRecentOrders($limit) {
        $limit = (int)$limit;
        $stmt = $this->db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCategorySalesByMonth($year = null, $month = null) {
        if ($year === null) {
            $year = date('Y');
        }
        if ($month === null) {
            $month = date('m');
        }

        $sql = "
            SELECT c.category_name, COALESCE(SUM(oi.quantity), 0) as units
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN (
                SELECT variant_id, SUM(quantity) as quantity
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id AND o.status != 'Cancelled'
                WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ?
                GROUP BY variant_id
            ) oi ON oi.variant_id = pv.id
            GROUP BY c.category_name
            ORDER BY units DESC
            LIMIT 5
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getBrandSalesByMonth($year = null, $month = null) {
        if ($year === null) {
            $year = date('Y');
        }
        if ($month === null) {
            $month = date('m');
        }

        $sql = "
            SELECT c.brand, COALESCE(SUM(oi.quantity), 0) as units
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN (
                SELECT variant_id, SUM(quantity) as quantity
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id AND o.status != 'Cancelled'
                WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ?
                GROUP BY variant_id
            ) oi ON oi.variant_id = pv.id
            WHERE c.brand IS NOT NULL AND c.brand != ''
            GROUP BY c.brand
            ORDER BY units DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}