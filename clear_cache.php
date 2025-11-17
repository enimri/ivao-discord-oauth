<?php
/**
 * Cache clearing utility
 * 
 * Usage: php clear_cache.php
 * Or visit: https://yourdomain.com/clear_cache.php?confirm=1
 */

// Security: Require confirmation parameter in production
$confirm = $_GET['confirm'] ?? $_SERVER['argv'][1] ?? '0';

if ($confirm !== '1' && $confirm !== 'yes') {
    echo "Cache Clear Utility\n";
    echo "===================\n\n";
    echo "To clear all caches, run:\n";
    echo "  php clear_cache.php 1\n";
    echo "Or visit: " . (isset($_SERVER['HTTP_HOST']) ? "https://{$_SERVER['HTTP_HOST']}/clear_cache.php?confirm=1" : "this script with ?confirm=1") . "\n\n";
    exit(1);
}

echo "Clearing caches...\n\n";

// 1. Clear PHP opcache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✓ PHP opcache cleared\n";
    } else {
        echo "✗ PHP opcache reset failed (may not be enabled)\n";
    }
} else {
    echo "ℹ PHP opcache not available\n";
}

// 2. Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        echo "✓ APCu cache cleared\n";
    } else {
        echo "✗ APCu cache clear failed\n";
    }
} else {
    echo "ℹ APCu not available\n";
}

// 3. Clear Python __pycache__ directories
$backendDir = __DIR__ . '/backend/src';
if (is_dir($backendDir)) {
    $cleared = false;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backendDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $path) {
        if ($path->isDir() && basename($path) === '__pycache__') {
            $files = glob($path->getPathname() . '/*.pyc');
            foreach ($files as $file) {
                @unlink($file);
            }
            if (@rmdir($path->getPathname())) {
                $cleared = true;
            }
        }
    }
    
    if ($cleared) {
        echo "✓ Python __pycache__ directories cleared\n";
    } else {
        echo "ℹ No Python __pycache__ directories found\n";
    }
} else {
    echo "ℹ Backend directory not found\n";
}

// 4. Clear session cache
session_start();
$_SESSION = [];
if (session_destroy()) {
    session_start(); // Restart session
    echo "✓ Session cache cleared\n";
} else {
    echo "ℹ Session cache clear skipped\n";
}

echo "\n✓ Cache clearing complete!\n";

