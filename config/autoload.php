<?php
define('APP_ENV', 'production');
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

spl_autoload_register(function ($class) {
    $prefixes = [
        'Core\\'     => __DIR__ . '/../classes/Core/',
        'Models\\'   => __DIR__ . '/../classes/Models/',
        'Services\\' => __DIR__ . '/../classes/Services/',
        'Traits\\'   => __DIR__ . '/../classes/Traits/',
        'Controllers\\' => __DIR__ . '/../controllers/',
    ];
    
    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});