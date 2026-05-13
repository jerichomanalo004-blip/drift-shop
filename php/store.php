<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Core\Database;
use Models\Product;

SessionManager::start();

$is_customer = Auth::check();
$is_admin = Auth::isAdmin();

if (!$is_customer && !$is_admin) {
    header("Location: index.php?page=login");
    exit();
}

$db = Database::getInstance()->getConnection();

$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'Drift';
$department_filter = $_GET['filter'] ?? '';
$type_filter = $_GET['type'] ?? '';
$size_filter = $_GET['size'] ?? '';
$sort = $_GET['sort'] ?? 'sales_desc';

$cart_count = 0;
$cart_total = 0;
$cart = SessionManager::get('cart', []);
$productModel = new Product();
foreach ($cart as $key => $item) {
    $cart_count += $item['qty'];
    $pid = explode('_', $key)[0];
    $product = $productModel->find($pid);
    if ($product) $cart_total += $product['price'] * $item['qty'];
}

$wishlist = SessionManager::get('wishlist', []);
$wishlist_count = count($wishlist);

$filters = [
    'search' => $search_query,
    'brand' => $category_filter,
    'department' => $department_filter,
    'type' => $type_filter,
    'size' => $size_filter
];

$limit = 12;
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$offset = ($page_num - 1) * $limit;

$products = $productModel->getFiltered($filters, $sort, $limit, $offset);
$total_items = $productModel->getTotalCount($filters);
$total_pages = ceil($total_items / $limit);

