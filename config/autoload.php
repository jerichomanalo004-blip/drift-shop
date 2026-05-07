<?php
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