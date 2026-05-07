<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

$db = Database::getInstance()->getConnection();

// Delete review
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $db->prepare("DELETE FROM product_reviews WHERE id = ?")->execute([$delId]);
    header("Location: admin_reviews.php?deleted=1");
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total = (int)$db->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
$totalPages = ceil($total / $limit);

$sql = "SELECT r.id, r.rating, r.comment, r.created_at, p.product_name, CONCAT(u.first_name, ' ', u.last_name) AS username 
        FROM product_reviews r
        JOIN products p ON r.product_id = p.id
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC LIMIT $limit OFFSET $offset";
$reviews = $db->query($sql)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / FEEDBACK</div>
    <h1>Customer Reviews Management</h1>
    <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

<div class="main-content">
    <div class="table-box">
        <table class="data-table">
            <thead><tr><th>Timestamp</th><th>Customer</th><th>Product</th><th>Rating</th><th>Review Content</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($reviews as $rev): ?>
                <tr>
                    <td style="font-family:'JetBrains Mono'; font-size:12px;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($rev['username']) ?></strong></td>
                    <td><?= htmlspecialchars($rev['product_name']) ?></td>
                    <td class="rating-text"><?= $rev['rating'] ?>.0 / 5.0</td>
                    <td class="comment-text">"<?= htmlspecialchars($rev['comment']) ?>"</td>
                    <td><a href="?delete_id=<?= $rev['id'] ?>" onclick="return confirm('Delete this review?')" style="color:var(--danger); text-decoration:none;">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=1">First</a><?php endif; ?>
        <?php for ($i = max(1, $page-1); $i <= min($totalPages, $page+1); $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $totalPages ?>">Last</a><?php endif; ?>
    </div>
    <div class="footer">DRIFT Feedback Terminal © 2026</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>