function getProductCount($db, $brand, $department = null, $type = null, $size = null) {
    $sql = "SELECT COUNT(DISTINCT p.id) as total 
            FROM products p 
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants v ON p.id = v.product_id 
            WHERE 1=1";
    $params = [];
    if ($brand && strtolower($brand) != 'drift' && $brand != 'default') {
        $sql .= " AND c.brand = ?";
        $params[] = $brand;
    }
    if ($department) {
        $sql .= " AND p.department = ?";
        $params[] = $department;
    }
    if ($type) {
        $sql .= " AND c.category_name = ?";
        $params[] = $type;
    }
    if ($size) {
        $sql .= " AND v.size = ?";
        $params[] = $size;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>DRIFT Store | Browse All</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/shop/css/theme.css">
    <link rel="stylesheet" href="/shop/css/store.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/shop/css/login_reg.css?v=<?= time() ?>">
</head>
<body>

<?php 
$page = $_GET['page'] ?? 'store';
if (in_array($page, ['login', 'register', 'forgot-password', 'reset-password'])): 
?>
<div class="auth-overlay">
    <div class="auth-container">
        <a href="store.php" class="close-btn">&times;</a>
        <?php 
            if ($page == 'login') include('auth/login.php');
            elseif ($page == 'register') include('auth/register.php');
            elseif ($page == 'forgot-password') include('auth/forgot_password.php');
            elseif ($page == 'reset-password') include('auth/reset_password.php');
        ?>
    </div>
</div>
<?php endif; ?>

<!-- TOP BAR (with My Account / Back to Dashboard) -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-left">
            Free shipping · marketing@drift.com · contact@drift.com
        </div>
        <div class="top-bar-center"></div>
        <div class="top-bar-right">
            <?php if ($is_admin): ?>
                <a href="admin/dashboard.php" class="my-account" style="color: #ff4747;">Back to Dashboard</a>
            <?php else: ?>
                <a href="users/customer.php" class="my-account">
                    <img width="18" height="18" src="https://img.icons8.com/fluency-systems-regular/48/user.png" alt="user" style="vertical-align: middle; margin-right: 5px;"/>
                    My Account
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MAIN HEADER (logo, search, cart/wishlist) -->
<header class="main-header">
    <div class="container">
        <div class="logo-area">
            <a href="index.php"><h1>DRIFT</h1></a>
        </div>
        <div class="header-search">
            <form action="store.php" method="GET">
                <?php if(isset($_GET['category'])): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($_GET['category']) ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Search" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="search-btn">🔍</button>
            </form>
        </div>
        <div class="header-actions">
            <?php if ($is_customer): ?>
                <div class="action-item" onclick="location.href='addto/wishlist.php'">
                    <img width="22" height="22" src="https://img.icons8.com/ios-glyphs/30/like--v1.png" alt="wishlist">
                    <span class="count" id="wishlist-count-badge"><?= $wishlist_count ?></span>
                </div>
                <div class="cart-widget" onclick="location.href='addto/cart.php'">
                    <img width="22" height="22" src="https://img.icons8.com/fluency-systems-regular/48/shopping-cart.png" alt="cart">
                    <div class="cart-text">
                        <span class="label">Item(<span id="cart-count-display"><?= $cart_count ?></span>) - <span id="cart-total-display">₱<?= number_format($cart_total, 2) ?></span></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="preview-badge">PREVIEW MODE</div>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- CATEGORY NAVIGATION -->
<nav class="category-nav">
    <div class="container">
        <ul>
            <li><a href="store.php">All</a></li>
            <li><a href="store.php?category=DBTK">DBTK</a></li>
            <li><a href="store.php?category=HIGHMINDS">HIGHMINDS</a></li>
            <li><a href="store.php?category=CHARLOTTE FOLK">CHARLOTTE FOLK</a></li>
        </ul>
    </div>
</nav>

<!-- BREADCRUMB -->
<section class="subheader">
    <div class="container">
        <div class="subheader-nav">
            <a href="store.php">Shop</a> / 
            <span><?= strtoupper($category_filter == 'Drift' ? 'All' : $category_filter) ?></span>
            <?php if ($department_filter): ?> / <span><?= ucfirst($department_filter) ?></span><?php endif; ?>
            <?php if ($type_filter): ?> / <span><?= ucfirst($type_filter) ?></span><?php endif; ?>
        </div>
    </div>
</section>

<!-- MAIN CONTENT (SIDEBAR + PRODUCTS) -->
<main class="store-layout">
    <div class="container store-grid">
        <!-- SIDEBAR -->
        <aside class="store-sidebar">
            <div class="sidebar-block">
                <h3 class="sidebar-title">Shop by category</h3>
                <ul class="category-tree">
                    <?php foreach (['men', 'women', 'unisex'] as $d): ?>
                        <li class="has-dropdown">
                            <div class="category-main">
                                <a href="store.php?category=<?= urlencode($category_filter) ?>&filter=<?= $d ?>"><?= ucfirst($d) ?></a>
                                <span class="toggle-btn">+</span>
                            </div>
                            <ul class="dropdown-content">
                                <li><a href="store.php?category=<?= urlencode($category_filter) ?>&filter=<?= $d ?>&type=t-shirt">T‑Shirts (<?= getProductCount($db, $category_filter, $d, 'T-Shirt') ?>)</a></li>
                                <li><a href="store.php?category=<?= urlencode($category_filter) ?>&filter=<?= $d ?>&type=hoodies">Hoodies (<?= getProductCount($db, $category_filter, $d, 'Hoodies') ?>)</a></li>
                                <li><a href="store.php?category=<?= urlencode($category_filter) ?>&filter=<?= $d ?>&type=jacket">Jackets (<?= getProductCount($db, $category_filter, $d, 'Jacket') ?>)</a></li>
                                <li><a href="store.php?category=<?= urlencode($category_filter) ?>&filter=<?= $d ?>&type=sweatshirt">Sweatshirts (<?= getProductCount($db, $category_filter, $d, 'Sweatshirt') ?>)</a></li>
                                <li><a href="store.php?category=<?= urlencode($category_filter) ?>&filter=<?= $d ?>&type=long sleeves">Long sleeves (<?= getProductCount($db, $category_filter, $d, 'Long Sleeves') ?>)</a></li>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sidebar-block">
                <h3 class="sidebar-title">Size</h3>
                <div class="size-filter-buttons">
                    <?php foreach (['S','M','L','XL'] as $s): 
                        $isActive = (isset($_GET['size']) && $_GET['size'] == $s) ? 'active' : '';
                    ?>
                    <button class="size-filter-btn <?= $isActive ?>" data-size="<?= $s ?>" onclick="applySizeFilter(this)">
                        <?= $s ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- PRODUCT GRID -->
        <section class="store-content">
            <div class="product-toolbar">
                <div class="toolbar-left">
                    <div class="view-toggles">
                        <button onclick="setView('grid')" id="grid-btn" class="active">
                            <img src="https://img.icons8.com/material-outlined/24/grid-2.png" alt="grid">
                        </button>
                        <button onclick="setView('list')" id="list-btn">
                            <img src="https://img.icons8.com/material-outlined/24/list.png" alt="list">
                        </button>
                    </div>
                    <span class="product-info"><?= $offset + 1 ?>–<?= min($offset + $limit, $total_items) ?> of <?= $total_items ?> items</span>
                </div>
                <div class="toolbar-right">
                    <span>Sort by:</span>
                    <select class="sort-select">
                        <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price low to high</option>
                        <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price high to low</option>
                    </select>
                </div>
            </div>

            
            <div id="product-container" class="grid-view">
                <?php foreach ($products as $product): 
                    $gallery = $productModel->getGallery($product['id']);
                    $hover_img = $gallery[0]['image_path'] ?? '';
                ?>
                <div class="product-item">
                    <div class="product-img" onclick="openProductModal(<?= $product['id'] ?>)" style="cursor:pointer;">
                        <img src="/shop/<?= htmlspecialchars($product['main_image']) ?>" class="primary-img" alt="<?= htmlspecialchars($product['product_name']) ?>">
                        <?php if ($hover_img): ?>
                            <img src="/shop/<?= htmlspecialchars($hover_img) ?>" class="hover-img" alt="hover">
                        <?php endif; ?>
                        <?php if ($product['is_new']): ?><span class="badge">NEW</span><?php endif; ?>
                    </div>
                    <div class="product-details">
                        <span class="brand-name"><?= htmlspecialchars($product['brand']) ?></span>
                        <h4 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h4>
                        <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-footer">
                            <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                            <?php if ($is_customer): ?>
                                <button class="add-to-cart" onclick="openProductModal(<?= $product['id'] ?>)">ADD TO CART</button>
                            <?php else: ?>
                                <button class="add-to-cart" disabled>PREVIEW ONLY</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <p style="text-align:center; grid-column:1/-1;">No products found.</p>
                <?php endif; ?>
            </div>
            <div class="pagination-container">
                <div class="pagination-buttons">
                    <?php if($page_num > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['p' => $page_num - 1])) ?>" class="page-btn">&lt;</a>
                    <?php endif; ?>
                    <?php
                    $range = 1;
                    $start = max(1, $page_num - $range);
                    $end = min($total_pages, $page_num + $range);
                    if($start > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['p' => 1])) ?>" class="page-btn">1</a>
                        <?php if($start > 2): ?><span class="page-dots">...</span><?php endif; ?>
                    <?php endif;
                    for($i = $start; $i <= $end; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>" class="page-btn <?= ($i == $page_num) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor;
                    if($end < $total_pages): ?>
                        <?php if($end < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['p' => $total_pages])) ?>" class="page-btn"><?= $total_pages ?></a>
                    <?php endif;
                    if($page_num < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['p' => $page_num + 1])) ?>" class="page-btn">&gt;</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- MODAL -->
