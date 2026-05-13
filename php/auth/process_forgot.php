<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + 1800);

    $db = Database::getInstance()->getConnection();
    try {
        $stmt = $db->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?");
        $stmt->execute([$token_hash, $expiry, $email]);

        if ($stmt->rowCount() > 0) {
            // In production, send email. For testing, redirect with token.
            header("Location: /shop/php/index.php?page=reset-password&token=$token&email=$email&success=1");
            exit();
        } else {
            header("Location: /shop/php/index.php?page=forgot-password&error=not_found");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: /shop/php/index.php?page=forgot-password&error=system_fail");
        exit();
    }
}