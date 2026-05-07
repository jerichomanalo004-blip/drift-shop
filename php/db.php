<?php

require_once __DIR__ . '/../config/autoload.php';

use Core\Database;

try {
    $db = Database::getInstance()->getConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

return $db;
?>