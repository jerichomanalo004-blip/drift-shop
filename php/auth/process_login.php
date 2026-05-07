<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Core\SessionManager;

SessionManager::start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = Database::getInstance()->getConnection();

    try {
        // Check users table
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            SessionManager::set('user_id', $user['id']);
            SessionManager::set('user_name', $user['first_name']);
            SessionManager::set('role', 'customer');

            // Load wishlist
            $wishStmt = $db->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
            $wishStmt->execute([$user['id']]);
            $wishlist = [];
            while ($row = $wishStmt->fetch()) $wishlist[$row['product_id']] = true;
            SessionManager::set('wishlist', $wishlist);

            // Load cart
            $cartStmt = $db->prepare("SELECT product_id, size, quantity FROM user_cart WHERE user_id = ?");
            $cartStmt->execute([$user['id']]);
            $cart = [];
            while ($row = $cartStmt->fetch()) {
                $key = $row['product_id'] . '_' . $row['size'];
                $cart[$key] = ['qty' => $row['quantity']];
            }
            SessionManager::set('cart', $cart);

            header("Location: /shop/php/index.php");
            exit();
        }

        // Check admins table (if exists)
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($admin && ($password === 'admin123' || password_verify($password, $admin['password']))) {
            SessionManager::set('admin_id', $admin['id']);
            SessionManager::set('user_name', $admin['fullname']);
            SessionManager::set('role', 'admin');
            header("Location: /shop/php/admin/dashboard.php");
            exit();
        }

        header("Location: /shop/php/index.php?page=login&error=invalid_credentials");
        exit();

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: /shop/php/index.php?page=login&error=system_fail");
        exit();
    }
}