<div id="product-modal" class="auth-overlay" style="display:none;">
    <div class="auth-container product-modal-container">
        <a href="javascript:void(0)" class="close-btn" onclick="closeProductModal()">&times;</a>
        <div id="modal-body"><p style="padding:20px;">Loading product details...</p></div>
    </div>
</div>

<script>const isAdmin = <?= $is_admin ? 'true' : 'false' ?>;</script>
<script src="/shop/php/js/store.js"></script>
<script>
    const searchInput = document.querySelector('.header-search input[name="search"]');
    const productContainer = document.getElementById('product-container');
    let timer;

    function updateSearch() {
        const searchValue = searchInput.value;
        const url = new URL(window.location.href);
        url.searchParams.set('search', searchValue);
        fetch(url)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.getElementById('product-container');
                if (newContainer) {
                    productContainer.innerHTML = newContainer.innerHTML;
                }
                // Optionally also update pagination
                const newPagination = doc.querySelector('.pagination-container');
                const oldPagination = document.querySelector('.pagination-container');
                if (newPagination && oldPagination) {
                    oldPagination.outerHTML = newPagination.outerHTML;
                }
            });
    }

    searchInput?.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(updateSearch, 400);
    });

    
(function() {
    const originalSetView = window.setView;
    if (typeof originalSetView === 'function') {
        window.setView = function(viewType) {
            originalSetView(viewType);
            localStorage.setItem('storeView', viewType);
        };
    }
    const savedView = localStorage.getItem('storeView');
    if (savedView === 'list' && typeof window.setView === 'function') {
        setTimeout(function() {
            window.setView('list');
        }, 50);
    }
})();
</script>
</body>
</html>