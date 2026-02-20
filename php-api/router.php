<?php

/**
 * Router for PHP built-in server.
 * Run: php -S localhost:8080 -t . router.php
 * This ensures all requests go through index.php so config/database.php and routes load.
 */
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// Serve static files if they exist (optional)
if (preg_match('#^/(assets|vendor)/#', $path) && file_exists(__DIR__ . $path)) {
    return false; // let built-in server serve the file
}

// Everything else goes through index.php
require __DIR__ . '/index.php';
return true;
