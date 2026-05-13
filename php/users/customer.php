<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Models\User;

SessionManager::start();

if (!Auth::check()) {
    header('Location: /shop/php/index.php');
    exit;
}

$userId = SessionManager::get('user_id');
$userModel = new User();

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $data = [
        'first_name' => $_POST['first_name'],
        'last_name'  => $_POST['last_name'],
        'email'      => $_POST['email'],
        'address'    => $_POST['address']
    ];
    $userModel->updateProfile($userId, $data);
    header("Location: customer.php?status=updated");
    exit();
}

$user = $userModel->find($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings | DRIFT</title>
    <link rel="stylesheet" href="/shop/css/customer_portal.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
</head>
<body>

<div class="account-wrapper">
    <aside class="account-sidebar">
        <div class="logo">DRIFT</div>
        <ul class="sidebar-menu">
            <li><a href="customer.php" class="active">Account Settings</a></li>
            <li><a href="orders.php">Order History</a></li>
            <li><a href="/shop/php/store.php">Return to Store</a></li>
        </ul>
    </aside>

    <main class="account-content">
        <div class="account-header">
            <h2>Settings</h2>
            <?php if(isset($_GET['status'])): ?>
                <span style="font-weight: 800; font-size: 12px; color: green;">CHANGES SAVED</span>
            <?php endif; ?>
        </div>

        <form action="customer.php" method="POST" id="profileForm">
            <div class="info-grid">
                <div class="info-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                </div>
                <div class="info-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                </div>
                <div class="info-group" style="grid-column: 1 / -1;">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="info-group" style="grid-column: 1 / -1;">
                    <label>Shipping Address</label>
                    <textarea name="address" rows="4"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
            </div>
        </form>
        <div class="account-footer">
            <button type="submit" form="profileForm" name="update_profile" class="btn-save">Update Profile</button>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </main>
</div>

</body>
</html>