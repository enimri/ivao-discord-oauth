<?php
/**
 * Main entry point - for Plesk with Document Root at domain root
 * This file should be at the domain root (discord.(your-division).ivao.aero/)
 */

// Error reporting - set to false in production
$debug_mode = true; // Change to false in production
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Clear opcache if needed (for development/debugging)
if (function_exists('opcache_reset') && ini_get('opcache.enable') && ($_GET['clear_cache'] ?? '') === '1') {
    opcache_reset();
}

// Start session
session_start();

// Define base paths (for root-level structure)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/app');
}
if (!defined('TEMPLATES_PATH')) {
    define('TEMPLATES_PATH', __DIR__ . '/templates');
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', __DIR__ . '/assets');
}

// Load bootstrap
try {
    require_once APP_PATH . '/bootstrap.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo "<h1>Bootstrap Error</h1>";
    echo "<p>Error loading bootstrap: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// Load router
try {
    $router = new \App\Core\Router();

    // Define routes
    $router->get('/', 'App\Controllers\AuthController@index');
    $router->get('/auth/ivao', 'App\Controllers\AuthController@ivaoCallback');
    $router->get('/auth/discord', 'App\Controllers\AuthController@discordCallback');
    $router->get('/success', 'App\Controllers\AuthController@success');
    $router->get('/maintenance', 'App\Controllers\AuthController@maintenance');

    // Handle the request
    $router->dispatch();
} catch (Throwable $e) {
    http_response_code(500);
    echo "<h1>Application Error</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

