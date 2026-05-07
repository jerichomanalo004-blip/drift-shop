<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRIFT / Intel Suite</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/shop/php/admin/css/admin.css?v=<?= time() ?>">
</head>
<body>

<header class="top-header">
    <a href="dashboard.php" class="brand-block">
        <span class="brand-title">DRIFT</span>
        <span class="brand-subtitle">INTEL SUITE</span>
    </a>
    <nav class="global-links">
        <a href="dashboard.php" class="global-link">Overview</a>
        <a href="/shop/php/index.php" class="global-link">Visit Store</a>
    </nav>
</header>

<div class="suite-wrapper">
    <aside class="sidebar">
        <div class="sidebar-group-label">Analysis Core</div>
        <ul class="node-list">
            <?php 
                $current = basename($_SERVER['PHP_SELF']);
                $nodes = [
                    'dashboard.php' => ['name' => 'General Overview', 'icon' => '📈'],
                    'products.php' => ['name' => 'Inventory Matrix', 'icon' => '📦'],
                    'orders.php' => ['name' => 'Order Intel', 'icon' => '📋'],
                    'reports.php' => ['name' => 'Revenue Analytics', 'icon' => '📊']
                ];
                foreach ($nodes as $url => $info):
                    $active = ($current == $url) ? 'active' : '';
            ?>
                <li class="node-item">
                    <a href="<?= $url ?>" class="node-link <?= $active ?>">
                        <span class="node-icon"><?= $info['icon'] ?></span>
                        <?= $info['name'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="sidebar-group-label">Intelligence Nodes</div>
        <ul class="node-list">
            <li class="node-item">
                <a href="inventory_history.php" class="node-link <?= ($current == 'inventory_history.php') ? 'active' : '' ?>">
                    <span class="node-icon">📂</span> Stock Velocity
                </a>
            </li>
            <li class="node-item">
                <a href="admin_reviews.php" class="node-link <?= ($current == 'admin_reviews.php') ? 'active' : '' ?>">
                    <span class="node-icon">💬</span> Sentiment Data
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="/shop/php/users/logout.php" class="terminate-btn">Logout</a>
        </div>
    </aside>

    <main class="main-content">