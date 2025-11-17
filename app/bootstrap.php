<?php
/**
 * Bootstrap file - for root-level structure
 */

// Clear opcache for this file if opcache is enabled and cache issues occur
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
require_once __DIR__ . '/config/config.php';

