<?php
/**
 * Router for PHP built-in server.
 * Run: php -S localhost:8080 -t . router.php
 * This ensures all requests go through index.php so config/database.php and routes load.
 */

// CORS: allow all origins and methods (preflight must be handled here so response has headers)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Max-Age: 86400');

// Preflight OPTIONS: respond immediately so CORS headers are always present
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// Serve static files if they exist (optional)
if (preg_match('#^/(assets|vendor|uploads)/#', $path) && file_exists(__DIR__ . $path)) {
    return false; // let built-in server serve the file
}

// Dashboard static assets: serve as file so dashboard PHP never runs with SCRIPT_NAME like /dashboard/assets/...
if (preg_match('#^/dashboard/(assets|vendor)/#', $path) && file_exists(__DIR__ . $path)) {
    return false;
}

// Dashboard: serve dashboard files so the server never runs from dashboard/ (avoids "require router.php" in wrong dir)
if (strpos($path, '/dashboard') === 0) {
    $sub = substr($path, strlen('/dashboard')) ?: '/';
    $sub = ($sub === '' || $sub === '/') ? '/index.php' : $sub;
    $sub = preg_replace('#/+#', '/', $sub);
    if (substr($sub, -1) === '/') $sub .= 'index.php';
    elseif (substr($sub, -4) !== '.php') $sub .= '.php';
    $file = __DIR__ . '/dashboard' . $sub;
    if (is_file($file)) {
        require $file;
        return true;
    }
    require __DIR__ . '/dashboard/index.php';
    return true;
}

// Everything else goes through index.php
require __DIR__ . '/index.php';
return true;
