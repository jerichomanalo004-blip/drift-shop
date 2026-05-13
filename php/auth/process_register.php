<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Models\User;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $birthdate  = $_POST['birthdate'];
    $gender     = $_POST['gender'];
    $contact    = trim($_POST['contact']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // --- Validation ---

    // Birthdate validation and age calculation
    $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$birthDateObj) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=system_fail");
        exit();
    }
    $today = new DateTime();
    $age = $today->diff($birthDateObj)->y;
    if ($age < 18) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=underage");
        exit();
    }
    if ($age > 150) {
        session_start();
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=invalid_age");
        exit();
    }

    // Contact number exactly 11 digits
    if (strlen($contact) !== 11 || !ctype_digit($contact)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=invalid_contact");
        exit();
    }

    // Password match
    if ($password !== $confirm) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=password_mismatch");
        exit();
    }

    // Strong password validation
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $password)) {
        header("Location: /shop/php/index.php?page=register&error=weak_password");
        exit();
    }

    // Email uniqueness check
    $db = Database::getInstance()->getConnection();
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=email_exists");
        exit();
    }

    // Build user data (address omitted – will be NULL)
    $userData = [
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'birthdate'      => $birthdate,
        'age'            => $age,
        'gender'         => $gender,
        'contact_number' => $contact,
        'email'          => $email,
        'password'       => $password,   // will be hashed inside register()
    ];

    $userModel = new User();
    if ($userModel->register($userData)) {
        // Clear form data on success
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['form_data']);
        header("Location: /shop/php/index.php?page=login&success=1");
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_data'] = $_POST;
        header("Location: /shop/php/index.php?page=register&error=system_fail");
    }
    exit();
}