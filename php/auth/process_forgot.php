<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\CSRF;
use Core\Database;
use Core\SessionManager;

SessionManager::start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /shop/php/index.php?page=forgot-password");
    exit();
}

if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
    header("Location: /shop/php/index.php?page=forgot-password&error=invalid_csrf");
    exit();
}

$db = Database::getInstance()->getConnection();

$email = trim(strtolower($_POST['email'] ?? ''));
$securityAnswer = trim($_POST['security_answer'] ?? '');

if ($securityAnswer === '') {
    // Step 1: verify email and choose security question.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: /shop/php/index.php?page=forgot-password&error=not_found");
        exit();
    }

    try {
        $stmt = $db->prepare("SELECT birthdate, contact_number FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location: /shop/php/index.php?page=forgot-password&error=system_fail");
        exit();
    }

    if (!$user) {
        SessionManager::remove('reset_verify_email');
        SessionManager::remove('reset_verify_question');
        header("Location: /shop/php/index.php?page=forgot-password&error=not_found");
        exit();
    }

    $availableQuestions = [];
    if (!empty($user['birthdate'])) {
        $availableQuestions[] = 'birthdate';
    }
    if (!empty($user['contact_number'])) {
        $availableQuestions[] = 'contact_number';
    }

    if (empty($availableQuestions)) {
        SessionManager::remove('reset_verify_email');
        SessionManager::remove('reset_verify_question');
        header("Location: /shop/php/index.php?page=forgot-password&error=system_fail");
        exit();
    }

    $chosenQuestion = $availableQuestions[array_rand($availableQuestions)];
    SessionManager::set('reset_verify_email', $email);
    SessionManager::set('reset_verify_question', $chosenQuestion);

    header("Location: /shop/php/index.php?page=forgot-password");
    exit();
}

// Step 2: verify security answer and generate token.
$storedEmail = SessionManager::get('reset_verify_email');
$chosenQuestion = SessionManager::get('reset_verify_question');

if (empty($storedEmail) || empty($chosenQuestion)) {
    header("Location: /shop/php/index.php?page=forgot-password&error=no_question");
    exit();
}

try {
    $stmt = $db->prepare("SELECT id, birthdate, contact_number FROM users WHERE LOWER(email) = ? LIMIT 1");
    $stmt->execute([strtolower($storedEmail)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: /shop/php/index.php?page=forgot-password&error=system_fail");
    exit();
}

if (!$user) {
    SessionManager::remove('reset_verify_email');
    SessionManager::remove('reset_verify_question');
    header("Location: /shop/php/index.php?page=forgot-password&error=no_question");
    exit();
}

$valid = false;
if ($chosenQuestion === 'birthdate') {
    $submittedDate = date_create($securityAnswer);
    $storedDate = $user['birthdate'] ? date_create($user['birthdate']) : false;
    if ($submittedDate && $storedDate) {
        $valid = $submittedDate->format('Y-m-d') === $storedDate->format('Y-m-d');
    }
} elseif ($chosenQuestion === 'contact_number') {
    $normalizedSubmitted = preg_replace('/\D+/', '', $securityAnswer);
    $normalizedStored = preg_replace('/\D+/', '', $user['contact_number'] ?? '');
    $valid = $normalizedSubmitted !== '' && $normalizedSubmitted === $normalizedStored;
}

if (!$valid) {
    header("Location: /shop/php/index.php?page=forgot-password&error=invalid_answer");
    exit();
}

$token = bin2hex(random_bytes(16));
$token_hash = hash('sha256', $token);
$expiry = date('Y-m-d H:i:s', time() + 1800);

try {
    $stmt = $db->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
    $stmt->execute([$token_hash, $expiry, $user['id']]);
} catch (PDOException $e) {
    header("Location: /shop/php/index.php?page=forgot-password&error=system_fail");
    exit();
}

SessionManager::remove('reset_verify_email');
SessionManager::remove('reset_verify_question');
header("Location: /shop/php/index.php?page=reset-password&token=$token&success=1");
exit();