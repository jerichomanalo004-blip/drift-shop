<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        header("Location: /shop/php/index.php?page=reset-password&token=$token&error=mismatch");
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $token_hash = hash("sha256", $token);
    $now = date("Y-m-d H:i:s");

    $db = Database::getInstance()->getConnection();
    try {
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE reset_token_hash = ? AND reset_token_expires_at > ?");
        $stmt->execute([$hashed, $token_hash, $now]);

        if ($stmt->rowCount() > 0) {
            header("Location: /shop/php/index.php?page=login&success=1");
        } else {
            header("Location: /shop/php/index.php?page=reset-password&token=$token&error=invalid_token");
        }
        exit();
    } catch (PDOException $e) {
        header("Location: /shop/php/index.php?page=reset-password&token=$token&error=system_fail");
        exit();
    }
}