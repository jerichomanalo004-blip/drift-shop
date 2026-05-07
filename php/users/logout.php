<?php
require_once __DIR__ . '/../../config/autoload.php';
use Core\SessionManager;
SessionManager::start();
SessionManager::destroy();
header("Location: /shop/php/index.php");
exit();