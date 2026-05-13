<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>DRIFT | The Premium Topwear Collection</title>
    <link rel="stylesheet" href="/shop/css/landingPage.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/shop/css/login_reg.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
</head>

<?php if (in_array($page, ['login', 'register', 'forgot-password', 'reset-password'])): ?>
<div class="auth-overlay">
    <div class="auth-container">
        <a href="/shop/php/index.php" class="close-btn">&times;</a>
        <?php
            // Auth views are in shop/php/auth/
            $authFile = __DIR__ . '/../../php/auth/' . $page . '.php';
            if ($page == 'forgot-password') $authFile = __DIR__ . '/../../php/auth/forgot_password.php';
            if ($page == 'reset-password') $authFile = __DIR__ . '/../../php/auth/reset_password.php';
            if (file_exists($authFile)) require $authFile;
        ?>
    </div>
</div>
<?php endif; ?>

<body style="<?= (in_array($page, ['login','register'])) ? 'overflow: hidden;' : '' ?>">

<nav class="navbar">
    <div class="logo">DRIFT</div>
    <ul class="nav-links">
        <li><a href="#home">HOME</a></li>
        <li><a href="#categories">CATEGORIES</a></li>
        <li><a href="#seasonal">SEASONAL</a></li>
        <li><a href="/shop/php/store.php">SHOPS</a></li>
    </ul>
    <div class="nav-actions">
        <?php if ($is_customer): ?>
            <a href="/shop/php/users/customer.php" class="user-greeting">
                <img width="18" height="18" src="https://img.icons8.com/fluency-systems-regular/48/user.png" alt="user icon" style="vertical-align: middle; margin-right: 5px;"/>
                Hi, <?= htmlspecialchars($display_name) ?>
            </a>
            <a href="/shop/php/users/logout.php" class="btn-login" style="margin-left:10px;">Logout</a>

        <?php elseif ($is_admin): ?>
            <span style="font-size: 0.7rem; color: #888; margin-right: 10px;">ADMIN MODE</span>
            <a href="/shop/php/admin/dashboard.php" class="btn-reg" style="background: #ff4747;">Dashboard</a>
            <a href="/shop/php/users/logout.php" class="btn-login">Logout</a>

        <?php else: ?>
            <a href="/shop/php/index.php?page=register" class="btn-reg">Register</a>
            <a href="/shop/php/index.php?page=login" class="btn-login">Login</a>
        <?php endif; ?>
    </div>
</nav>

<?php require __DIR__ . '/../home/index.php'; ?>

<footer class="simple-footer">
    <p class="disclaimer">This is a student project. All product images and brand names are used for educational purposes only. No commercial use intended.</p>
    <p class="copyright">&copy; 2026 DRIFT. All rights reserved.</p>
</footer>

<script src="/shop/php/js/landing.js?v=<?= time() ?>"></script>
</body>
</html>