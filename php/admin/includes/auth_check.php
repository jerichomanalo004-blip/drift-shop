<?php
require_once __DIR__ . '/../../../config/autoload.php';

use Core\SessionManager;
use Core\Auth;

SessionManager::start();

if (!Auth::isAdmin()) {
    header("Location: /shop/php/admin/login.php");
    exit();
}