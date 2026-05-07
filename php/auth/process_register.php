<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $age        = (int)$_POST['age'];
    $gender     = $_POST['gender'];
    $contact    = $_POST['contact'];
    $address    = $_POST['address'];
    $email      = $_POST['email'];
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    if ($age < 18) {
        header("Location: /shop/php/index.php?page=register&error=underage");
        exit();
    }
    if (strlen($contact) !== 11 || !ctype_digit($contact)) {
        header("Location: /shop/php/index.php?page=register&error=invalid_contact");
        exit();
    }
    if ($password !== $confirm) {
        header("Location: /shop/php/index.php?page=register&error=password_mismatch");
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $db = Database::getInstance()->getConnection();
    try {
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, age, gender, contact_number, address, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $age, $gender, $contact, $address, $email, $hashed]);
        header("Location: /shop/php/index.php?page=login&success=1");
        exit();
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            header("Location: /shop/php/index.php?page=register&error=email_exists");
        } else {
            header("Location: /shop/php/index.php?page=register&error=system_fail");
        }
        exit();
    }
}