<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;
use Models\Review;

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && Auth::check()) {
    $product_id = (int)$_POST['product_id'];
    $user_id = SessionManager::get('user_id');
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        header("Location: review.php?id=$product_id&error=invalid_rating");
        exit;
    }
    if (empty($comment)) {
        header("Location: review.php?id=$product_id&error=empty_comment");
        exit;
    }

    $reviewModel = new Review();
    $success = $reviewModel->add($product_id, $user_id, $rating, $comment);

    if ($success) {
        header("Location: /shop/php/store.php?open_id=$product_id&review=success");
    } else {
        header("Location: review.php?id=$product_id&error=system_fail");
    }
    exit;
}

header("Location: /shop/php/store.php");
exit;