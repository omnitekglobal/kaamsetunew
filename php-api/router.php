<?php
/**
 * Router script for PHP built-in dev server.
 * Usage: php -S localhost:8000 router.php
 *
 * Without this, the built-in server bypasses index.php for paths that map to
 * existing files (e.g. /api/services → api/services/index.php), causing
 * "undefined function getDb()" fatal errors and missing CORS headers.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve existing static files directly (dashboard assets, uploads, etc.)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    $static = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map'];
    if (in_array(strtolower($ext), $static, true)) {
        return false; // let built-in server handle it
    }
}

// Dashboard pages: serve them directly (they have their own includes)
if (strpos($uri, '/dashboard') === 0 && file_exists(__DIR__ . $uri)) {
    return false;
}

// The built-in server sets SCRIPT_NAME to the full URI (e.g. /api/auth/login) instead of
// the actual file (/index.php). This breaks the base-path stripping logic in index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/index.php';
