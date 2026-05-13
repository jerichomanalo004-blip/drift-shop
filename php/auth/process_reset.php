<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Models\User;

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$token) {
    header('Location: /shop/php/index.php?page=reset_password&error=invalid_token');
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: /shop/php/index.php?page=reset_password&token=' . urlencode($token) . '&error=mismatch');
    exit;
}

$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
if (!preg_match($passwordPattern, $password)) {
    header('Location: /shop/php/index.php?page=reset_password&token=' . urlencode($token) . '&error=weak_password');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT user_id, expires_at, used FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset || $reset['used'] || strtotime($reset['expires_at']) < time()) {
        header('Location: /shop/php/index.php?page=reset_password&error=invalid_token');
        exit;
    }

    $userId = $reset['user_id'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $updateUser = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateUser->execute([$hashedPassword, $userId]);

    $markUsed = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $markUsed->execute([$token]);

    header('Location: /shop/php/index.php?page=login&reset=success');
    exit;
} catch (Exception $e) {
    header('Location: /shop/php/index.php?page=reset_password&token=' . urlencode($token) . '&error=system_fail');
    exit;
}