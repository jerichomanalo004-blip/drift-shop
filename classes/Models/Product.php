<?php
namespace Models;

use Core\Model;

class Product extends Model {
    protected $table = 'products';
    protected $fillable = ['product_name', 'department', 'price', 'description', 'main_image', 'is_new', 'category_id'];

    public function getFiltered(array $filters, $sort = 'sales_desc', $limit = 12, $offset = 0) {
        $sql = "SELECT DISTINCT p.*, c.brand, c.category_name 
                FROM products p 
                INNER JOIN categories c ON p.category_id = c.id
                INNER JOIN product_variants v ON p.id = v.product_id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['brand']) && strtolower($filters['brand']) !== 'drift') {
            $sql .= " AND c.brand = ?";
            $params[] = $filters['brand'];
        }
        if (!empty($filters['department'])) {
            $sql .= " AND p.department = ?";
            $params[] = $filters['department'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND c.category_name = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['size'])) {
            $sql .= " AND v.size = ?";
            $params[] = $filters['size'];
        }
        $sql .= " AND v.stock_quantity > 0";

        if ($sort == 'price_low') {
            $order = "ORDER BY p.price ASC";
        } elseif ($sort == 'price_high') {
            $order = "ORDER BY p.price DESC";
        } else {
            $order = "ORDER BY p.id DESC";
        }
        $sql .= " $order LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTotalCount(array $filters) {
        $sql = "SELECT COUNT(DISTINCT p.id) as total 
                FROM products p 
                INNER JOIN categories c ON p.category_id = c.id
                INNER JOIN product_variants v ON p.id = v.product_id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['brand']) && strtolower($filters['brand']) !== 'drift') {
            $sql .= " AND c.brand = ?";
            $params[] = $filters['brand'];
        }
        if (!empty($filters['department'])) {
            $sql .= " AND p.department = ?";
            $params[] = $filters['department'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND c.category_name = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['size'])) {
            $sql .= " AND v.size = ?";
            $params[] = $filters['size'];
        }
        $sql .= " AND v.stock_quantity > 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    public function getVariants($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = ? AND stock_quantity > 0 ORDER BY FIELD(size, 'S','M','L','XL')");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getGallery($productId) {
        $stmt = $this->db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getReviews($productId) {
        $stmt = $this->db->prepare("SELECT product_reviews.*, users.first_name 
                                    FROM product_reviews 
                                    JOIN users ON product_reviews.user_id = users.id 
                                    WHERE product_id = ? ORDER BY created_at DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getReviewStats($productId) {
        $stmt = $this->db->prepare("SELECT COUNT(id) as total_reviews, AVG(rating) as avg_rating FROM product_reviews WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Admin methods
    public function getLowStockCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM product_variants WHERE stock_quantity < 10");
        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    public function getBrandDistribution() {
        $stmt = $this->db->query("SELECT c.brand, COUNT(p.id) as count 
            FROM products p JOIN categories c ON p.category_id = c.id GROUP BY c.brand");
        return $stmt->fetchAll();
    }

    public function getProductWithBrand($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.brand 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}