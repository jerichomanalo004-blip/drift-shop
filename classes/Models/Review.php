<?php
namespace Models;

use Core\Model;

class Review extends Model {
    protected $table = 'product_reviews';
    protected $fillable = ['product_id', 'user_id', 'rating', 'comment'];

    public function getLatest($limit = 3) {
        $limit = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT product_reviews.*, users.first_name, products.product_name 
            FROM product_reviews 
            JOIN users ON product_reviews.user_id = users.id 
            JOIN products ON product_reviews.product_id = products.id
            ORDER BY created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function add($productId, $userId, $rating, $comment) {
        return $this->save([
            'product_id' => $productId,
            'user_id'    => $userId,
            'rating'     => $rating,
            'comment'    => $comment
        ]);
    }

    // Get all reviews for a specific product (with user name)
    public function getProductReviews($productId) {
        $stmt = $this->db->prepare("
            SELECT product_reviews.*, users.first_name 
            FROM product_reviews 
            JOIN users ON product_reviews.user_id = users.id 
            WHERE product_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Get review statistics (total count and average rating) for a product
    public function getReviewStats($productId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(id) as total_reviews, AVG(rating) as avg_rating 
            FROM product_reviews 
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
?>