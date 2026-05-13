<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/autoload.php';
use Core\SessionManager;
SessionManager::start();

$controller = new \Controllers\HomeController();
$controller->index();